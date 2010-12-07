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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Backend extends \F3\FLOW3\Persistence\Backend\AbstractSqlBackend {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

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
			$this->databaseHandle = new \PDO($this->dataSourceName, $this->username, $this->password);
			$this->createTables();
		} else {
			$this->databaseHandle = new \PDO($this->dataSourceName, $this->username, $this->password);
		}
		$this->databaseHandle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		if ($this->pdoDriver === 'mysql') {
			$this->databaseHandle->exec('SET SESSION sql_mode=\'ANSI\';');
		}
	}

	/**
	 * Creates the tables needed for the backend.
	 *
	 * @return void
	 * @throws \F3\FLOW3\Persistence\Exception if something goes wrong
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createTables() {
		try {
			\F3\FLOW3\Utility\PdoHelper::importSql($this->databaseHandle, $this->pdoDriver, FLOW3_PATH_FLOW3 . 'Resources/Private/Persistence/SQL/DDL.sql');
		} catch (\PDOException $e) {
			throw new \F3\FLOW3\Persistence\Exception('Could not create persistence tables with DSN "' . $this->dataSourceName . '". PDO error: ' . $e->getMessage(), 1259701414);
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
	protected function hasValueobjectRecord($identifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT COUNT("identifier") FROM "valueobjects" WHERE "identifier"=?');
		$statementHandle->execute(array($identifier));
		return ($statementHandle->fetchColumn() > 0);
	}

	/**
	 * Creates a node for the given object and registers it with the identity map.
	 *
	 * @param object $object The object for which to create a node
	 * @return string The identifier of the created record
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createObjectRecord($object, $parentIdentifier) {
		$classSchema = $this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()];
		$identifier = $this->getIdentifierFromObject($object);

		if ($classSchema->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY) {
			$statementHandle = $this->databaseHandle->prepare('INSERT INTO "entities" ("identifier", "type", "parent") VALUES (?, ?, ?)');
			$statementHandle->execute(array(
				$identifier,
				$classSchema->getClassName(),
				$parentIdentifier
			));
		} else {
			$statementHandle = $this->databaseHandle->prepare('INSERT INTO "valueobjects" ("identifier", "type") VALUES (?, ?)');
			$statementHandle->execute(array(
				$identifier,
				$classSchema->getClassName()
			));
		}

		return $identifier;
	}

	/**
	 * Actually store an object, backend-specific
	 *
	 * @param object $object
	 * @param string $identifier
	 * @param string $parentIdentifier
	 * @param array $objectData
	 * @return integer one of self::OBJECTSTATE_*
	 */
	protected function storeObject($object, $identifier, $parentIdentifier, array &$objectData) {
		$classSchema = $this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()];
		if ($this->persistenceSession->hasObject($object)) {
			if ($classSchema->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT) {
				return $identifier;
			}
			$objectState = self::OBJECTSTATE_RECONSTITUTED;
		} elseif ($classSchema->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT && $this->hasValueobjectRecord($identifier)) {
			return $identifier;
		} else {
			$this->validateObject($object);
			$this->createObjectRecord($object, $parentIdentifier);
			$this->persistenceSession->registerObject($object, $identifier);
			$objectState = self::OBJECTSTATE_NEW;
		}

		$dirty = FALSE;
		$objectData = array(
			'identifier' => $identifier,
			'classname' => $classSchema->getClassName(),
			'properties' => $this->collectProperties($identifier, $object, $classSchema->getProperties(), $dirty)
		);
		if (count($objectData['properties'])) {
			if ($objectState === self::OBJECTSTATE_RECONSTITUTED) {
				$this->validateObject($object);
			}
			$this->setProperties($objectData, $objectState);
		}
		return $objectState;
	}

	/**
	 * "Serializes" a nested array for storage.
	 *
	 * @param string $parentIdentifier
	 * @param array $nestedArray
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processNestedArray($parentIdentifier, array $nestedArray, \Closure $handler = NULL) {
		$that = $this;
		return parent::processNestedArray($parentIdentifier, $nestedArray, function($parentIdentifier, $identifier, $data) use ($that) {
			$that->storePropertyData($parentIdentifier, $identifier, $data);
		});
	}

	/**
	 * Persists the given properties to the database. $objectData is expected to
	 * look like documented in:
	 *  "Documentation/PersistenceFramework object data format.txt"
	 *
	 * @param array $objectData
	 * @param integer $objectState one of self::OBJECTSTATE_*
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

			$this->storePropertyData($objectData['identifier'], $propertyName, $propertyData);
		}
	}

	/**
	 *
	 * @param string $parentIdentifier
	 * @param string $propertyName
	 * @param array $propertyData
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function storePropertyData($parentIdentifier, $propertyName, array $propertyData) {
		if ($propertyData['multivalue'] && $propertyData['value'] !== NULL) {
			foreach ($propertyData['value'] as $valueData) {
				if ($valueData['value'] === NULL) {
					$statementHandle = $this->databaseHandle->prepare('INSERT INTO "properties_data" ("parent", "name", "index", "type") VALUES (?, ?, ?, \'NULL\')');
					$statementHandle->execute(array(
						$parentIdentifier,
						$propertyName,
						$valueData['index']
					));
				} else {
					$statementHandle = $this->databaseHandle->prepare('INSERT INTO "properties_data" ("parent", "name", "index", "type", "' . $this->getTypeName($valueData['type']) . '") VALUES (?, ?, ?, ?, ?)');
					$statementHandle->execute(array(
						$parentIdentifier,
						$propertyName,
						$valueData['index'],
						$valueData['type'],
						is_array($valueData['value']) ? $valueData['value']['identifier'] : $valueData['value']
					));
				}
			}
		} elseif ($propertyData['multivalue']) {
			$statementHandle = $this->databaseHandle->prepare('INSERT INTO "properties_data" ("parent", "name", "type") VALUES (?, ?, \'NULL\')');
			$statementHandle->execute(array(
				$parentIdentifier,
				$propertyName
			));
		} else {
			$statementHandle = $this->databaseHandle->prepare('INSERT INTO "properties_data" ("parent", "name", "index", "type", "' . $this->getTypeName($propertyData['type']) . '") VALUES (?, ?, ?, ?, ?)');
			$statementHandle->execute(array(
				$parentIdentifier,
				$propertyName,
				NULL,
				$propertyData['type'],
				is_array($propertyData['value']) ? $propertyData['value']['identifier'] : $propertyData['value']
			));
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
		$statementHandle = $this->databaseHandle->prepare('SELECT "identifier", "type" FROM "entities" WHERE "parent" = ?');
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
		$this->knownRecords = array();

		$parsedQuery = $this->buildQuery($query);

		$statementHandle = $this->databaseHandle->prepare($parsedQuery['sql']);
		$statementHandle->execute($parsedQuery['parameters']);

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
		$propertyStatement = $this->databaseHandle->prepare('SELECT p."name", p."multivalue", p."type" AS "parenttype", d."index", d."type", d."array", d."string", d."integer", d."float", d."datetime", d."boolean", d."object" FROM "properties" AS p LEFT JOIN "properties_data" AS d ON p."parent"=d."parent" AND p."name"=d."name" WHERE p."parent"=?');

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
				// the property does no longer exist in the class
			if (!$this->classSchemata[$className]->hasProperty($propertyRow['name'])) continue;

				// we have a value (including NULL) on shelf
			if (isset($propertyRow['type'])) {
				$propertyMetadata = $this->classSchemata[$className]->getProperty($propertyRow['name']);
					// a NULL value for a multi-value property
				if ($propertyRow['multivalue'] && $propertyRow['type'] === 'NULL' && !isset($propertyRow['index'])) {
					$properties[$propertyRow['name']] = array(
						'type' => $propertyRow['type'],
						'multivalue' => TRUE,
						'value' => NULL
					);
				} elseif ($propertyRow['multivalue']) {
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
				// no entry in properties_data, empty collection
			} else {
				$properties[$propertyRow['name']] = array(
					'type' => $propertyRow['parenttype'],
					'multivalue' => TRUE,
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
	 * @param array $propertyMetadata The metadata for property we're dealing with
	 * @return mixed
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getValue(array $data, array $propertyMetadata) {
		$typename = $this->getTypeName($data['type']);
		if (!isset($data[$typename])) {
			return NULL;
		}
		switch ($typename) {
			case 'object':
				if (isset($this->knownRecords[$data['object']])) {
					return array('identifier' => $data['object']);
				} else {
						// check for lazy loading
					if ($propertyMetadata['lazy'] === TRUE) {
						return array('identifier' => $data['object'], 'classname' => $data['type'], 'properties' => array());
					} else {
						return $this->_getObjectData($data['object']);
					}
				}
				break;
			case 'array':
				return $this->getArray($data['array']);
				break;
			default:
				return $data[$typename];
		}
	}

	/**
	 *
	 * @param string $arrayIdentifier
	 * @return array
	 */
	protected function getArray($arrayIdentifier) {
		$nestedArray = array();
		$statement = $this->databaseHandle->prepare('SELECT "index", "type", "array", "string", "integer", "float", "datetime", "boolean", "object" FROM "properties_data" WHERE "name"=?');
		$statement->execute(array($arrayIdentifier));

		foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			$nestedArray[] = array('type' => $row['type'], 'index' => $row['index'], 'value' => $this->getValue($row, array('lazy' => FALSE)));
		}

		return $nestedArray;
	}

	/**
	 * Builds a query string from the given Query.
	 *
	 * @param \F3\FLOW3\Persistence\QueryInterface $query
	 * @param array $parameters
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function buildQuery(\F3\FLOW3\Persistence\QueryInterface $query) {
		$sql = array(
			'fields' => array('"_entity"."identifier" AS "identifier"', '"_entity"."type" AS "classname"'),
			'tables' => array(),
			'where' => '',
			'orderings' => ''
		);
		$parameters = array('fields' => array(), 'values' => array());

		if ($query->getConstraint() === NULL && $query->getOrderings() === array()) {
			$sql['tables'][] = '"entities" AS "_entity"';
			$sql['where'] = '"_entity"."type"=?';
			$parameters['values'][] = $query->getType();
		} elseif ($query->getConstraint() === NULL) {
			$sql['tables'][] = '"entities" AS "_entity", "properties_data" AS "d"';
			$sql['where'] = '"_entity"."identifier" = "d"."parent" AND "_entity"."type"=?';
			$parameters['values'][] = $query->getType();
		} else {
			$sql['tables'][] = '"entities" AS "_entity" LEFT JOIN "properties_data" AS "d" ON "_entity"."parent" = "d"."parent"';
			$sql['where'] = array('fields' => array(), 'values' => array());
			$this->parseConstraint($query->getConstraint(), $sql, $parameters);
			$sql['where'] = implode(' AND ', $sql['where']['fields']) . ' AND ' . implode(' ', $sql['where']['values']) . ' AND "_entity"."type"=?';
			$parameters['values'][] = $query->getType();
		}

		$this->parseOrderings($query, $sql);

		$sqlString = 'SELECT DISTINCT ' . implode(', ', $sql['fields']) . ' FROM ' . implode(' ', $sql['tables']) . ' WHERE ' . $sql['where'];

		if ($sql['orderings'] !== '') {
			$sqlString .= ' ORDER BY ' . $sql['orderings'];
		}

		if ($query->getLimit() !== NULL) {
			$sqlString .= ' LIMIT ' . $query->getLimit() . ' OFFSET '. $query->getOffset();
		}

		return array(
			'sql' => $sqlString,
			'parameters' => array_merge($parameters['fields'], $parameters['values'])
		);
	}

	/**
	 * Transforms an orderings into SQL-like order parts
	 *
	 * @param \F3\FLOW3\Persistence\QueryInterface $query
	 * @param array &$sql
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseOrderings(\F3\FLOW3\Persistence\QueryInterface $query, array &$sql) {
		if ($query->getOrderings() === array()) return;

		$orderings = array();
		$propertyData = $this->reflectionService->getClassSchema($query->getType())->getProperties();
		$sql['tables'][] = 'LEFT JOIN "properties" ON "_entity"."identifier" = "properties"."parent"';
		foreach ($query->getOrderings() as $propertyName => $order) {
			if (!isset($propertyData[$propertyName])) {
				throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('Unknown property "' . $propertyName . '" in query orderings.', 1284661371);
			}
			$sql['tables'][] = 'LEFT JOIN (SELECT "parent", "' . $this->getTypeName($propertyData[$propertyName]['elementType'] ?: $propertyData[$propertyName]['type']) . '" AS "' . $propertyName . '" FROM "properties_data" WHERE "name" = ' . $this->databaseHandle->quote($propertyName) . ') AS "_orderingtable' . count($orderings) . '" ON "_orderingtable' . count($orderings) . '"."parent" = "properties"."parent"';
			$orderings[] = '"_orderingtable' . count($orderings) . '"."' . $propertyName . '" ' . $order;
		}
		$sql['orderings'] = implode(', ', $orderings);
	}

	/**
	 * Transforms a constraint into SQL and parameter arrays
	 *
	 * @param \F3\FLOW3\Persistence\Qom\Constraint $constraint
	 * @param array &$sql
	 * @param array &$parameters
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseConstraint(\F3\FLOW3\Persistence\Qom\Constraint $constraint, array &$sql, array &$parameters) {
		if ($constraint instanceof \F3\FLOW3\Persistence\Qom\LogicalAnd) {
			$sql['where']['values'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $sql, $parameters);
			$sql['where']['values'][] = ' AND ';
			$this->parseConstraint($constraint->getConstraint2(), $sql, $parameters);
			$sql['where']['values'][] = ') ';
		} elseif ($constraint instanceof \F3\FLOW3\Persistence\Qom\LogicalOr) {
			$sql['where']['values'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $sql, $parameters);
			$sql['where']['values'][] = ' OR ';
			$this->parseConstraint($constraint->getConstraint2(), $sql, $parameters);
			$sql['where']['values'][] = ') ';
		} elseif ($constraint instanceof \F3\FLOW3\Persistence\Qom\LogicalNot) {
			$sql['where']['values'][] = '(NOT ';
			$this->parseConstraint($constraint->getConstraint(), $sql, $parameters);
			$sql['where']['values'][] = ') ';
		} elseif ($constraint instanceof \F3\FLOW3\Persistence\Qom\Comparison) {
			$this->parseComparison($constraint, $sql, $parameters);
		}
	}

	/**
	 * Parse a Comparison into SQL and parameter arrays.
	 *
	 * @param \F3\FLOW3\Persistence\Qom\Comparison $comparison The comparison to parse
	 * @param array &$sql SQL query parts to add to
	 * @param array &$parameters Parameters to bind to the SQL
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseComparison(\F3\FLOW3\Persistence\Qom\Comparison $comparison, array &$sql, array &$parameters) {
		switch ($comparison->getOperator()) {
			case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_IN:
				$this->parseDynamicOperand($comparison->getOperand1(), $comparison->getOperator(), $sql, $parameters, NULL, $comparison->getOperand2());
				foreach ($comparison->getOperand2() as $value) {
					$parameters['values'][] = $this->getPlainValue($value);
				}
			break;
			case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_IS_EMPTY:
			case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_IS_NULL:
				$this->parseDynamicOperand($comparison->getOperand1(), $comparison->getOperator(), $sql, $parameters);
			break;
			default:
				$this->parseDynamicOperand($comparison->getOperand1(), $comparison->getOperator(), $sql, $parameters);
				$parameters['values'][] = $this->getPlainValue($comparison->getOperand2());
			break;
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
			return $this->processDateTime($input);
		} elseif (is_object($input) && $this->getIdentifierByObject($input) !== NULL) {
			return $this->getIdentifierByObject($input);
		} else {
			return $input;
		}
	}

	/**
	 * Parse a DynamicOperand into SQL and parameter arrays.
	 *
	 * @param \F3\FLOW3\Persistence\Qom\DynamicOperand $operand
	 * @param string $operator One of the JCR_OPERATOR_* constants
	 * @param array &$sql
	 * @param array &$parameters
	 * @param string $valueFunction an optional SQL function to apply to the operand value
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseDynamicOperand(\F3\FLOW3\Persistence\Qom\DynamicOperand $operand, $operator, array &$sql, array &$parameters, $valueFunction = NULL, $operand2 = NULL) {
		if ($operand instanceof \F3\FLOW3\Persistence\Qom\LowerCase) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $sql, $parameters, 'LOWER');
		} elseif ($operand instanceof \F3\FLOW3\Persistence\Qom\UpperCase) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $sql, $parameters, 'UPPER');
		} elseif ($operand instanceof \F3\FLOW3\Persistence\Qom\PropertyValue) {
			$selectorName = $operand->getSelectorName();
			$coalesce = 'COALESCE("' . $selectorName . 'pd' . count($parameters['fields']) . '"."string", CAST("' . $selectorName . 'pd' . count($parameters['fields']) . '"."integer" AS CHAR), CAST("' . $selectorName . 'pd' . count($parameters['fields']) . '"."float" AS CHAR), CAST("' . $selectorName . 'pd' . count($parameters['fields']) . '"."datetime" AS CHAR), "' . $selectorName . 'pd' . count($parameters['fields']) . '"."boolean", "' . $selectorName . 'pd' . count($parameters['fields']) . '"."object")';
			switch ($operator) {
				case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_IN:
					if ($valueFunction === NULL) {
						$valueWhere = $coalesce . ' IN (';
					} else {
						$valueWhere = $valueFunction . '(' . $coalesce . ') IN (';
					}
					$valueWhere .= implode(', ', array_fill(0, count($operand2), '?')) . ') ';
				break;
				case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_IS_EMPTY:
					$valueWhere = '("' . $selectorName . 'pd' . count($parameters['fields']) . '"."type" = \'NULL\' OR "' . $selectorName . 'pd' . count($parameters['fields']) . '"."type" IS NULL)';
				break;
				case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_IS_NULL:
					$valueWhere = '("' . $selectorName . 'pd' . count($parameters['fields']) . '"."type" = \'NULL\' AND "' . $selectorName . 'pd' . count($parameters['fields']) . '"."type" IS NOT NULL)';
				break;
				case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_CONTAINS:
						// in our data structure we can do this using equality...
					$operator = \F3\FLOW3\Persistence\QueryInterface::OPERATOR_EQUAL_TO;
				default:
					if ($valueFunction === NULL) {
						$valueWhere = $coalesce . ' ' . $this->resolveOperator($operator) . ' ?';
					} else {
						$valueWhere = $valueFunction . '(' . $coalesce . ') ' . $this->resolveOperator($operator) . ' ?';
					}
			}

			$sql['where']['fields'][] = '"' . $selectorName . 'p' . count($parameters['fields']) . '"."name" = ?';
			$sql['where']['values'][] = $valueWhere;
			$sql['tables'][] = 'LEFT JOIN "properties" AS "' . $selectorName . 'p' . count($parameters['fields']) . '" ON "' . $selectorName . '"."identifier" = "' . $selectorName . 'p' . count($parameters['fields']) . '"."parent" LEFT JOIN "properties_data" AS "' . $selectorName . 'pd' . count($parameters['fields']) . '" ON "' . $selectorName . 'p' . count($parameters['fields']) . '"."parent" = "' . $selectorName . 'pd' . count($parameters['fields']) . '"."parent" AND "' . $selectorName . 'p' . count($parameters['fields']) . '"."name" = "' . $selectorName . 'pd' . count($parameters['fields']) . '"."name"';
			$parameters['fields'][] = $operand->getPropertyName();
		}
	}

}

?>