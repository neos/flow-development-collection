<?php
namespace TYPO3\Flow\Tests\Unit\Reflection;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

require_once('Fixture/DummyInterface1.php');
require_once('Fixture/DummyInterface2.php');


/**
 * Testcase for ClassReflection
 *
 */
class ClassReflectionTest extends \TYPO3\Flow\Tests\UnitTestCase implements \TYPO3\Flow\Tests\Reflection\Fixture\DummyInterface1, \TYPO3\Flow\Tests\Reflection\Fixture\DummyInterface2
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
        $class = new \TYPO3\Flow\Reflection\ClassReflection(__CLASS__);
        $properties = $class->getProperties();

        $this->assertTrue(is_array($properties), 'The returned value is no array.');
        $this->assertInstanceOf(\TYPO3\Flow\Reflection\PropertyReflection::class, array_pop($properties), 'The returned properties are not of type \TYPO3\Flow\Reflection\PropertyReflection.');
    }

    /**
     * @test
     */
    public function getPropertyReturnsFlowsPropertyReflection()
    {
        $class = new \TYPO3\Flow\Reflection\ClassReflection(__CLASS__);
        $this->assertInstanceOf(\TYPO3\Flow\Reflection\PropertyReflection::class, $class->getProperty('someProperty'), 'The returned property is not of type \TYPO3\Flow\Reflection\PropertyReflection.');
        $this->assertEquals('someProperty', $class->getProperty('someProperty')->getName(), 'The returned property seems not to be the right one.');
    }

    /**
     * @test
     */
    public function getMethodsReturnsFlowsMethodReflection()
    {
        $class = new \TYPO3\Flow\Reflection\ClassReflection(__CLASS__);
        $methods = $class->getMethods();
        foreach ($methods as $method) {
            $this->assertInstanceOf(\TYPO3\Flow\Reflection\MethodReflection::class, $method, 'The returned methods are not of type \TYPO3\Flow\Reflection\MethodReflection.');
        }
    }

    /**
     * @test
     */
    public function getMethodsReturnsArrayWithNumericIndex()
    {
        $class = new \TYPO3\Flow\Reflection\ClassReflection(__CLASS__);
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
        $class = new \TYPO3\Flow\Reflection\ClassReflection(__CLASS__);
        $method = $class->getMethod('getMethodReturnsFlowsMethodReflection');
        $this->assertInstanceOf(\TYPO3\Flow\Reflection\MethodReflection::class, $method, 'The returned method is not of type \TYPO3\Flow\Reflection\MethodReflection.');
    }

    /**
     * @test
     */
    public function getConstructorReturnsFlowsMethodReflection()
    {
        $class = new \TYPO3\Flow\Reflection\ClassReflection(__CLASS__);
        $constructor = $class->getConstructor();
        $this->assertInstanceOf(\TYPO3\Flow\Reflection\MethodReflection::class, $constructor, 'The returned method is not of type \TYPO3\Flow\Reflection\MethodReflection.');
    }

    /**
     * @test
     */
    public function getInterfacesReturnsFlowsClassReflection()
    {
        $class = new \TYPO3\Flow\Reflection\ClassReflection(__CLASS__);
        $interfaces = $class->getInterfaces();
        foreach ($interfaces as $interface) {
            $this->assertInstanceOf(\TYPO3\Flow\Reflection\ClassReflection::class, $interface);
        }
    }

    /**
     * @test
     */
    public function getParentClassReturnsFlowsClassReflection()
    {
        $class = new \TYPO3\Flow\Reflection\ClassReflection(__CLASS__);
        $parentClass = $class->getParentClass();
        $this->assertInstanceOf(\TYPO3\Flow\Reflection\ClassReflection::class, $parentClass);
    }
}
