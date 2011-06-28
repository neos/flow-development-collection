<?php
namespace TYPO3\FLOW3\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
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
 * A helper class for handling PDO databases
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
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

?>