<?php
namespace TYPO3\Flow\Tests\Unit\Reflection;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Reflection\ClassReflection;
use TYPO3\Flow\Reflection\ParameterReflection;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the ParameterReflection
 */
class ParameterReflectionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getDeclaringClassReturnsFlowsClassReflection($dummy = null)
    {
        $parameter = new ParameterReflection([__CLASS__, 'fixtureMethod'], 'arg2');
        $this->assertInstanceOf(ClassReflection::class, $parameter->getDeclaringClass());
    }

    /**
     * @test
     */
    public function getClassReturnsFlowsClassReflection($dummy = null)
    {
        $parameter = new ParameterReflection([__CLASS__, 'fixtureMethod'], 'arg1');
        $this->assertInstanceOf(ClassReflection::class, $parameter->getClass());
    }

    /**
     * Just a fixture method
     */
    protected function fixtureMethod(\ArrayObject $arg1, $arg2 = null)
    {
    }
}
