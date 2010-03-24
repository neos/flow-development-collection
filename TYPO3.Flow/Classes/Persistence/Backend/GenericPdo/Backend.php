<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Backend\GenericPdo;

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
class Backend extends \F3\FLOW3\Persistence\Backend\AbstractSqlBackend {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Validation\ValidatorResolver
	 */
	protected $validatorResolver;

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
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the ValidatorResolver
	 *
	 * @param \F3\FLOW3\Validation\ValidatorResolver $validatorResolver
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectValidatorResolver(\F3\FLOW3\Validation\ValidatorResolver $validatorResolver) {
		$this->validatorResolver = $validatorResolver;
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
			$pdoHelper = $this->objectManager->create('F3\FLOW3\Utility\PdoHelper', $this->dataSourceName, $this->username, $this->password);
			$pdoHelper->importSql(FLOW3_PATH_FLOW3 . 'Resources/Private/Persistence/SQL/DDL.sql');
		} catch (\PDOException $e) {
			throw new \RuntimeException('Could not create persistence tables with DSN "' . $this->dataSourceName . '". PDO error: ' . $e->getMessage(), 1259701414);
		}
	}

	/**
	 * Commits the current persistence session. Wrap the whole process in a
	 * transaction, this gives massive speedups with SQLite (and still some when
	 * using InnoDB tables in MySQL).
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commit() {
		$this->databaseHandle->beginTransaction();
		parent::commit();
		$this->databaseHandle->commit();
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
	 * Creates a node for the given object and registers it with the identity map.
	 *
	 * @param object $object The object for which to create a node
	 * @param string $parentIdentifier The identifier of the object's parent, if any
	 * @return string The identifier of the created record
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createObjectRecord($object, $parentIdentifier = NULL) {
		$classSchema = $this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()];

		if ($classSchema->getUuidPropertyName() !== NULL) {
			$identifier = $object->FLOW3_AOP_Proxy_getProperty($classSchema->getUuidPropertyName());
		} elseif ($object instanceof \F3\FLOW3\AOP\ProxyInterface && $object->FLOW3_AOP_Proxy_hasProperty('FLOW3_Persistence_Entity_UUID')) {
			$identifier = $object->FLOW3_AOP_Proxy_getProperty('FLOW3_Persistence_Entity_UUID');
		} elseif ($object instanceof \F3\FLOW3\AOP\ProxyInterface && $object->FLOW3_AOP_Proxy_hasProperty('FLOW3_Persistence_ValueObject_Hash')) {
			$identifier = $object->FLOW3_AOP_Proxy_getProperty('FLOW3_Persistence_ValueObject_Hash');
		}

		if ($classSchema->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY) {
			$statementHandle = $this->databaseHandle->prepare('INSERT INTO "entities" ("identifier", "type") VALUES (?, ?)');
			$statementHandle->execute(array(
				$identifier,
				$classSchema->getClassName()
			));
		} else {
			if (!$this->hasValueobjectRecord($identifier)) {
				$statementHandle = $this->databaseHandle->prepare('INSERT INTO "valueobjects" ("identifier", "type") VALUES (?, ?)');
				$statementHandle->execute(array(
					$identifier,
					$classSchema->getClassName()
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
		if (isset($this->visitedDuringPersistence[$object])) {
			return $this->visitedDuringPersistence[$object];
		}

		$classSchema = $this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()];
		if ($this->persistenceSession->hasObject($object)) {
			$identifier = $this->persistenceSession->getIdentifierByObject($object);
			$objectState = self::OBJECTSTATE_RECONSTITUTED;
			if ($classSchema->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT) {
				return $identifier;
			}
		} else {
			$this->validateObject($object);
			$identifier = $this->createObjectRecord($object, $parentIdentifier);
			$objectState = self::OBJECTSTATE_NEW;
		}

		$this->visitedDuringPersistence[$object] = $identifier;

		$objectData = array(
			'identifier' => $identifier,
			'classname' => $classSchema->getClassName(),
			'properties' => $this->collectProperties($classSchema->getProperties(), $object, $identifier)
		);
		if (count($objectData['properties'])) {
			if ($objectState === self::OBJECTSTATE_RECONSTITUTED) {
				$this->validateObject($object);
			}
			$this->setProperties($objectData, $objectState);
		}
		if ($classSchema->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY) {
			$this->persistenceSession->registerReconstitutedEntity($object, $objectData);
		}
		$this->emitPersistedObject($object, $objectState);

		return $identifier;
	}

	/**
	 * Validates the given object and throws an exception if validation fails.
	 *
	 * @param object $object
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function validateObject($object) {
		$classSchema = $this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()];
		$validator = $this->validatorResolver->getBaseValidatorConjunction($classSchema->getClassName());
		if ($validator !== NULL && !$validator->isValid($object)) {
			$errorMessages = '';
			foreach ($validator->getErrors() as $error) {
				$errorMessages .= (string)$error . PHP_EOL;
			}
			throw new \F3\FLOW3\Persistence\Exception\ObjectValidationFailedException('An instance of "' . $object->FLOW3_AOP_Proxy_getProxyTargetClassName() . '" failed to pass validation with ' . count($validator->getErrors()) . ' error(s): ' . PHP_EOL . $errorMessages);
		}
	}

	/**
	 *
	 * @param array $properties The properties to collect (as per class schema)
	 * @param object $object The object to work on
	 * @param string $identifier The object's identifier
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function collectProperties(array $properties, $object, $identifier) {
		$propertyData = array();
		foreach ($properties as $propertyName => $propertyMetaData) {
			$propertyValue = $object->FLOW3_AOP_Proxy_getProperty($propertyName);
			$propertyType = $propertyMetaData['type'];

			$this->checkType($propertyType, $propertyValue);

				// handle all objects now, because even clean ones need to be traversed
				// as dirty checking is not recursive
			if ($propertyValue instanceof \F3\FLOW3\AOP\ProxyInterface) {
				if ($this->persistenceSession->isDirty($object, $propertyName)) {
					$propertyData[$propertyName] = array(
						'type' => $propertyType,
						'multivalue' => FALSE,
						'value' => array(
							'identifier' => $this->persistObject($propertyValue, $identifier)
						)
					);
				} else {
					$this->persistObject($propertyValue, $identifier);
				}
			} elseif ($this->persistenceSession->isDirty($object, $propertyName)) {
				switch ($propertyType) {
					case 'integer':
					case 'float':
					case 'string':
					case 'boolean':
						$propertyData[$propertyName] = array(
							'type' => $propertyType,
							'multivalue' => FALSE,
							'value' => $propertyValue
						);
					break;
					case 'DateTime':
						$propertyData[$propertyName] = array(
							'type' => 'DateTime',
							'multivalue' => FALSE,
							'value' => $this->processDateTime($propertyValue)
						);
					break;
					case 'array':
						$propertyData[$propertyName] = array(
							'type' => 'array',
							'multivalue' => TRUE,
							'value' => $this->processArray($propertyValue, $identifier, $this->getCleanState($object, $propertyName))
						);
					break;
					case 'SplObjectStorage':
						$propertyData[$propertyName] = array(
							'type' => 'SplObjectStorage',
							'multivalue' => TRUE,
							'value' => $this->processSplObjectStorage($propertyValue, $identifier, $this->getCleanState($object, $propertyName))
						);
					break;
				}
			}
		}

		return $propertyData;
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
					'type' => 'DateTime',
					'index' => $key,
					'value' => $value->getTimestamp()
				);
			} elseif ($value instanceof \SplObjectStorage) {
				throw new \RuntimeException('SplObjectStorage instances in arrays are not uspported - missing feature?!?', 1261048721);
			} elseif (is_object($value)) {
				$values[] = array(
					'type' => $this->getType($value),
					'index' => $key,
					'value' => array('identifier' => $this->persistObject($value, $parentIdentifier))
				);
			} elseif (is_array($value)) {
				throw new \RuntimeException('Nested arrays cannot be persisted - missing feature?!?', 1260284934);
			} else {
				$values[] = array(
					'type' => $this->getType($value),
					'index' => $key,
					'value' => $value
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
					'type' => 'DateTime',
					'index' => NULL,
					'value' => $object->getTimestamp()
				);
			} else {
				$values[] = array(
					'type' => $this->getType($object),
					'index' => NULL,
					'value' => array('identifier' => $this->persistObject($object, $parentIdentifier))
				);
			}
		}

		return $values;
	}

	/**
	 * Persists the given properties to the database. $objectData is expected to
	 * look like this:
	 * array(
	 *  'identifier' => '<the-uuid-for-this-entity>',
	 *  'classname' => '<The\Class\Name>',
	 *  'properties' => array(
	 *   '<name>' => array(
	 *    'type' => '...',
	 *    'multivalue' => boolean,
	 *    'value => array(
	 *      'index' => ...,
	 *      'type' => '...'
	 *      'value' => ...
	 *   )
	 *  )
	 * )
	 *
	 * @param array $objectData
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function setProperties(array $objectData, $objectState) {
		$insertPropertyStatementHandle = $this->databaseHandle->prepare('INSERT INTO "properties" ("parent", "name", "multivalue", "type") VALUES (?, ?, ?, ?)');
		foreach ($objectData['properties'] as $propertyName => $propertyData) {
			
				// optimize into one call to removeProperties
			if ($objectState === self::OBJECTSTATE_RECONSTITUTED) {
				$this->removeProperties(array($propertyName => array('parent' => $objectData['identifier'])));
			}

			$insertPropertyStatementHandle->execute(array(
				$objectData['identifier'],
				$propertyName,
				(integer)$propertyData['multivalue'],
				$propertyData['type']
			));

			if ($propertyData['value'] === NULL) {
				// we don't store those in properties_data
			} else {
				if ($propertyData['multivalue']) {
					foreach ($propertyData['value'] as $valueData) {
						$statementHandle = $this->databaseHandle->prepare('INSERT INTO "properties_data" ("parent", "name", "index", "type", "' . $this->getTypeName($valueData['type']) . '") VALUES (?, ?, ?, ?, ?)');
						$statementHandle->execute(array(
							$objectData['identifier'],
							$propertyName,
							$valueData['index'],
							$valueData['type'],
							is_array($valueData['value']) ? $valueData['value']['identifier'] : $valueData['value']
						));
					}
				} else {
					$statementHandle = $this->databaseHandle->prepare('INSERT INTO "properties_data" ("parent", "name", "index", "type", "' . $this->getTypeName($propertyData['type']) . '") VALUES (?, ?, ?, ?, ?)');
					$statementHandle->execute(array(
						$objectData['identifier'],
						$propertyName,
						NULL,
						$propertyData['type'],
						is_array($propertyData['value']) ? $propertyData['value']['identifier'] : $propertyData['value']
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
	 * @param object $parent
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removePropertiesByParent($parent) {
		$parentIdentifier = $this->persistenceSession->getIdentifierByObject($parent);
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "properties_data" WHERE "parent"=?');
		$statementHandle->execute(array($parentIdentifier));
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "properties" WHERE "parent"=?');
		$statementHandle->execute(array($parentIdentifier));
	}

	/**
	 * Removes all referenced entities (which are not aggregate roots) of the
	 * given $parent.
	 *
	 * @param object $parent
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeEntitiesByParent($parent) {
		$statementHandle = $this->databaseHandle->prepare('SELECT "identifier", "type" FROM "entities" WHERE "identifier" IN (SELECT DISTINCT "object" FROM "properties_data" WHERE "parent"=?)');
		$statementHandle->execute(array($this->persistenceSession->getIdentifierByObject($parent)));
		foreach ($statementHandle->fetchAll(\PDO::FETCH_ASSOC) as $entityRow) {
			if ($this->classSchemata[$entityRow['type']]->isAggregateRoot() !== TRUE) {
				$this->removeEntity($this->persistenceSession->getObjectByIdentifier($entityRow['identifier']));
			}
		}
	}

	/**
	 * Remove all referenced value objects (that are used only once at the time
	 * of action) of the given $parent
	 *
	 * @param object $parent
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeValueObjectsByParent($parent) {
		$statementHandle = $this->databaseHandle->prepare('SELECT "identifier" FROM "valueobjects" WHERE "identifier" IN (SELECT DISTINCT "object" FROM "properties_data" WHERE "parent"=?)');
		$statementHandle->execute(array($this->persistenceSession->getIdentifierByObject($parent)));
		while ($valueObjectIdentifier = $statementHandle->fetchColumn()) {
			$valueObject = $this->persistenceSession->getObjectByIdentifier($valueObjectIdentifier);
			if ($this->getValueObjectUsageCount($valueObject) === 1) {
				$this->removeValueObject($valueObject);
			}
		}
	}

	/**
	 * Removes an entity and all objects contained within it's boundary.
	 *
	 * @param object $object An object
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeEntity($object) {
		$this->removeEntitiesByParent($object);
		$this->removeValueObjectsByParent($object);
		$this->removePropertiesByParent($object);

		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "entities" WHERE "identifier"=?');
		$statementHandle->execute(array($this->persistenceSession->getIdentifierByObject($object)));

		$this->emitRemovedObject($object);
	}

	/**
	 * Removes a value objects.
	 *
	 * @param object $object
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeValueObject($object) {
		$this->removeValueObjectsByParent($object);
		$this->removePropertiesByParent($object);

		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "valueobjects" WHERE "identifier"=?');
		$statementHandle->execute(array($this->persistenceSession->getIdentifierByObject($object)));

		$this->emitRemovedObject($object);
	}

	/**
	 * Checks how often a value object is used by other objects.
	 *
	 * @param object $object
	 * @return integer
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getValueObjectUsageCount($object) {
		$statementHandle = $this->databaseHandle->prepare('SELECT COUNT(DISTINCT "parent") FROM "properties_data" WHERE "object"=?');
		$statementHandle->execute(array($this->persistenceSession->getIdentifierByObject($object)));
		return (integer)$statementHandle->fetchColumn();
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
		return count($this->getObjectDataByQuery($query));
	}

	/**
	 * Returns the object data for the given identifier.
	 *
	 * @param string $identifier The UUID or Hash of the object
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectDataByIdentifier($identifier) {
		$this->knownRecords = array();
		return $this->_getObjectData($identifier);
	}

	/**
	 * Returns the data for the record with the given identifier, be it an entity
	 * or value object. The data is recursively populated for the references
	 * found, unless a lazy loading object is encountered.
	 *
	 * @param string $identifier The UUID or Hash of the object
	 * @return object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function _getObjectData($identifier) {
		if (strlen($identifier) === 36) {
			$statementHandle = $this->databaseHandle->prepare('SELECT "identifier", "type" AS "classname" FROM "entities" WHERE "identifier"=?');
		} else {
			$statementHandle = $this->databaseHandle->prepare('SELECT "identifier", "type" AS "classname" FROM "valueobjects" WHERE "identifier"=?');
		}
		$statementHandle->execute(array($identifier));
		$objects = $this->processObjectRecords($statementHandle->fetchAll(\PDO::FETCH_ASSOC));
		return current($objects);
	}

	/**
	 * Returns the object data matching the $query.
	 *
	 * @param \F3\FLOW3\Persistence\QueryInterface $query
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectDataByQuery(\F3\FLOW3\Persistence\QueryInterface $query) {
		$parameters = array();
		$this->knownRecords = array();

		$sql = $this->buildQuery($query, $parameters);

		$statementHandle = $this->databaseHandle->prepare($sql);
		$statementHandle->execute($parameters);

		$objectData = $this->processObjectRecords($statementHandle->fetchAll(\PDO::FETCH_ASSOC));
		return $objectData;
	}

	/**
	 * Returns raw data for an object in the form of an array. See
	 * BackendInterface for details.
	 *
	 * @param array $objectRows
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processObjectRecords(array $objectRows) {
		$objectData = array();
		$propertyStatement = $this->databaseHandle->prepare('SELECT p."name", p."multivalue", p."type" AS "parenttype", d."index", d."type", d."string", d."integer", d."float", d."datetime", d."boolean", d."object" FROM "properties" AS p LEFT JOIN "properties_data" AS d ON p."parent"=d."parent" AND p."name"=d."name" WHERE p."parent"=?');

		foreach ($objectRows as $objectRow) {
			$this->knownRecords[$objectRow['identifier']] = TRUE;
			$propertyStatement->execute(array($objectRow['identifier']));
			$objectData[] = array(
				'identifier' => $objectRow['identifier'],
				'classname' => $objectRow['classname'],
				'properties' => $this->buildPropertiesArray($propertyStatement, $objectRow['classname'])
			);
		}

		return $objectData;
	}

	/**
	 * Iterates over the rows in the statement (must be executed already) and 
	 * returns an array with the property data.
	 * 
	 * @param PDOStatement $propertyStatement
	 * @param string $className The classname the properties we're dealing with are in
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function buildPropertiesArray(\PDOStatement $propertyStatement, $className) {
		$properties = array();
		foreach ($propertyStatement->fetchAll(\PDO::FETCH_ASSOC) as $propertyRow) {
				// we have a value on shelf
			if (isset($propertyRow['type'])) {
				$propertyMetadata = $this->classSchemata[$className]->getProperty($propertyRow['name']);
				if ($propertyRow['multivalue']) {
					$properties[$propertyRow['name']]['type'] = $propertyRow['parenttype'];
					$properties[$propertyRow['name']]['multivalue'] = TRUE;
					$properties[$propertyRow['name']]['value'][] = array('type' => $propertyRow['type'], 'index' => $propertyRow['index'], 'value' => $this->getValue($propertyRow, $propertyMetadata));
				} else {
					$properties[$propertyRow['name']] = array(
						'type' => $propertyRow['type'],
						'multivalue' => FALSE,
						'value' => $this->getValue($propertyRow, $propertyMetadata)
					);
				}
				// a NULL value
			} else {
				$properties[$propertyRow['name']] = array(
					'type' => ($propertyRow['multivalue'] == 1) ? $propertyRow['parenttype'] : $propertyRow['type'],
					'multivalue' => ($propertyRow['multivalue'] == 1),
					'value' => NULL
				);
			}
		}

		return $properties;
	}

	/**
	 * Returns the expected value for the given data, i.e. the expected native
	 * type.
	 *
	 * @param array $data
	 * @param array $propertyMetadata The metadat for property we're dealing with
	 * @return mixed
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getValue(array $data, $propertyMetadata) {
		$typename = $this->getTypeName($data['type']);
		switch ($typename) {
			case 'object':
				if (isset($this->knownRecords[$data['object']])) {
					return array('identifier' => $data['object']);
				} else {
						// check or lazy loading
					if ($propertyMetadata['lazy'] === TRUE) {
						return array('identifier' => $data['object'], 'classname' => $propertyMetadata['type'], 'properties' => array());
					} else {
						return $this->_getObjectData($data['object']);
					}
				}
				break;
			default:
				return $data[$typename];
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
			$sqlString .= ' ORDER BY ' . implode(', ', $sql['orderings']);
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
		$sql['fields'][] = '"_entity"."identifier" AS "identifier"';
		$sql['fields'][] = '"_entity"."type" AS "classname"';
		if ($query->getConstraint() === NULL && $query->getOrderings() === NULL) {
			$sql['tables'][] = '"entities" AS "_entity"';
			$sql['where'][] = '"_entity"."type"=?';
		} elseif ($query->getConstraint() === NULL) {
			$sql['tables'][] = '"entities" AS "_entity" INNER JOIN "properties_data" AS "d" ON "_entity"."identifier" = "d"."parent"';
			$sql['where'][] = '"_entity"."type"=?';
		} else {
			$sql['tables'][] = '"entities" AS "_entity" INNER JOIN "properties_data" AS "d" ON "_entity"."identifier" = "d"."parent"';
			$sql['where'][] = '"_entity"."type"=? AND ';
			$this->parseConstraint($query->getConstraint(), $sql, $parameters);
		}

		$sql = $this->parseOrderings($query, $sql);
	}

	/**
	 * Transforms an orderings into SQL-like order parts
	 *
	 * @param \F3\FLOW3\Persistence\QueryInterface $query
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseOrderings(\F3\FLOW3\Persistence\QueryInterface $query, array $sql) {
		if ($query->getOrderings() === NULL) return;

		$propertyData = $this->reflectionService->getClassSchema($query->getType())->getProperties();
		foreach ($query->getOrderings() as $propertyName => $order) {
			$sql['fields'][] = '"_orderingtable' . count($sql['orderings']) . '"."' . $propertyName . '"';
			$sql['tables'][] = 'LEFT JOIN (SELECT "parent", "' . $this->getTypeName($propertyData[$propertyName]['elementType'] ?: $propertyData[$propertyName]['type']) . '" AS "' . $propertyName . '" FROM "properties_data" WHERE "name" = ' . $this->databaseHandle->quote($propertyName) . ') AS "_orderingtable' . count($sql['orderings']) . '" ON "_orderingtable' . count($sql['orderings']) . '"."parent" = "d"."parent"';
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
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseConstraint(\F3\FLOW3\Persistence\QOM\Constraint $constraint, array &$sql, array &$parameters) {
		if ($constraint instanceof \F3\FLOW3\Persistence\QOM\LogicalAnd) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $sql, $parameters);
			$sql['where'][] = ' AND ';
			$this->parseConstraint($constraint->getConstraint2(), $sql, $parameters);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof \F3\FLOW3\Persistence\QOM\LogicalOr) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $sql, $parameters);
			$sql['where'][] = ' OR ';
			$this->parseConstraint($constraint->getConstraint2(), $sql, $parameters);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof \F3\FLOW3\Persistence\QOM\LogicalNot) {
			$sql['where'][] = '(NOT ';
			$this->parseConstraint($constraint->getConstraint(), $sql, $parameters);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof \F3\FLOW3\Persistence\QOM\Comparison) {
			$this->parseComparison($constraint, $sql, $parameters);
		}
	}

	/**
	 * Parse a Comparison into SQL and parameter arrays.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\Comparison $comparison The comparison to parse
	 * @param array &$sql SQL query parts to add to
	 * @param array &$parameters Parameters to bind to the SQL
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseComparison(\F3\FLOW3\Persistence\QOM\Comparison $comparison, array &$sql, array &$parameters) {
		if ($comparison->getOperator() === \F3\FLOW3\Persistence\QueryInterface::OPERATOR_IN) {
			$this->parseDynamicOperand($comparison->getOperand1(), $comparison->getOperator(), $sql, $parameters, NULL, $comparison->getOperand2());
			foreach ($comparison->getOperand2() as $value) {
				$parameters[] = $this->getPlainValue($value);
			}
		} else {
			$this->parseDynamicOperand($comparison->getOperand1(), $comparison->getOperator(), $sql, $parameters);
			$parameters[] = $this->getPlainValue($comparison->getOperand2());
		}
	}

	/**
	 * Returns a plain value, i.e. objects are flattened out if possible.
	 *
	 * @param mixed $input
	 * @return mixed
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getPlainValue($input) {
		if ($input instanceof \DateTime) {
			return $input->getTimestamp();
		} elseif (is_object($input) && $this->getIdentifierByObject($input) !== NULL) {
			return $this->getIdentifierByObject($input);
		} else {
			return $input;
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
	protected function parseDynamicOperand(\F3\FLOW3\Persistence\QOM\DynamicOperand $operand, $operator, array &$sql, array &$parameters, $valueFunction = NULL, $operand2 = NULL) {
		if ($operand instanceof \F3\FLOW3\Persistence\QOM\LowerCase) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $sql, $parameters, 'LOWER');
		} elseif ($operand instanceof \F3\FLOW3\Persistence\QOM\UpperCase) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $sql, $parameters, 'UPPER');
		} elseif ($operand instanceof \F3\FLOW3\Persistence\QOM\PropertyValue) {
			$selectorName = $operand->getSelectorName();
			$where = '';
			switch ($operator) {
				case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_IN:
					$coalesce = 'COALESCE("' . $selectorName . 'properties' . count($parameters) . '"."string", CAST("' . $selectorName . 'properties' . count($parameters) . '"."integer" AS CHAR), CAST("' . $selectorName . 'properties' . count($parameters) . '"."float" AS CHAR), CAST("' . $selectorName . 'properties' . count($parameters) . '"."datetime" AS CHAR), "' . $selectorName . 'properties' . count($parameters) . '"."boolean", "' . $selectorName . 'properties' . count($parameters) . '"."object")';
					$where = '("' . $selectorName . 'properties' . count($parameters) . '"."name" = ? AND ';
					if ($valueFunction === NULL) {
						$where .= $coalesce . ' IN (';
					} else {
						$where .= '' . $valueFunction . '(' . $coalesce . ') IN (';
					}
					$where .= implode(',', array_fill(0, count($operand2), '?')) . ')) ';
				break;
				case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_CONTAINS:
						// in our data structure we can do this using equality...
					$operator = \F3\FLOW3\Persistence\QueryInterface::OPERATOR_EQUAL_TO;
				default:
					$operator = $this->resolveOperator($operator);
					$coalesce = 'COALESCE("' . $selectorName . 'properties' . count($parameters) . '"."string", CAST("' . $selectorName . 'properties' . count($parameters) . '"."integer" AS CHAR), CAST("' . $selectorName . 'properties' . count($parameters) . '"."float" AS CHAR), CAST("' . $selectorName . 'properties' . count($parameters) . '"."datetime" AS CHAR), "' . $selectorName . 'properties' . count($parameters) . '"."boolean", "' . $selectorName . 'properties' . count($parameters) . '"."object")';
					$where = '("' . $selectorName . 'properties' . count($parameters) . '"."name" = ? AND ';
					if ($valueFunction === NULL) {
						$where .= $coalesce . ' ' . $operator . ' ?';
					} else {
						$where .= '' . $valueFunction . '(' . $coalesce . ') ' . $operator . ' ?';
					}
					$where .= ') ';
				break;
			}

			$sql['where'][] = $where;
			$sql['tables'][] = 'INNER JOIN "properties_data" AS "' . $selectorName . 'properties' . count($parameters) . '" ON "' . $selectorName . '"."identifier" = "' . $selectorName . 'properties' . count($parameters) . '"."parent"';
			$parameters[] = $operand->getPropertyName();
		}
	}

}

?>