<?php
declare(ENCODING = 'utf-8') ;

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
	'database' => array(
		'DSN' => 'sqlite:' . __DIR__ . '/../../../../Data/Persistent/Objects.db',
		'username' => NULL,
		'password' => NULL
	)
);

require_once(__DIR__ . '/../Classes/Utility/Algorithms.php');

/**
 * Migrates data in Resource objects to have a resource pointer.
 *
 * Intended to be used when switching to FLOW3 1.0.0 alpha 14 from earlier
 * versions.
 *
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
		$this->connect($this->configuration['database']['DSN'], $this->configuration['database']['username'], $this->configuration['database']['password']);
		$this->migrate();
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
			exit('No database found at "' . $splitdsn[1] . '"');
		}

		$this->databaseHandle = new \PDO($dsn, $username, $password);
		$this->databaseHandle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		if ($pdoDriver === 'mysql') {
			$this->databaseHandle->exec('SET SESSION sql_mode=\'ANSI\';');
		}
	}

	protected function migrate() {
		$result = $this->databaseHandle->query('SELECT * FROM "valueobjects" WHERE "type"=\'F3\FLOW3\Resource\Resource\'');
		while ($resourceRow = $result->fetch(PDO::FETCH_ASSOC)) {

			$row = $this->databaseHandle->query('SELECT * FROM "properties_data" WHERE "name"=\'hash\' AND "parent"=\'' . $resourceRow['identifier'] . '\'')->fetch(PDO::FETCH_ASSOC);
			$resourceHash = $row['string'];
			$row = $this->databaseHandle->query('SELECT * FROM "properties_data" WHERE "name"=\'fileExtension\' AND "parent"=\'' . $resourceRow['identifier'] . '\'')->fetch(PDO::FETCH_ASSOC);
			$fileExtension = $row['string'];

				// create ResourcePointer value object
			$newResourcePointerHash = sha1($resourceHash);
			$statement = $this->databaseHandle->prepare('INSERT INTO "valueobjects" ("identifier", "type") VALUES (?, ?)');
			$statement->execute(array($newResourcePointerHash, 'F3\FLOW3\Resource\ResourcePointer'));
				// create hash property for ResourcePointer
			$statement = $this->databaseHandle->prepare('INSERT INTO "properties" ("parent", "name", "multivalue", "type") VALUES (?, ?, ?, ?)');
			$statement->execute(array($newResourcePointerHash, 'hash', 0, 'string'));
			$statement = $this->databaseHandle->prepare('INSERT INTO "properties_data" ("parent", "name", "type", "string") VALUES (?, ?, ?, ?)');
			$statement->execute(array($newResourcePointerHash, 'hash', 'string', $resourceHash));

				// find parents for this resource
			$parentIdentifiers = $this->findParentIdentifiers($resourceRow);

			foreach ($parentIdentifiers as $parentIdentifier) {
					// create Resource entity
				$newResourceUuid = \F3\FLOW3\Utility\Algorithms::generateUUID();
				$statement = $this->databaseHandle->prepare('INSERT INTO "entities" ("identifier", "type", "parent") VALUES (?, ?, ?)');
				$statement->execute(array($newResourceUuid, 'F3\FLOW3\Resource\Resource', $parentIdentifier));
					// create fileExtension property for Resource
				$statement = $this->databaseHandle->prepare('INSERT INTO "properties" ("parent", "name", "multivalue", "type") VALUES (?, ?, ?, ?)');
				$statement->execute(array($newResourceUuid, 'fileExtension', 0, 'string'));
				$statement = $this->databaseHandle->prepare('INSERT INTO "properties_data" ("parent", "name", "type", "string") VALUES (?, ?, ?, ?)');
				$statement->execute(array($newResourceUuid, 'fileExtension', 'string', $fileExtension));
					// create "fake" fileName property for Resource
				$statement = $this->databaseHandle->prepare('INSERT INTO "properties" ("parent", "name", "multivalue", "type") VALUES (?, ?, ?, ?)');
				$statement->execute(array($newResourceUuid, 'filename', 0, 'string'));
				$statement = $this->databaseHandle->prepare('INSERT INTO "properties_data" ("parent", "name", "type", "string") VALUES (?, ?, ?, ?)');
				$statement->execute(array($newResourceUuid, 'filename', 'string', $resourceHash . '.' . $fileExtension));
					// create resourcePointer property for Resource
				$statement = $this->databaseHandle->prepare('INSERT INTO "properties" ("parent", "name", "multivalue", "type") VALUES (?, ?, ?, ?)');
				$statement->execute(array($newResourceUuid, 'resourcePointer', 0, 'string'));
				$statement = $this->databaseHandle->prepare('INSERT INTO "properties_data" ("parent", "name", "type", "object") VALUES (?, ?, ?, ?)');
				$statement->execute(array($newResourceUuid, 'resourcePointer', 'F3\FLOW3\Resource\ResourcePointer', $newResourcePointerHash));

					// connect new Resource to parent
				$statement = $this->databaseHandle->prepare('UPDATE "properties_data" SET "object"=? WHERE "object"=? AND "parent"=?');
				$statement->execute(array($newResourceUuid, $resourceRow['identifier'], $parentIdentifier));
			}

				// delete (old) Resource
			$statement = $this->databaseHandle->prepare('DELETE FROM "valueobjects" WHERE "identifier"=?');
			$statement->execute(array($resourceRow['identifier']));
				// delete properties on (old) Resource
			$statement = $this->databaseHandle->prepare('DELETE FROM "properties" WHERE "parent"=?');
			$statement->execute(array($resourceRow['identifier']));
			$statement = $this->databaseHandle->prepare('DELETE FROM "properties_data" WHERE "parent"=?');
			$statement->execute(array($resourceRow['identifier']));

		}
	}

	protected function findParentIdentifiers($resourceRow) {
		$statement = $this->databaseHandle->prepare('SELECT "parent" FROM "properties_data" WHERE "object"=?');
		$statement->execute(array($resourceRow['identifier']));
		return $statement->fetchAll(PDO::FETCH_COLUMN);
	}
}

$migrator = new Migrator();
$migrator->run($configuration);

?>
