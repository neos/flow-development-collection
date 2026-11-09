<?php

declare(strict_types=1);

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
use Neos\Flow\Reflection\ClassReflection;
use Neos\Flow\Reflection\MethodReflection;
use Neos\Flow\Reflection\ParameterReflection;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for MethodReflection
 */
final class MethodReflectionTest extends UnitTestCase
{
    /**
     * @var mixed
     */
    protected $someProperty;

    #[Test]
    public function getDeclaringClassReturnsFlowsClassReflection()
    {
        $method = new MethodReflection(__CLASS__, __FUNCTION__);
        self::assertInstanceOf(ClassReflection::class, $method->getDeclaringClass());
    }

    #[Test]
    public function getParametersReturnsFlowsParameterReflection($dummyArg1 = null, $dummyArg2 = null)
    {
        $method = new MethodReflection(__CLASS__, __FUNCTION__);
        foreach ($method->getParameters() as $parameter) {
            self::assertInstanceOf(ParameterReflection::class, $parameter);
            self::assertEquals(__CLASS__, $parameter->getDeclaringClass()->getName());
        }
    }
}
