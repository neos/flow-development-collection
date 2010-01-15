<?php
declare(ENCODING = 'utf-8');

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

$configuration = array(
	'sourceDatabase' => array(
		'DSN' => 'sqlite:' . __DIR__ . '/../../../../Data/Persistent/TYPO3CR.db',
		'username' => NULL,
		'password' => NULL
	),
	'targetDatabase' => array(
		'DSN' => 'sqlite:' . __DIR__ . '/../../../../Data/Persistent/Objects.db',
		'username' => NULL,
		'password' => NULL
	)
);

require(__DIR__ . '/../Classes/Utility/PdoHelper.php');

/**
 * Migrates data from the TYPO3CR-based persistence into the "native" persistence
 * format.
 *
 * Intended to be used when switching to FLOW3 1.0.0 alpha 7 from earlier
 * versions.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class Migrator {

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var \PDO
	 */
	protected $databaseHandle;

	/**
	 *
	 * @param array $configuration
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function run(array $configuration) {
		$this->configuration = $configuration;

		if ($GLOBALS['argc'] === 2) {
			switch ($GLOBALS['argv'][1]) {
				case 'export':
					$this->export(STDOUT);
				break;
				case 'import':
					$this->import(STDIN);
			}
		} else {
			$temporaryFile = tmpfile();
			$this->export($temporaryFile);
			rewind($temporaryFile);
			$this->import($temporaryFile);
		}

	}

	/**
	 *
	 * @param resource $targetFile
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function export($targetFile) {
		$exportData = array();
		$this->connect($this->configuration['sourceDatabase']['DSN'], $this->configuration['sourceDatabase']['username'], $this->configuration['sourceDatabase']['password']);

		$statementHandle = $this->databaseHandle->prepare('SELECT "identifier" FROM "nodes" WHERE "parent"=(SELECT "identifier" FROM "nodes" WHERE "name"=? AND "namespace"=?)');
		$statementHandle->execute(array('objects', 'http://forge.typo3.org/namespaces/flow3'));
		$rootLevelNodeIdentifiers = $statementHandle->fetchAll(\PDO::FETCH_COLUMN);

		foreach ($rootLevelNodeIdentifiers as $identifier) {
			$exportData[$identifier] = $this->processNodeForExport($identifier, $exportData);
		}

		fwrite($targetFile, json_encode($exportData));
		unset($this->databaseHandle);
	}

	/**
	 *
	 * @param resource $sourceFile
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function import($sourceFile) {
		$input = '';
		while ($line = fgets($sourceFile)) $input .= $line;
		$importData = json_decode($input, TRUE);

		$this->connect($this->configuration['targetDatabase']['DSN'], $this->configuration['targetDatabase']['username'], $this->configuration['targetDatabase']['password']);
		foreach ($importData as $identifier => $objectData) {
			$this->writeObjectToDatabase($identifier, $objectData);
		}
		unset($this->databaseHandle);
	}

	/**
	 *
	 * @param string $identifier
	 * @param array $objectData
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function writeObjectToDatabase($identifier, array $objectData) {
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO "entities" ("identifier", "type") VALUES (?, ?)');
		$statementHandle->execute(array(
			$identifier,
			$objectData['type']
		));

		$insertPropertyStatementHandle = $this->databaseHandle->prepare('INSERT INTO "properties" ("parent", "name", "multivalue", "type") VALUES (?, ?, ?, ?)');
		foreach ($objectData['properties'] as $propertyName => $propertyData) {
			$insertPropertyStatementHandle->execute(array(
				$identifier,
				$propertyName,
				(integer)$propertyData['multivalue'],
				$propertyData['type']
			));

			foreach ($propertyData['value'] as $index => $valueData) {
				$statementHandle = $this->databaseHandle->prepare('INSERT INTO "properties_data" ("parent", "name", "index", "type", "' . $valueData['type'] . '") VALUES (?, ?, ?, ?, ?)');
				$statementHandle->execute(array(
					$identifier,
					$propertyName,
					$index,
					$valueData['type'],
					$valueData['value']
				));
			}
		}
	}

	/**
	 * Connect to the database
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function connect($dsn, $username, $password) {
		$splitdsn = explode(':', $dsn, 2);
		$pdoDriver = $splitdsn[0];

		if ($pdoDriver === 'sqlite' && !file_exists($splitdsn[1])) {
			$this->createTables($dsn, $username, $password);
		}

		$this->databaseHandle = new \PDO($dsn, $username, $password);
		$this->databaseHandle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		if ($pdoDriver === 'mysql') {
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
	protected function createTables($dsn, $username, $password) {
		try {
			$pdoHelper = new \F3\FLOW3\Utility\PdoHelper($dsn, $username, $password);
			$pdoHelper->importSql(__DIR__ . '/../Resources/Private/Persistence/SQL/DDL.sql');
		} catch (\PDOException $e) {
			throw new \RuntimeException('Could not create persistence tables with DSN "' . $dsn . '". PDO error: ' . $e->getMessage());
		}
	}

	/**
	 *
	 * @param string $identifier 
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processNodeForExport($identifier, &$exportData) {
		$objectData = array();

		$nodeData = $this->getNodeData($identifier);
		$objectData['type'] = str_replace('_', '\\', $nodeData['nodetype']);
		$objectData['properties'] = $this->processRawPropertiesForExport($nodeData['properties']);
		$this->processSubNodesForExport($nodeData['subNodeIdentifiers'], $objectData['properties'], $exportData);

		return $objectData;
	}

	/**
	 *
	 * @param array $rawProperties
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processRawPropertiesForExport(array $rawProperties) {
		$properties = array();

		foreach ($rawProperties as $rawProperty) {
			$properties[$rawProperty['name']] = array(
				'multivalue' => $rawProperty['multivalue'],
				'type' => \PropertyType::typeFromType($rawProperty['type']),
				'value' => array(array(
					'type' => strtolower(\PropertyType::typeFromType($rawProperty['type'])),
					'value' => $rawProperty['value']
				))
			);
		}

		return $properties;
	}

	/**
	 *
	 * @param array $subNodeIdentifiers
	 * @param array $properties
	 * @param array $exportData
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processSubNodesForExport(array $subNodeIdentifiers, array &$properties, array &$exportData) {
		foreach ($subNodeIdentifiers as $identifier) {
			$nodeData = $this->getNodeData($identifier);
			switch ($nodeData['nodetype']) {
				case 'objectProxy':
					$properties[$nodeData['name']] = array(
						'multivalue' => FALSE,
						'type' => 'object',
						'value' => array(array(
							'type' => 'object',
							'value' => $nodeData['properties'][0]['value']
						))
					);
				break;
				case 'arrayProxy':
					$properties[$nodeData['name']] = array(
						'multivalue' => TRUE,
						'type' => 'array',
						'value' => $this->processArrayProxyForExport($nodeData, $exportData)
					);
				break;
				case 'splObjectStorageProxy':
					$properties[$nodeData['name']] = array(
						'multivalue' => TRUE,
						'type' => 'SplObjectStorage',
						'value' => $this->processSplObjectStorageProxyForExport($nodeData, $exportData)
					);
				break;
				default:
					$exportData[$identifier] = $this->processNodeForExport($identifier, $exportData);
					$properties[$nodeData['name']] = array(
						'multivalue' => FALSE,
						'type' => 'object',
						'value' => array(array(
							'type' => 'object',
							'value' => $identifier
						))
					);
				break;
			}
		}
	}

	/**
	 *
	 * @param array $itemNodeIdentifiers
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processArrayProxyForExport(array $arrayProxyData, &$exportData) {
		$items = array();

		foreach ($arrayProxyData['subNodeIdentifiers'] as $identifier) {
			$nodeData = $this->getNodeData($identifier);
			switch ($nodeData['nodetype']) {
				case 'objectProxy':
					$items[$nodeData['name']] = array(
						'type' => 'object',
						'value' => $nodeData['properties'][0]['value']
					);
				break;
				case 'arrayProxy':
					throw new \RuntimeException('Sorry, your data cannot be migrated. Nested arrays are not (yet) supported.');
				break;
				case 'splObjectStorageProxy':
					throw new \RuntimeException('Sorry, your data cannot be migrated. SplObjectStorage instances in arrays are not (yet) supported.');
				break;
				default:
					$exportData[$identifier] = $this->processNodeForExport($identifier, $exportData);
					$items[$nodeData['name']] = array(
						'type' => 'object',
						'value' => $identifier
					);
				break;
			}
		}

		foreach ($arrayProxyData['properties'] as $property) {
			$items[$property['name']] = array(
				'type' => strtolower(\PropertyType::typeFromType($property['type'])),
				'value' => $property['value']
			);
		}

		return $items;
	}

	/**
	 *
	 * @param array $itemNodeIdentifiers
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processSplObjectStorageProxyForExport(array $proxyNodeData, &$exportData) {
		$objects = array();

		foreach ($proxyNodeData['subNodeIdentifiers'] as $identifier) {
			$nodeData = $this->getNodeData($identifier);
			$nodeData = $this->getNodeData($nodeData['subNodeIdentifiers'][0]);
			switch ($nodeData['nodetype']) {
				case 'objectProxy':
					$objects[] = array(
						'type' => 'object',
						'value' => $nodeData['properties'][0]['value']
					);
				break;
				case 'splObjectStorageProxy':
					throw new \RuntimeException('Sorry, your data cannot be migrated. Nested SplObjectStorage instances are not (yet) supported.');
				break;
				default:
					$exportData[$nodeData['identifier']] = $this->processNodeForExport($nodeData['identifier'], $exportData);
					$objects[] = array(
						'type' => 'object',
						'value' => $nodeData['identifier']
					);
				break;
			}
		}

		return $objects;
	}

	/*                                                                        *
	 * FLOW3 default backend write methods                                    *
	 *                                                                        */



	/*                                                                        *
	 * TYPO3CR read methods                                                   *
	 *                                                                        */

	/**
	 * Returns raw data for a single node.
	 *
	 * @param string $identifier
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getNodeData($identifier) {
		$nodeData = $this->getRawNodeByIdentifier($identifier);
		$nodeData['properties'] = $this->getRawPropertiesOfNode($identifier);
		$nodeData['subNodeIdentifiers']  = $this->getIdentifiersOfSubNodesOfNode($identifier);

		return $nodeData;
	}

	/**
	 * Converts the given string into the given type
	 *
	 * @param integer $type one of the constants defined in PropertyType
	 * @param string $string a string representing a value of the given type
	 *
	 * @return string|int|float|DateTime|boolean
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function convertFromString($type, $string) {
		switch ($type) {
			case \PropertyType::LONG:
				return (int) $string;
			case \PropertyType::DOUBLE:
			case \PropertyType::DECIMAL:
				return (float) $string;
			case \PropertyType::DATE:
				$datetime = new \DateTime($string);
				return $datetime->getTimeStamp();
			case \PropertyType::BOOLEAN:
				return (boolean) $string;
			default:
				return $string;
		}
	}

	/**
	 * Fetches raw node data from the database
	 *
	 * @param string $identifier The Identifier of the node to fetch
	 * @return array|FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getRawNodeByIdentifier($identifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT "parent", "name", "namespace", "identifier", "nodetype" FROM "nodes" WHERE "identifier" = ?');
		$statementHandle->execute(array($identifier));
		$result = $statementHandle->fetch(\PDO::FETCH_ASSOC);
		if (is_array($result)) {
			return $result;
		}
		return FALSE;
	}

	/**
	 * Fetches sub node Identifiers from the database
	 *
	 * @param string $identifier The node Identifier to fetch (sub-)nodes for
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getIdentifiersOfSubNodesOfNode($identifier) {
		$nodeIdentifiers = array();
		$statementHandle = $this->databaseHandle->prepare('SELECT "identifier" FROM "nodes" WHERE "parent" = ?');
		$statementHandle->execute(array($identifier));
		$rawNodes = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		foreach ($rawNodes as $rawNode) {
			$nodeIdentifiers[] = $rawNode['identifier'];
		}
		return $nodeIdentifiers;
	}

	/**
	 * Fetches raw property data from the database
	 *
	 * @param string $identifier The node Identifier to fetch properties for
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function getRawPropertiesOfNode($identifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT "parent", "name", "multivalue", "type" FROM "properties" WHERE "parent" = ?');
		$statementHandle->execute(array($identifier));
		$properties = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		return $this->getRawPropertyValues($properties);
	}

	/**
	 * Fetches raw properties with the given type and value from the database
	 *
	 * @param string $name name of the reference properties considered, if NULL properties of any name will be returned
	 * @param integer $type one of the types defined in PropertyType (does not work for path or name right now as those are represented by more than the value column in their respective tables)
	 * @param mixed $value a value of the given type
	 * @return array
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getRawPropertiesOfTypedValue($name, $type, $value) {
		$typeName = strtolower(\PropertyType::nameFromValue($type));

		if ($name == NULL) {
			$statementHandle = $this->databaseHandle->prepare('SELECT "properties"."parent", "properties"."name", "properties"."multivalue", "properties"."type" FROM (SELECT DISTINCT "parent", "name", "value" FROM "' . $typeName . 'properties") AS "pv" JOIN "properties" ON "pv"."parent" = "properties"."parent" AND "pv"."name" = "properties"."name" WHERE "value" = ? ORDER BY "properties"."parent", "properties"."name"');
			$statementHandle->execute(array($value));
		} else {
			$statementHandle = $this->databaseHandle->prepare('SELECT "properties"."parent", "properties"."name", "properties"."multivalue", "properties"."type" FROM (SELECT DISTINCT "parent", "name", "value" FROM "' . $typeName . 'properties") AS "pv" JOIN "properties" ON "pv"."parent" = "properties"."parent" AND "pv"."name" = "properties"."name" WHERE "properties"."name" = ? AND "value" = ? ORDER BY "properties"."parent", "properties"."name"');
			$statementHandle->execute(array($name, $value));
		}

		$properties = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		return $this->getRawPropertyValues($properties);
	}

	/**
	 * Fetches raw property values for the given properties from the typed tables in the database
	 *
	 * @param array $properties from the "properties" table (at least columns 'parent', 'name', 'namespace', 'type' and 'multivalue')
	 * @return array
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getRawPropertyValues($properties) {
		if (is_array($properties)) {
			foreach ($properties as &$property) {
				$property['multivalue'] = (boolean)$property['multivalue'];

				if (! $property['multivalue']) {
					$this->getRawSingleValuedProperty($property);
				} else {
					$this->getRawMultiValuedProperty($property);
				}
			}
		}
		return $properties;
	}

	/**
	 * Fetches raw single valued property not of type \PropertyType::PATH
	 *
	 * @param array &$property The property as read from the "properties" table of the database with $property['type'] != \PropertyType::PATH and $property['multivalue'] == FALSE
	 * @return void
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getRawSingleValuedProperty(&$property) {
		$typeName = strtolower(\PropertyType::nameFromValue($property['type']));

		$statementHandle = $this->databaseHandle->prepare('SELECT "value" FROM "' . $typeName . 'properties" WHERE "parent" = ? AND "name" = ?');
		$statementHandle->execute(array($property['parent'], $property['name']));
		$values = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		foreach ($values as $value) {
			$property['value'] = $this->convertFromString($property['type'], $value['value']);
		}
	}

	/**
	 * Fetches raw multi valued property not of type \PropertyType::PATH
	 *
	 * @param array &$property The property as read from the "properties" table of the database with $property['type'] != \PropertyType::PATH and $property['multivalue'] == TRUE
	 * @return void
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getRawMultiValuedProperty(&$property) {
		$typeName = strtolower(\PropertyType::nameFromValue($property['type']));

		$statementHandle = $this->databaseHandle->prepare('SELECT "index", "value" FROM "' . $typeName . 'properties" WHERE "parent" = ? AND "name" = ?');
		$statementHandle->execute(array($property['parent'], $property['name']));
		$multivalues = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		if (is_array($multivalues)) {
			$resultArray = array();
			foreach ($multivalues as $multivalue) {
				$resultArray[$multivalue['index']] = $this->convertFromString($property['type'], $multivalue['value']);
			}
			$property['value'] = $resultArray;
		}
	}

}


/**
 * JCR property type handling.
 */
