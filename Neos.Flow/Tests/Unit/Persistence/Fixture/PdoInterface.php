<?php
namespace Neos\Flow\Tests\Unit\Persistence\Fixture;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
    public function prepare($statement, $driver_options = []);
    public function query($statement);
    public function quote($string, $parameter_type = \PDO::PARAM_STR);
    public function rollBack();
    public function setAttribute($attribute, $value);
}
