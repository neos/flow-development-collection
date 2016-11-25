<?php
namespace Neos\Utility;

/*
 * This file is part of the Neos.Utility.Pdo package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A helper class for handling PDO databases
 *
 */
abstract class PdoHelper
{
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
    public static function importSql(\PDO $databaseHandle, $pdoDriver, $pathAndFilename)
    {
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
