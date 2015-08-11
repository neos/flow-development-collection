<?php
namespace TYPO3\Flow\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Mvc\Exception\StopActionException;
use TYPO3\Flow\Package;

/**
 * Command controller for tasks related to database handling
 *
 * @Flow\Scope("singleton")
 */
class DatabaseCommandController extends CommandController {

	/**
	 * @Flow\InjectConfiguration(path="persistence")
	 * @var array
	 */
	protected $persistenceSettings = array();

	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Create a Doctrine DBAL Connection with the configured settings.
	 *
	 * @return void
	 * @throws DBALException
	 */
	protected function initializeConnection() {
		$this->connection = DriverManager::getConnection($this->persistenceSettings['backendOptions']);
	}

	/**
	 * Convert the database schema to use the given character set and collation (defaults to utf8 and utf8_unicode_ci).
	 *
	 * This command can be used to convert the database configured in the Flow settings to the utf8 character
	 * set and the utf8_unicode_ci collation (by default, a custom collation can be given). It will only
	 * work when using the pdo_mysql driver.
	 *
	 * <b>Make a backup</b> before using it, to be on the safe side. If you want to inspect the statements used
	 * for conversion, you can use the $output parameter to write them into a file. This file can be used to do
	 * the conversion manually.
	 *
	 * For background information on this, see:
	 *
	 * - http://stackoverflow.com/questions/766809/
	 * - http://dev.mysql.com/doc/refman/5.5/en/alter-table.html
	 *
	 * The main purpose of this is to fix setups that were created with Flow 2.3.x or earlier and whose
	 * database server did not have a default collation of utf8_unicode_ci. In those cases, the tables will
	 * have a collation that does not match the default collation of later Flow versions, potentially leading
	 * to problems when creating foreign key constraints (among others, potentially).
	 *
	 * If you have special needs regarding the charset and collation, you <i>can</i> override the defaults with
	 * different ones. One thing this might be useful for is when switching to the utf8mb4 character set, see:
	 *
	 * - https://mathiasbynens.be/notes/mysql-utf8mb4
	 * - https://florian.ec/articles/mysql-doctrine-utf8/
	 *
	 * Note: This command <b>is not a general purpose conversion tool</b>. It will specifically not fix cases
	 * of actual utf8 stored in latin1 columns. For this a conversion to BLOB followed by a conversion to the
	 * proper type, charset and collation is needed instead.
	 *
	 * @param string $characterSet Character set, defaults to utf8
	 * @param string $collation Collation to use, defaults to utf8_unicode_ci
	 * @param string $output A file to write SQL to, instead of executing it
	 * @param boolean $verbose If set, the statements will be shown as they are executed
	 * @throws StopActionException
	 */
	public function setCharsetCommand($characterSet = 'utf8', $collation = 'utf8_unicode_ci', $output = NULL, $verbose = FALSE) {
		if (!in_array($this->persistenceSettings['backendOptions']['driver'], array('pdo_mysql', 'mysqli'))) {
			$this->outputLine('Database charset/collation fixing is only supported on MySQL.');
			$this->quit(1);
		}

		if ($this->persistenceSettings['backendOptions']['host'] === NULL) {
			$this->outputLine('Database charset/collation fixing has been SKIPPED, the host backend option is not set in the configuration.');
			$this->quit(1);
		}

		$this->initializeConnection();
		$this->convertToCharacterSetAndCollation($characterSet, $collation, $output, $verbose);

		if ($output === NULL) {
			$this->outputLine('Database charset/collation was converted.');
		} else {
			$this->outputLine('Wrote SQL for converting database charset/collation to file "' . $output . '".');
		}
	}

	/**
	 * Convert the tables in the current database to use given character set and collation.
	 *
	 * @param string $characterSet Character set to convert to
	 * @param string $collation Collation to set, must be compatible with the character set
	 * @param string $outputPathAndFilename
	 * @param boolean $verbose
	 * @throws ConnectionException
	 * @throws DBALException
	 */
	protected function convertToCharacterSetAndCollation($characterSet = 'utf8', $collation = 'utf8_unicode_ci', $outputPathAndFilename = NULL, $verbose = FALSE) {
		$statements = array('SET foreign_key_checks = 0');

		$statements[] = 'ALTER DATABASE ' . $this->connection->quoteIdentifier($this->persistenceSettings['backendOptions']['dbname']) . ' CHARACTER SET ' . $characterSet . ' COLLATE ' . $collation;

		$tableNames = $this->connection->getSchemaManager()->listTableNames();
		foreach ($tableNames as $tableName) {
			$statements[] = 'ALTER TABLE ' . $this->connection->quoteIdentifier($tableName) . ' DEFAULT CHARACTER SET ' . $characterSet . ' COLLATE ' . $collation;
			$statements[] = 'ALTER TABLE ' . $this->connection->quoteIdentifier($tableName) . ' CONVERT TO CHARACTER SET ' . $characterSet . ' COLLATE ' . $collation;
		}

		$statements[] = 'SET foreign_key_checks = 1';

		if ($outputPathAndFilename === NULL) {
			try {
				$this->connection->beginTransaction();
				foreach ($statements as $statement) {
					if ($verbose) {
						$this->outputLine($statement);
					}
					$this->connection->exec($statement);
				}
				$this->connection->commit();
			} catch (\Exception $exception) {
				$this->connection->rollBack();
				$this->outputLine($exception->getMessage());
				$this->outputLine('[ERROR] The transaction was rolled back.');
			}
		} else {
			file_put_contents($outputPathAndFilename, implode(';' . PHP_EOL, $statements) . ';');
		}
	}

}
