<?php
namespace Neos\Flow\Tests\Unit\Reflection;

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

use Neos\Flow\Reflection\ClassReflection;
use Neos\Flow\Reflection\MethodReflection;
use Neos\Flow\Reflection\PropertyReflection;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Tests\Reflection\Fixture;

/**
 * Testcase for ClassReflection
 */
class ClassReflectionTest extends UnitTestCase implements Fixture\DummyInterface1, Fixture\DummyInterface2
{
    /**
     * @var mixed
     */
    protected $someProperty;

    /**
     * @var mixed
     */
    protected static $someStaticProperty = 'statix';

    /**
     * @test
     */
    public function getPropertiesReturnsFlowsPropertyReflection()
    {
        $class = new ClassReflection(__CLASS__);
        $properties = $class->getProperties();

        $this->assertTrue(is_array($properties), 'The returned value is no array.');
        $this->assertInstanceOf(PropertyReflection::class, array_pop($properties), 'The returned properties are not of type \Neos\Flow\Reflection\PropertyReflection.');
    }

    /**
     * @test
     */
    public function getPropertyReturnsFlowsPropertyReflection()
    {
        $class = new ClassReflection(__CLASS__);
        $this->assertInstanceOf(PropertyReflection::class, $class->getProperty('someProperty'), 'The returned property is not of type \Neos\Flow\Reflection\PropertyReflection.');
        $this->assertEquals('someProperty', $class->getProperty('someProperty')->getName(), 'The returned property seems not to be the right one.');
    }

    /**
     * @test
     */
    public function getMethodsReturnsFlowsMethodReflection()
    {
        $class = new ClassReflection(__CLASS__);
        $methods = $class->getMethods();
        foreach ($methods as $method) {
            $this->assertInstanceOf(MethodReflection::class, $method, 'The returned methods are not of type \Neos\Flow\Reflection\MethodReflection.');
        }
    }

    /**
     * @test
     */
    public function getMethodsReturnsArrayWithNumericIndex()
    {
        $class = new ClassReflection(__CLASS__);
        $methods = $class->getMethods();
        foreach (array_keys($methods) as $key) {
            $this->assertInternalType('integer', $key, 'The index was not an integer.');
        }
    }

    /**
     * @test
     */
    public function getMethodReturnsFlowsMethodReflection()
    {
        $class = new ClassReflection(__CLASS__);
        $method = $class->getMethod('getMethodReturnsFlowsMethodReflection');
        $this->assertInstanceOf(MethodReflection::class, $method, 'The returned method is not of type \Neos\Flow\Reflection\MethodReflection.');
    }

    /**
     * @test
     */
    public function getConstructorReturnsFlowsMethodReflection()
    {
        $class = new ClassReflection(__CLASS__);
        $constructor = $class->getConstructor();
        $this->assertInstanceOf(MethodReflection::class, $constructor, 'The returned method is not of type \Neos\Flow\Reflection\MethodReflection.');
    }

    /**
     * @test
     */
    public function getInterfacesReturnsFlowsClassReflection()
    {
        $class = new ClassReflection(__CLASS__);
        $interfaces = $class->getInterfaces();
        foreach ($interfaces as $interface) {
            $this->assertInstanceOf(ClassReflection::class, $interface);
        }
    }

    /**
     * @test
     */
    public function getParentClassReturnsFlowsClassReflection()
    {
        $class = new ClassReflection(__CLASS__);
        $parentClass = $class->getParentClass();
        $this->assertInstanceOf(ClassReflection::class, $parentClass);
    }
}
