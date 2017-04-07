<?php
namespace Neos\Cache\Tests;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The mother of all test cases.
 *
 */
abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $backupGlobalsBlacklist = ['GLOBALS', 'bootstrap', '__PHPUNIT_BOOTSTRAP'];

    /**
     * Enable or disable the backup and restoration of static attributes.
     * @var boolean
     */
    protected $backupStaticAttributes = false;

    /**
     * Injects $dependency into property $name of $target
     *
     * This is a convenience method for setting a protected or private property in
     * a test subject for the purpose of injecting a dependency.
     *
     * @param object $target The instance which needs the dependency
     * @param string $name Name of the property to be injected
     * @param mixed $dependency The dependency to inject – usually an object but can also be any other type
     * @return void
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function inject($target, $name, $dependency)
    {
        if (!is_object($target)) {
            throw new \InvalidArgumentException('Wrong type for argument $target, must be object.');
        }

        $objectReflection = new \ReflectionObject($target);
        $methodNamePart = strtoupper($name[0]) . substr($name, 1);
        if ($objectReflection->hasMethod('set' . $methodNamePart)) {
            $methodName = 'set' . $methodNamePart;
            $target->$methodName($dependency);
        } elseif ($objectReflection->hasMethod('inject' . $methodNamePart)) {
            $methodName = 'inject' . $methodNamePart;
            $target->$methodName($dependency);
        } elseif ($objectReflection->hasProperty($name)) {
            $property = $objectReflection->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($target, $dependency);
        } else {
            throw new \RuntimeException('Could not inject ' . $name . ' into object of type ' . get_class($target));
        }
    }
}
