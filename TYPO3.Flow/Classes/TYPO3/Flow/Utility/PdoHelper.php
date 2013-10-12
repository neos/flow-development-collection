<?php
namespace TYPO3\Flow\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A helper class for handling PDO databases
 *
 */
class PdoHelper {

	/**
	 * Pumps the SQL into the database. Use for DDL only.
	 *
	 * Important: key definitions with length specifiers (needed for MySQL) must
	 * be given as "field"(xyz) - no space between double quote and parenthesis -
	 * so they can be removed automatically.
	 *
	 * @param \PDO $databaseHandle
	 * @param string $pdoDriver
	 * @param string $pathAndFilename
	 * @return void
	 */
	static public function importSql(\PDO $databaseHandle, $pdoDriver, $pathAndFilename) {
		$sql = file($pathAndFilename, FILE_IGNORE_NEW_LINES & FILE_SKIP_EMPTY_LINES);
			// Remove MySQL style key length delimiters (yuck!) if we are not setting up a MySQL db
		if ($pdoDriver !== 'mysql') {
			$sql = preg_replace('/"\([0-9]+\)/', '"', $sql);
		}

		$statement = '';
		foreach ($sql as $line) {
			$statement .= ' ' . trim($line);
			if (substr($statement, -1) === ';') {
				$databaseHandle->exec($statement);
				$statement = '';
			}
		}
	}
}
