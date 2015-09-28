<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Fixture;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * PdoInterface so we can mock PDO using PHPUnit 3.4 - without the interface a
 * mock cannot be created because "You cannot serialize or unserialize PDO
 * instances"...
 *
 */
interface PdoInterface
{
    public function __construct($dsn, $username = null, $password = null, $driver_options = null);
    public function beginTransaction();
    public function commit();
    public function errorCode();
    public function errorInfo();
    public function exec($statement);
    public function getAttribute($attribute);
    public function getAvailableDrivers();
    public function lastInsertId($name = null);
    public function prepare($statement, $driver_options = array());
    public function query($statement);
    public function quote($string, $parameter_type = \PDO::PARAM_STR);
    public function rollBack();
    public function setAttribute($attribute, $value);
}