final class PropertyType {

	/**
	 * This constant can be used within a property definition to specify that
	 * the property in question may be of any type.
	 * However, it cannot be the actual type of any property instance. For
	 * example, it will never be returned by Property#getType and it cannot be
	 * assigned as the type when creating a new property.
	 */
	const UNDEFINED = 0;

	/**
	 * The STRING property type is used to store strings.
	 */
	const STRING = 1;

	/**
	 * BINARY properties are used to store binary data.
	 */
	const BINARY = 2;

	/**
	 * The LONG property type is used to store integers.
	 */
	const LONG = 3;

	/**
	 * The DOUBLE property type is used to store floating point numbers.
	 */
	const DOUBLE = 4;

	/**
	 * The DATE property type is used to store time and date information.
	 */
	const DATE = 5;

	/**
	 * The BOOLEAN property type is used to store boolean values.
	 */
	const BOOLEAN = 6;

	/**
	 * A NAME is a pairing of a namespace and a local name. When read, the
	 * namespace is mapped to the current prefix.
	 */
	const NAME = 7;

	/**
	 * A PATH property is an ordered list of path elements. A path element is a
	 * NAME with an optional index. When read, the NAMEs within the path are
	 * mapped to their current prefix. A path may be absolute or relative.
	 */
	const PATH = 8;

