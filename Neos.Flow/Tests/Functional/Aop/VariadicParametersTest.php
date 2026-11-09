<?php

namespace Neos\Flow\Tests\Functional\Aop;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithVariadicParameters;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for advices on methods with variadic parameters
 */
class VariadicParametersTest extends FunctionalTestCase
{
    #[Test]
    public function argumentsOfAVariadicParameterArePassedOnToTheAdvisedMethod(): void
    {
        $targetClass = new TargetClassWithVariadicParameters();
        self::assertSame(6, $targetClass->sum(1, 2, 3));
    }

    #[Test]
    public function anEmptyVariadicParameterIsPassedOnToTheAdvisedMethod(): void
    {
        $targetClass = new TargetClassWithVariadicParameters();
        self::assertSame(0, $targetClass->sum());
    }

    #[Test]
    public function joinPointContainsTheArgumentsOfAVariadicParameterAsArray(): void
    {
        $targetClass = new TargetClassWithVariadicParameters();
        $targetClass->sum(1, 2, 3);

        self::assertSame(['numbers' => [1, 2, 3]], $targetClass->beforeAdviceArguments);
        self::assertSame(['numbers' => [1, 2, 3]], $targetClass->aroundAdviceArguments);
    }

    #[Test]
    public function argumentsOfARegularAndAVariadicParameterArePassedOnToTheAdvisedMethod(): void
    {
        $targetClass = new TargetClassWithVariadicParameters();
        self::assertSame('one, two, and more', $targetClass->concatenate(', ', 'one', 'two'));
    }

    #[Test]
    public function argumentsOfAnUntypedVariadicParameterArePassedOnToTheAdvisedMethod(): void
    {
        $targetClass = new TargetClassWithVariadicParameters();

        self::assertSame(3, $targetClass->countItems('one', 2, null));
        self::assertSame(['items' => ['one', 2, null]], $targetClass->beforeAdviceArguments);
        self::assertSame(0, $targetClass->countItems());
    }
}
