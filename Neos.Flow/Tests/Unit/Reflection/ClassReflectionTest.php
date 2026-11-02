<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Reflection;

use Neos\Flow\Reflection\ClassReflection;
use Neos\Flow\Reflection\MethodReflection;
use Neos\Flow\Reflection\PropertyReflection;
use Neos\Flow\Tests\Reflection\Fixture\DummyInterface1;
use Neos\Flow\Tests\Reflection\Fixture\DummyInterface2;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once('Fixture/DummyInterface1.php');
require_once('Fixture/DummyInterface2.php');

/**
 * Testcase for ClassReflection
 */
final class ClassReflectionTest extends UnitTestCase implements DummyInterface1, DummyInterface2
{
    /**
     * @var mixed
     */
    protected $someProperty;

    /**
     * @var mixed
     */
    protected static $someStaticProperty = 'statix';

    #[Test]
    public function getPropertiesReturnsFlowsPropertyReflection()
    {
        $class = new ClassReflection(__CLASS__);
        $properties = $class->getProperties();

        self::assertTrue(is_array($properties), 'The returned value is no array.');
        self::assertInstanceOf(PropertyReflection::class, array_pop($properties), 'The returned properties are not of type \Neos\Flow\Reflection\PropertyReflection.');
    }

    #[Test]
    public function getPropertyReturnsFlowsPropertyReflection()
    {
        $class = new ClassReflection(__CLASS__);
        self::assertInstanceOf(PropertyReflection::class, $class->getProperty('someProperty'), 'The returned property is not of type \Neos\Flow\Reflection\PropertyReflection.');
        self::assertEquals('someProperty', $class->getProperty('someProperty')->getName(), 'The returned property seems not to be the right one.');
    }

    #[Test]
    public function getMethodsReturnsFlowsMethodReflection()
    {
        $class = new ClassReflection(__CLASS__);
        $methods = $class->getMethods();
        foreach ($methods as $method) {
            self::assertInstanceOf(MethodReflection::class, $method, 'The returned methods are not of type \Neos\Flow\Reflection\MethodReflection.');
        }
    }

    #[Test]
    public function getMethodsReturnsArrayWithNumericIndex()
    {
        $class = new ClassReflection(__CLASS__);
        $methods = $class->getMethods();
        foreach (array_keys($methods) as $key) {
            $this->assertIsInt($key, 'The index was not an integer.');
        }
    }

    #[Test]
    public function getMethodReturnsFlowsMethodReflection()
    {
        $class = new ClassReflection(__CLASS__);
        $method = $class->getMethod('getMethodReturnsFlowsMethodReflection');
        self::assertInstanceOf(MethodReflection::class, $method, 'The returned method is not of type \Neos\Flow\Reflection\MethodReflection.');
    }

    #[Test]
    public function getConstructorReturnsFlowsMethodReflection()
    {
        $class = new ClassReflection(__CLASS__);
        $constructor = $class->getConstructor();
        self::assertInstanceOf(MethodReflection::class, $constructor, 'The returned method is not of type \Neos\Flow\Reflection\MethodReflection.');
    }

    #[Test]
    public function getInterfacesReturnsFlowsClassReflection()
    {
        $class = new ClassReflection(__CLASS__);
        $interfaces = $class->getInterfaces();
        self::assertContainsOnlyInstancesOf(ClassReflection::class, $interfaces);
    }

    #[Test]
    public function getParentClassReturnsFlowsClassReflection()
    {
        $class = new ClassReflection(__CLASS__);
        $parentClass = $class->getParentClass();
        self::assertInstanceOf(ClassReflection::class, $parentClass);
    }
}
