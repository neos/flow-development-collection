<?php
namespace TYPO3\Flow\Tests\Unit\Reflection;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


/**
 * Testcase for MethodReflection
 *
 */
class MethodReflectionTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var mixed
     */
    protected $someProperty;

    /**
     * @test
     */
    public function getDeclaringClassReturnsFlowsClassReflection()
    {
        $method = new \TYPO3\Flow\Reflection\MethodReflection(__CLASS__, __FUNCTION__);
        $this->assertInstanceOf('TYPO3\Flow\Reflection\ClassReflection', $method->getDeclaringClass());
    }

    /**
     * @test
     */
    public function getParametersReturnsFlowsParameterReflection($dummyArg1 = null, $dummyArg2 = null)
    {
        $method = new \TYPO3\Flow\Reflection\MethodReflection(__CLASS__, __FUNCTION__);
        foreach ($method->getParameters() as $parameter) {
            $this->assertInstanceOf('TYPO3\Flow\Reflection\ParameterReflection', $parameter);
            $this->assertEquals(__CLASS__, $parameter->getDeclaringClass()->getName());
        }
    }
}
