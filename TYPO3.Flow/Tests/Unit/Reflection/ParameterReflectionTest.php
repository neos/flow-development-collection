<?php
namespace TYPO3\Flow\Tests\Unit\Reflection;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the ParameterReflection
 *
 */
class ParameterReflectionTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getDeclaringClassReturnsFlowsClassReflection($dummy = null)
    {
        $parameter = new \TYPO3\Flow\Reflection\ParameterReflection(array(__CLASS__, 'fixtureMethod'), 'arg2');
        $this->assertInstanceOf('TYPO3\Flow\Reflection\ClassReflection', $parameter->getDeclaringClass());
    }

    /**
     * @test
     */
    public function getClassReturnsFlowsClassReflection($dummy = null)
    {
        $parameter = new \TYPO3\Flow\Reflection\ParameterReflection(array(__CLASS__, 'fixtureMethod'), 'arg1');
        $this->assertInstanceOf('TYPO3\Flow\Reflection\ClassReflection', $parameter->getClass());
    }

    /**
     * Just a fixture method
     */
    protected function fixtureMethod(\ArrayObject $arg1, $arg2 = null)
    {
    }
}
