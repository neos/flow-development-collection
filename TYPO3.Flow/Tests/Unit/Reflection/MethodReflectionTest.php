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