	/**
	 * A REFERENCE property stores the identifier of a referenceable node (one
	 * having type mix:referenceable), which must exist within the same
	 * workspace or session as the REFERENCE property. A REFERENCE property
	 * enforces this referential integrity by preventing the removal of its
	 * target node.
	 */
	const REFERENCE = 9;

	/**
	 * A WEAKREFERENCE property stores the identifier of a referenceable node
	 * (one having type mix:referenceable). A WEAKREFERENCE property does not
	 * enforce referential integrity.
	 */
	const WEAKREFERENCE = 10;

	/**
	 * A URI property is identical to STRING property except that it only
	 * accepts values that conform to the syntax of a URI-reference as defined
	 * in RFC 3986.
	 */
	const URI = 11;

	/**
	 * The DECIMAL property type is used to store precise decimal numbers.
	 */
	const DECIMAL = 12;

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_UNDEFINED = 'undefined';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_STRING = 'String';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_BINARY = 'Binary';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_LONG = 'Long';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_DOUBLE = 'Double';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_DATE = 'Date';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_BOOLEAN = 'Boolean';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_NAME = 'Name';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_PATH = 'Path';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_REFERENCE = 'Reference';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_WEAKREFERENCE = 'WeakReference';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_URI= 'URI';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_DECIMAL = 'Decimal';

