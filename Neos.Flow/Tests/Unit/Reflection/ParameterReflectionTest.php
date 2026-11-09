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
use Neos\Flow\Reflection\ParameterReflection;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the ParameterReflection
 */
final class ParameterReflectionTest extends UnitTestCase
{
    #[Test]
    public function getDeclaringClassReturnsFlowsClassReflection($dummy = null)
    {
        $parameter = new ParameterReflection([__CLASS__, 'fixtureMethod'], 'arg2');
        self::assertInstanceOf(ClassReflection::class, $parameter->getDeclaringClass());
    }

    #[Test]
    public function getClassReturnsFlowsClassReflection($dummy = null)
    {
        $parameter = new ParameterReflection([__CLASS__, 'fixtureMethod'], 'arg1');
        self::assertInstanceOf(ClassReflection::class, $parameter->getClass());
    }

    /**
     * Just a fixture method
     */
    protected function fixtureMethod(\ArrayObject $arg1, $arg2 = null)
    {
    }
}
