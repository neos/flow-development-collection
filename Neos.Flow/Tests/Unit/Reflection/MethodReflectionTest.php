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

use Neos\Flow\Reflection;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for MethodReflection
 */
class MethodReflectionTest extends UnitTestCase
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
        $method = new Reflection\MethodReflection(self::class, __FUNCTION__);
        self::assertInstanceOf(Reflection\ClassReflection::class, $method->getDeclaringClass());
    }

    /**
     * @test
     */
    public function getParametersReturnsFlowsParameterReflection($dummyArg1 = null, $dummyArg2 = null)
    {
        $method = new Reflection\MethodReflection(self::class, __FUNCTION__);
        foreach ($method->getParameters() as $parameter) {
            self::assertInstanceOf(Reflection\ParameterReflection::class, $parameter);
            self::assertEquals(self::class, $parameter->getDeclaringClass()->getName());
        }
    }
}
