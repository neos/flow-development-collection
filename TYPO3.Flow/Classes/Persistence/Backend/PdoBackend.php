<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The default FLOW3 persistence backend
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PdoBackend extends \F3\FLOW3\Persistence\Backend\AbstractSqlBackend {

	/**
	 * @var \F3\FLOW3\Object\ObjectFactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \PDO
	 */
	protected $databaseHandle;

	/**
	 * @var string
	 */
	protected $pdoDriver;

	/**
	 * @var array
	 */
	protected $knownRecords = array();

	/**
	 * Injects the Object Factory
	 *
	 * @param \F3\FLOW3\Object\ObjectFactoryInterface $objectFactory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\ObjectFactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Initializes the backend
	 *
	 * @param array $options
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initialize(array $options) {
		parent::initialize($options);
		$this->connect();
	}

	/**
	 * Connect to the database
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function connect() {
		$splitdsn = explode(':', $this->dataSourceName, 2);
		$this->pdoDriver = $splitdsn[0];

		if ($this->pdoDriver === 'sqlite' && !file_exists($splitdsn[1])) {
			$this->createTables();
		}

		$this->databaseHandle = new \PDO($this->dataSourceName, $this->username, $this->password);
		$this->databaseHandle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		if ($this->pdoDriver === 'mysql') {
			$this->databaseHandle->exec('SET SESSION sql_mode=\'ANSI\';');
		}
	}

	/**
	 * Creates the tables needed for the backend.
	 *
	 * @return void
	 * @throws \RuntimeException if something goes wrong
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createTables() {
		try {
			$pdoHelper = $this->objectFactory->create('F3\FLOW3\Utility\PdoHelper', $this->dataSourceName, $this->username, $this->password);
			$pdoHelper->importSql(FLOW3_PATH_FLOW3 . 'Resources/Private/Persistence/SQL/DDL.sql');
		} catch (\PDOException $e) {
			throw new \RuntimeException('Could not create persistence tables with DSN "' . $this->dataSourceName . '". PDO error: ' . $e->getMessage(), 1259701414);
		}
	}

	/**
	 * Checks if an object with the given UUID or hash is persisted.
	 *
	 * @param string $identifier
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function hasEntityRecord($identifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT COUNT("identifier") FROM "entities" WHERE "identifier"=?');
		$statementHandle->execute(array($identifier));
		return ($statementHandle->fetchColumn() > 0);
	}

	/**
	 * Checks if an object with the given UUID or hash is persisted.
	 *
	 * @param string $identifier
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function hasValueobjectRecord($identifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT COUNT("identifier") FROM "valueobjects" WHERE "identifier"=?');
		$statementHandle->execute(array($identifier));
		return ($statementHandle->fetchColumn() > 0);
	}

	/**
	 * Checks if the property with $propertyName is present for the $parent.
	 *
	 * @param string $parent
	 * @param string $propertyName
	 * @return boolean
	 */
	protected function hasProperty($parent, $propertyName) {
		$statementHandle = $this->databaseHandle->prepare('SELECT COUNT("parent") FROM "properties" WHERE "parent"=? AND "name"=?');
		$statementHandle->execute(array($parent, $propertyName));
		return ($statementHandle->fetchColumn() > 0);
	}

	/**
	 * Creates a node for the given object and registers it with the identity map.
	 *
	 * @param object $object The object for which to create a node
	 * @param string $parentIdentifier The identifier of the object's parent, if any
	 * @return string The identifier of the created record
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createObjectRecord($object, $parentIdentifier = NULL) {
		$className = $object->FLOW3_AOP_Proxy_getProxyTargetClassName();
		$classSchema = $this->classSchemata[$className];

		if ($classSchema->getUUIDPropertyName() !== NULL) {
			$identifier = $object->FLOW3_AOP_Proxy_getProperty($classSchema->getUUIDPropertyName());
		} elseif ($object instanceof \F3\FLOW3\AOP\ProxyInterface && $object->FLOW3_AOP_Proxy_hasProperty('FLOW3_Persistence_Entity_UUID')) {
			$identifier = $object->FLOW3_AOP_Proxy_getProperty('FLOW3_Persistence_Entity_UUID');
		} elseif ($object instanceof \F3\FLOW3\AOP\ProxyInterface && $object->FLOW3_AOP_Proxy_hasProperty('FLOW3_Persistence_ValueObject_Hash')) {
			$identifier = $object->FLOW3_AOP_Proxy_getProperty('FLOW3_Persistence_ValueObject_Hash');
		}

		if ($classSchema->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY) {
			$statementHandle = $this->databaseHandle->prepare('INSERT INTO "entities" ("identifier", "type") VALUES (?, ?)');
			$statementHandle->execute(array(
				$identifier,
				$className
			));
		} else {
			if (!$this->hasValueobjectRecord($identifier)) {
				$statementHandle = $this->databaseHandle->prepare('INSERT INTO "valueobjects" ("identifier", "type") VALUES (?, ?)');
				$statementHandle->execute(array(
					$identifier,
					$className
				));
			}
		}

		$this->persistenceSession->registerObject($object, $identifier);
		return $identifier;
	}

	/**
	 * Stores or updates an object in the underlying storage.
	 *
	 * @param object $object The object to persist
	 * @param string $parentIdentifier
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function persistObject($object, $parentIdentifier = NULL) {
		$classSchema = $this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()];

		if ($this->persistenceSession->hasObject($object)) {
			$identifier = $this->persistenceSession->getIdentifierByObject($object);
		} else {
			$identifier = $this->createObjectRecord($object, $parentIdentifier);
		}

		$this->visitedDuringPersistence[$object] = $identifier;

		$properties = array();
		foreach ($classSchema->getProperties() as $propertyName => $propertyMetaData) {
			$propertyValue = $object->FLOW3_AOP_Proxy_getProperty($propertyName);
			$propertyType = $propertyMetaData['type'];

			$this->checkType($propertyType, $propertyValue);

				// handle only dirty properties here
			if ($object instanceof \F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface && $object->FLOW3_Persistence_isDirty($propertyName)) {
				switch ($propertyType) {
					case 'DateTime':
						$properties[$propertyName] = array(
							'parent' => $identifier,
							'type' => $propertyType,
							'multivalue' => FALSE,
							'value' => array(array(
								'value' => $this->processDateTime($propertyValue),
								'index' => NULL,
								'type' => $propertyType,
							))
						);
					break;
					case 'array':
						$properties[$propertyName] = array(
							'parent' => $identifier,
							'type' => $propertyType,
							'multivalue' => TRUE,
							'value' => $this->processArray($propertyValue, $identifier, $this->getCleanState($object, $propertyName))
						);
					break;
					case 'SplObjectStorage':
						$properties[$propertyName] = array(
							'parent' => $identifier,
							'type' => $propertyType,
							'multivalue' => TRUE,
							'value' => $this->processSplObjectStorage($propertyValue, $identifier, $this->getCleanState($object, $propertyName))
						);
					break;
					case 'integer':
					case 'float':
					case 'string':
					case 'boolean':
						$properties[$propertyName] = array(
							'parent' => $identifier,
							'type' => $propertyType,
							'multivalue' => FALSE,
							'value' => array(array(
								'value' => $propertyValue,
								'index' => NULL,
								'type' => $propertyType
							))
						);
				}
			}

				// handle all objects now, because even clean ones need to be traversed
				// as dirty checking is not recursive
			if (is_object($propertyValue) && $propertyValue instanceof \F3\FLOW3\AOP\ProxyInterface) {
				if ($object->FLOW3_Persistence_isDirty($propertyName)) {
					$properties[$propertyName] = array(
						'parent' => $identifier,
						'type' => $propertyType,
						'multivalue' => FALSE,
						'value' => array(array(
							'index' => NULL,
							'type' => $propertyType
						))
					);
					if ($this->visitedDuringPersistence->contains($propertyValue)) {
						$properties[$propertyName]['value'][0]['value'] = $this->visitedDuringPersistence[$propertyValue];
					} else {
						$properties[$propertyName]['value'][0]['value'] = $this->persistObject($propertyValue, $identifier);
					}
				} elseif (!$this->visitedDuringPersistence->contains($propertyValue)) {
					$this->persistObject($propertyValue, $identifier);
				}
			}

		}

		if (count($properties)) {
			$this->setProperties($properties);
		}

		if ($classSchema->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY) {
			$object->FLOW3_Persistence_memorizeCleanState();
		}

		return $identifier;
	}

	/**
	 * Creates a unix timestamp from the given DateTime object. If NULL is given
	 * NULL will be returned.
	 *
	 * @param \DateTime $dateTime
	 * @return integer
	 */
	protected function processDateTime(\DateTime $dateTime = NULL) {
		if ($dateTime instanceof \DateTime) {
			return $dateTime->getTimestamp();
		} else {
			return NULL;
		}
	}

	/**
	 * Store an array as a set of records, with each array element becoming a
	 * property named like the key and the value.
	 *
	 * Note: Objects contained in the array will have a matching entry created,
	 * the objects must be persisted elsewhere!
	 *
	 * @param array $array The array to persist
	 * @param string $parentIdentifier
	 * @param array $previousArray the previously persisted state of the array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processArray(array $array = NULL, $parentIdentifier, array $previousArray = NULL) {
			// remove objects removed from array since reconstitution
		if ($previousArray !== NULL) {
			foreach ($previousArray as $value) {
				if (is_object($value) && !($value instanceof \DateTime || $value instanceof \SplObjectStorage)) {
					if ($array === NULL || !in_array($value, $array, TRUE)) {
						if ($this->classSchemata[$value->FLOW3_AOP_Proxy_getProxyTargetClassName()]->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY
								&& $this->classSchemata[$value->FLOW3_AOP_Proxy_getProxyTargetClassName()]->isAggregateRoot() === FALSE) {
							$this->removeEntity($value);
						} elseif ($this->classSchemata[$value->FLOW3_AOP_Proxy_getProxyTargetClassName()]->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT) {
							$this->removeValueObject($value);
						}
					}
				}
			}
		}

		if ($array === NULL) {
			return NULL;
		}

		$values = array();
		foreach ($array as $key => $value) {
			if ($value instanceof \DateTime) {
				$values[] = array(
					'value' => $value->getTimestamp(),
					'index' => $key,
					'type' => 'datetime'
				);
			} elseif ($value instanceof \SplObjectStorage) {
				throw new \RuntimeException('SplObjectStorage instances in arrays are not uspported - missing feature?!?', 1261048721);
			} elseif (is_object($value)) {
				$values[] = array(
					'value' => $this->persistObject($value, $parentIdentifier),
					'index' => $key,
					'type' => $this->getType($value)
				);
			} elseif (is_array($value)) {
				throw new \RuntimeException('Nested arrays cannot be persisted - missing feature?!?', 1260284934);
			} else {
				$values[] = array(
					'value' => $value,
					'index' => $key,
					'type' => $this->getType($value)
				);
			}
		}

		return $values;
	}

	/**
	 * Store an SplObjectStorage as a set of records.
	 *
	 * Note: Objects contained in the SplObjectStorage will have a matching
	 * entry created, the objects must be persisted elsewhere!
	 *
	 * @param \SplObjectStorage $splObjectStorage The SplObjectStorage to persist
	 * @param string $parentIdentifier
	 * @param \SplObjectStorage $previousObjectStorage the previously persisted state of the SplObjectStorage
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processSplObjectStorage(\SplObjectStorage $splObjectStorage = NULL, $parentIdentifier, \SplObjectStorage $previousObjectStorage = NULL) {
		$values = array();

			// remove objects detached since reconstitution
		if ($previousObjectStorage !== NULL) {
			foreach ($previousObjectStorage as $object) {
				if ($splObjectStorage === NULL || !$splObjectStorage->contains($object)) {
					if ($this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()]->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY
							&& $this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()]->isAggregateRoot() === FALSE) {
						$this->removeEntity($object);
					} elseif ($this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()]->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT) {
						$this->removeValueObject($object);
					}
				}
			}
		}

		if ($splObjectStorage === NULL) {
			return NULL;
		}

		foreach ($splObjectStorage as $object) {
			if ($object instanceof \DateTime) {
				$values[] = array(
					'value' => $object->getTimestamp(),
					'index' => NULL,
					'type' => 'datetime'
				);
			} else {
				$values[] = array(
					'value' => $this->persistObject($object, $parentIdentifier),
					'index' => NULL,
					'type' => $this->getType($object)
				);
			}
		}

		return $values;
	}

	/**
	 * Persists the given properties to the database.
	 *
	 * @param array $properties
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function setProperties(array $properties) {
		$insertPropertyStatementHandle = $this->databaseHandle->prepare('INSERT INTO "properties" ("parent", "name", "multivalue", "type") VALUES (?, ?, ?, ?)');
		foreach ($properties as $propertyName => $propertyData) {
			if ($this->hasProperty($propertyData['parent'], $propertyName)) {
				$this->removeProperties(array($propertyName => array('parent' => $propertyData['parent'])));
			}
			$insertPropertyStatementHandle->execute(array(
				$propertyData['parent'],
				$propertyName,
				(integer)$propertyData['multivalue'],
				$propertyData['type']
			));

			if (is_array($propertyData['value'])) {
				foreach ($propertyData['value'] as $valueData) {
					$statementHandle = $this->databaseHandle->prepare('INSERT INTO "properties_data" ("parent", "name", "index", "type", "' . $this->getTypeName($valueData['type']) . '") VALUES (?, ?, ?, ?, ?)');
					$statementHandle->execute(array(
						$propertyData['parent'],
						$propertyName,
						$valueData['index'],
						$this->getTypeName($valueData['type']),
						$valueData['value']
					));
				}
			}
		}
	}

	/**
	 * Removes the property with $propertyName from $parent.
	 *
	 * @param array $properties
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeProperties($properties) {
		$deletePropertyStatementHandle = $this->databaseHandle->prepare('DELETE FROM "properties" WHERE "parent"=? AND "name"=?');
		$deleteDataStatementHandle = $this->databaseHandle->prepare('DELETE FROM "properties_data" WHERE "parent"=? AND "name"=?');
		foreach ($properties as $propertyName => $propertyData) {
			$deletePropertyStatementHandle->execute(array($propertyData['parent'], $propertyName));
			$deleteDataStatementHandle->execute(array($propertyData['parent'], $propertyName));
		}
	}

	/**
	 * Removes all properties attached to the given $parent.
	 *
	 * @param string $parentIdentifier
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removePropertiesByParent($parentIdentifier) {
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "properties_data" WHERE "parent"=?');
		$statementHandle->execute(array($parentIdentifier));
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "properties" WHERE "parent"=?');
		$statementHandle->execute(array($parentIdentifier));
	}

	/**
	 * Removes all referenced entities (which are not aggregate roots) of the
	 * given $parent.
	 *
	 * @param string $parentIdentifier
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeEntitiesByParent($parentIdentifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT "identifier", "type" FROM "entities" WHERE "identifier" IN (SELECT DISTINCT "object" FROM "properties_data" WHERE "parent"=?)');
		$statementHandle->execute(array($parentIdentifier));
		foreach ($statementHandle->fetchAll(\PDO::FETCH_ASSOC) as $entityRow) {
			if ($this->classSchemata[$entityRow['type']]->isAggregateRoot() !== TRUE) {
				$this->removeEntity($entityRow['identifier']);
			}
		}
	}

	/**
	 * Remove all referenced value objects (that are used only once at the time
	 * of action) of the given $parent
	 *
	 * @param string $parentIdentifier
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeValueObjectsByParent($parentIdentifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT "identifier" FROM "valueobjects" WHERE "identifier" IN (SELECT DISTINCT "object" FROM "properties_data" WHERE "parent"=?)');
		$statementHandle->execute(array($parentIdentifier));
		while ($valueObjectIdentifier = $statementHandle->fetchColumn()) {
			if ($this->getValueObjectUsageCount($valueObjectIdentifier) === 1) {
				$this->removeValueObject($valueObjectIdentifier);
			}
		}
	}

	/**
	 * Removes an entity and all objects contained within it's bundary.
	 *
	 * @param mixed $subject An object or it's (internal) identifier
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeEntity($subject) {
		if (is_object($subject)) {
			$subject = $this->persistenceSession->getIdentifierByObject($subject);
		}

		$this->removeEntitiesByParent($subject);
		$this->removeValueObjectsByParent($subject);
		$this->removePropertiesByParent($subject);

		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "entities" WHERE "identifier"=?');
		$statementHandle->execute(array($subject));
	}

	/**
	 * Removes a value objects.
	 *
	 * @param mixed $subject An object or it's (internal) identifier
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeValueObject($subject) {
		if (is_object($subject)) {
			$subject = $subject->FLOW3_Persistence_ValueObject_Hash;
		}

		$this->removeValueObjectsByParent($subject);
		$this->removePropertiesByParent($subject);

		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "valueobjects" WHERE "identifier"=?');
		$statementHandle->execute(array($subject));
	}

	/**
	 * Checks how often a value object is used by other objects.
	 *
	 * @param mixed $subject An object or it's (internal) identifier
	 * @return integer
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getValueObjectUsageCount($subject) {
		if (is_object($subject)) {
			$subject = $subject->FLOW3_Persistence_ValueObject_Hash;
		}
		$statementHandle = $this->databaseHandle->prepare('SELECT COUNT(DISTINCT "parent") FROM "properties_data" WHERE "object"=?');
		$statementHandle->execute(array($subject));
		return $statementHandle->fetchColumn();
	}

	/**
	 * Returns the type name as used in the database table names.
	 *
	 * @param string $type
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getTypeName($type) {
		if (strstr($type, '\\')) {
			return 'object';
		} else {
			return strtolower($type);
		}
	}

	/**
	 * Returns the number of records matching the query.
	 *
	 * @param \F3\FLOW3\Persistence\QueryInterface $query
	 * @return integer
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo optimize so properties are ignored and the db is asked for the count only
	 */
	public function getObjectCountByQuery(\F3\FLOW3\Persistence\QueryInterface $query) {
		return count($this->getObjectRecordsByQuery($query));
	}

	/**
	 * Returns the data for the record with the given identifier., be it an entity or
	 * value object.
	 *
	 * @param string $identifier The UUID or Hash of the object
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectRecord($identifier) {
		$this->knownRecords = array();
		return $this->_getObjectRecord($identifier);
	}

	/**
	 * Returns the data for the record with the given identifier., be it an entity or
	 * value object. The data is recursively populated for the references found.
	 *
	 * @param string $identifier The UUID or Hash of the object
	 * @return object<
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function _getObjectRecord($identifier) {
		if ($this->hasEntityRecord($identifier)) {
			$statementHandle = $this->databaseHandle->prepare('SELECT "identifier", "type" AS "classname" FROM "entities" WHERE "identifier"=?');
		} else {
			$statementHandle = $this->databaseHandle->prepare('SELECT "identifier", "type" AS "classname" FROM "valueobjects" WHERE "identifier"=?');
		}
		$statementHandle->execute(array($identifier));
		$objects = $this->processObjectRecords($statementHandle);
		return current($objects);
	}

	/**
	 * Returns the objects matching the $query.
	 *
	 * @param \F3\FLOW3\Persistence\QueryInterface $query
	 * @return array<object>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectRecordsByQuery(\F3\FLOW3\Persistence\QueryInterface $query) {
		$parameters = array();
		$this->knownRecords = array();

		$sql = $this->buildQuery($query, $parameters);

		$statementHandle = $this->databaseHandle->prepare($sql);
		$statementHandle->execute($parameters);

		$objects = $this->processObjectRecords($statementHandle);
		return $objects;
	}

	/**
	 *
	 * @param \PDOStatement $statementHandle
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processObjectRecords(\PDOStatement $statementHandle) {
		$objectData = array();
		$propertyStatement = $this->databaseHandle->prepare('SELECT p."name", p."multivalue", d."index", d."type", d."string", d."integer", d."float", d."datetime", d."boolean", d."object" FROM "properties" AS p LEFT JOIN "properties_data" AS d ON p."parent"=d."parent" AND p."name"=d."name" WHERE p."parent"=?');
		foreach ($statementHandle->fetchAll(\PDO::FETCH_ASSOC) as $entityRow) {
			$this->knownRecords[$entityRow['identifier']] = TRUE;
			$propertyData = array();
			$propertyStatement->execute(array($entityRow['identifier']));
			foreach ($propertyStatement->fetchAll(\PDO::FETCH_ASSOC) as $propertyRow) {
				if (isset($propertyRow['type'])) {
					if ($propertyRow['multivalue']==1 && isset($propertyRow['index'])) {
						$propertyData[$propertyRow['name']]['value'][$propertyRow['index']] = array('type' => $propertyRow['type'], 'value' => $this->getValue($propertyRow));
					} elseif ($propertyRow['multivalue']==1) {
						$propertyData[$propertyRow['name']]['value'][] = array('type' => $propertyRow['type'], 'value' => $this->getValue($propertyRow));
					} else {
						$propertyData[$propertyRow['name']]['value'] = array('type' => $propertyRow['type'], 'value' => $this->getValue($propertyRow));
					}
				} else {
					$propertyData[$propertyRow['name']]['value'] = array();
				}
			}
			$objectData[] = array('identifier' => $entityRow['identifier'], 'classname' => $entityRow['classname'], 'propertyData' => $propertyData);
		}

		return $objectData;
	}

	/**
	 * Returns the expected value for the given data, i.e. the expected native
	 * type.
	 *
	 * @param array $data
	 * @return mixed
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getValue(array $data) {
		if ($data['type'] === 'object') {
			if (isset($this->knownRecords[$data['object']])) {
				return array('identifier' => $data['object']);
			} else {
				return $this->_getObjectRecord($data['object']);
			}
		} else {
			return $data[$data['type']];
		}
	}

	/**
	 * Builds a query string from the given Query.
	 *
	 * @param \F3\FLOW3\Persistence\QueryInterface $query
	 * @param array $parameters
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function buildQuery(\F3\FLOW3\Persistence\QueryInterface $query, array &$parameters) {
		$sql = array('fields' => array(), 'tables' => array(), 'where' => array(), 'orderings' => array());
		$this->parseQuery($query, $sql, $parameters);

		$sqlString = 'SELECT DISTINCT ' . implode(', ', $sql['fields']) . ' FROM ' . implode(' ', $sql['tables']);
		$sqlString .= ' WHERE ' . implode(' ', $sql['where']);

		if (count($sql['orderings'])) {
			$sqlString .= 'ORDER BY ' . implode(', ', $sql['orderings']);
		}

		if ($query->getLimit() !== NULL) {
			$sqlString .= ' LIMIT ' . $query->getLimit() . ' OFFSET '. $query->getOffset();
		}

		return $sqlString;
	}

	/**
	 * Parses a Query into an array of SQL parts and an array of parameters.
	 *
	 * @param \F3\FLOW3\Persistence\QueryInterface $query
	 * @param array &$sql
	 * @param array &$parameters
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseQuery(\F3\FLOW3\Persistence\QueryInterface $query, array &$sql, array &$parameters) {
		$parameters[] = $query->getType();
		$sql['fields'][] = '"_entity"."identifier"';
		$sql['fields'][] = '"_entity"."type" AS "classname"';
		if ($query->getConstraint() === NULL) {
			$sql['tables'][] = '"entities" AS "_entity"';
			$sql['where'][] = '"_entity"."type"=?';
		} else {
			$sql['tables'][] = '"entities" AS "_entity" INNER JOIN "properties_data" AS "d" ON "_entity"."identifier" = "d"."parent"';
			$sql['where'][] = '"_entity"."type"=? AND ';
			$this->parseConstraint($query->getConstraint(), $sql, $parameters, $query->getOperands());
		}
		if ($query->getOrderings() !== NULL) {
			$sql = $this->parseOrderings($query->getOrderings(), $sql);
		}
	}

	/**
	 * Transforms an array with Orderings into SQL-like order parts
	 *
	 * @param array $orderings
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseOrderings(array $orderings, array $sql) {
		foreach ($orderings as $propertyName => $order) {
			$sql['fields'][] = '"_orderingtable' . count($sql['orderings']) . '"."' . $propertyName . '"';
			$sql['tables'][] = 'LEFT JOIN (SELECT "parent", COALESCE("string", CAST("integer" AS CHAR), CAST("float" AS CHAR), CAST("datetime" AS CHAR), "boolean", "object") AS "' . $propertyName . '" FROM "properties_data" WHERE "name" = ' . $this->databaseHandle->quote($propertyName) . ') AS "_orderingtable' . count($sql['orderings']) . '" ON "_orderingtable' . count($sql['orderings']) . '"."parent" = "d"."parent"';
			$sql['orderings'][] = '"_orderingtable' . count($sql['orderings']) . '"."' . $propertyName . '" ' . $order;
		}
		return $sql;
	}

	/**
	 * Transforms a constraint into SQL and parameter arrays
	 *
	 * @param \F3\FLOW3\Persistence\QOM\Constraint $constraint
	 * @param array &$sql
	 * @param array &$parameters
	 * @param array $operands
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseConstraint(\F3\FLOW3\Persistence\QOM\Constraint $constraint, array &$sql, array &$parameters, array $operands) {
		if ($constraint instanceof \F3\FLOW3\Persistence\QOM\LogicalAnd) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $sql, $parameters, $operands);
			$sql['where'][] = ' AND ';
			$this->parseConstraint($constraint->getConstraint2(), $sql, $parameters, $operands);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof \F3\FLOW3\Persistence\QOM\LogicalOr) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $sql, $parameters, $operands);
			$sql['where'][] = ' OR ';
			$this->parseConstraint($constraint->getConstraint2(), $sql, $parameters, $operands);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof \F3\FLOW3\Persistence\QOM\LogicalNot) {
			$sql['where'][] = '(NOT ';
			$this->parseConstraint($constraint->getConstraint(), $sql, $parameters, $operands);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof \F3\FLOW3\Persistence\QOM\Comparison) {
			$this->parseComparison($constraint, $sql, $parameters, $operands);
		}
	}

	/**
	 * Parse a Comparison into SQL and parameter arrays.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\Comparison $comparison The comparison to parse
	 * @param array &$sql SQL query parts to add to
	 * @param array &$parameters Parameters to bind to the SQL
	 * @param array $operands The bound variables in the query and their values
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseComparison(\F3\FLOW3\Persistence\QOM\Comparison $comparison, array &$sql, array &$parameters, array $operands) {
		$this->parseDynamicOperand($comparison->getOperand1(), $comparison->getOperator(), $sql, $parameters);

		if ($comparison->getOperand2() instanceof \F3\FLOW3\Persistence\QOM\BindVariableValue) {
			$value = $operands[$comparison->getOperand2()->getBindVariableName()];
			if ($value instanceof \DateTime) {
				$parameters[] = $value->getTimestamp();
			} elseif (is_object($value)) {
				$parameters[] = $this->getIdentifierByObject($value);
			} else {
				$parameters[] = $value;
			}
		} elseif ($comparison->getOperand2() instanceof \F3\FLOW3\Persistence\QOM\Literal) {
			$parameters[] = $comparison->getOperand2()->getLiteralValue();
		}
	}

	/**
	 * Parse a DynamicOperand into SQL and parameter arrays.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\DynamicOperand $operand
	 * @param string $operator One of the JCR_OPERATOR_* constants
	 * @param array &$sql
	 * @param array &$parameters
	 * @param string $valueFunction an optional SQL function to apply to the operand value
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseDynamicOperand(\F3\FLOW3\Persistence\QOM\DynamicOperand $operand, $operator, array &$sql, array &$parameters, $valueFunction = NULL) {
		if ($operand instanceof \F3\FLOW3\Persistence\QOM\LowerCase) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $sql, $parameters, 'LOWER');
		} elseif ($operand instanceof \F3\FLOW3\Persistence\QOM\UpperCase) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $sql, $parameters, 'UPPER');
		} elseif ($operand instanceof \F3\FLOW3\Persistence\QOM\PropertyValue) {
			$selectorName = $operand->getSelectorName();
			$operator = $this->resolveOperator($operator);
			$coalesce = 'COALESCE("' . $selectorName . 'properties' . count($parameters) . '"."string", CAST("' . $selectorName . 'properties' . count($parameters) . '"."integer" AS CHAR), CAST("' . $selectorName . 'properties' . count($parameters) . '"."float" AS CHAR), CAST("' . $selectorName . 'properties' . count($parameters) . '"."datetime" AS CHAR), "' . $selectorName . 'properties' . count($parameters) . '"."boolean", "' . $selectorName . 'properties' . count($parameters) . '"."object")';
			$constraintSQL = '("' . $selectorName . 'properties' . count($parameters) . '"."name" = ? AND ';
			if ($valueFunction === NULL) {
				$constraintSQL .= $coalesce . ' ' . $operator . ' ?';
			} else {
				$constraintSQL .= '' . $valueFunction . '(' . $coalesce . ') ' . $operator . ' ?';
			}
			$constraintSQL .= ') ';

			$sql['where'][] = $constraintSQL;
			$sql['tables'][] = 'INNER JOIN "properties_data" AS "' . $selectorName . 'properties' . count($parameters) . '" ON "' . $selectorName . '"."identifier" = "' . $selectorName . 'properties' . count($parameters) . '"."parent"';
			$parameters[] = $operand->getPropertyName();
		}
	}

}

?>