	/**
	 * Make instantiation impossible...
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	private function __construct() {}

	/**
	 * Returns the name of the specified type, as used in serialization.
	 *
	 * @param integer $type type the property type
	 * @return string  name of the specified type
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function nameFromValue($type) {
		switch (intval($type)) {
			case self::UNDEFINED :
				return self::TYPENAME_UNDEFINED;
				break;
			case self::STRING :
				return self::TYPENAME_STRING;
				break;
			case self::BINARY :
				return self::TYPENAME_BINARY;
				break;
			case self::BOOLEAN :
				return self::TYPENAME_BOOLEAN;
				break;
			case self::LONG :
				return self::TYPENAME_LONG;
				break;
			case self::DOUBLE :
				return self::TYPENAME_DOUBLE;
				break;
			case self::DECIMAL :
				return self::TYPENAME_DECIMAL;
				break;
			case self::DATE :
				return self::TYPENAME_DATE;
				break;
			case self::NAME :
				return self::TYPENAME_NAME;
				break;
			case self::PATH :
				return self::TYPENAME_PATH;
				break;
			case self::REFERENCE :
				return self::TYPENAME_REFERENCE;
				break;
			case self::WEAKREFERENCE :
				return self::TYPENAME_WEAKREFERENCE;
				break;
			case self::URI :
				return self::TYPENAME_URI;
				break;
			default:
				throw new \InvalidArgumentException('Unknown type (' . $type . ') given.');
		}
	}

	/**
	 * Returns the numeric constant value of the type for the given PHP type
	 * name as returned by gettype().
	 *
	 * Note: this is an addition not defined in JSR-283.
	 *
	 * @param string $type
	 * @return integer
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function typeFromType($type) {
		switch ($type) {
			case self::STRING:
				return 'string';
				break;
			case self::BOOLEAN:
				return 'boolean';
				break;
			case self::LONG:
				return 'integer';
				break;
			case self::DOUBLE:
				return 'float';
				break;
			case self::DATE:
				return 'DateTime';
				break;
		}
	}
}

$migrator = new Migrator();
$migrator->run($configuration);

?>
