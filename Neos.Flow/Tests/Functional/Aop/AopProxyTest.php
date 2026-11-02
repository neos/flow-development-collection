<?php

declare(strict_types=1);

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
use Neos\Flow\Tests\Functional\Aop\Fixtures\ChildClassOfTargetClass01;
use Neos\Flow\Tests\Functional\Aop\Fixtures\EntityWithOptionalConstructorArguments;
use Neos\Flow\Tests\Functional\Aop\Fixtures\PrototypeClassGsubsub;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for aop proxy classes
 */
final class AopProxyTest extends FunctionalTestCase
{
    #[Test]
    public function advicesAreExecutedAgainIfAnOverriddenMethodCallsItsParentMethod(): void
    {
        $targetClass = new ChildClassOfTargetClass01();
        self::assertEquals('Greetings, I just wanted to say: Hello World World', $targetClass->sayHello());
    }

    #[Test]
    public function anAdvisedParentMethodIsCalledCorrectlyIfANonAdvisedOverridingMethodCallsIt(): void
    {
        $targetClass = new ChildClassOfTargetClass01();
        self::assertEquals('Two plus two makes five! For big twos and small fives! That was smart, eh?', $targetClass->saySomethingSmart());
    }

    #[Test]
    public function methodArgumentsWithValueNullArePassedToTheProxiedMethod(): void
    {
        $proxiedClass = new EntityWithOptionalConstructorArguments('argument1', null, 'argument3');

        self::assertEquals('argument1', $proxiedClass->argument1);
        self::assertNull($proxiedClass->argument2);
        self::assertEquals('argument3', $proxiedClass->argument3);
    }

    #[Test]
    public function staticMethodsCannotBeAdvised(): void
    {
        $targetClass01 = new TargetClass01();
        self::assertSame('I won\'t take any advice', $targetClass01->someStaticMethod());
    }

    #[Test]
    public function canCallAdvisedParentMethodNotDeclaredInChild(): void
    {
        $targetClass = new ChildClassOfTargetClass01();
        $greeting = $targetClass->greet('Flow');
        self::assertEquals('Hello, me', $greeting);
    }

    #[Test]
    public function cloneCanCallParentCloneMethod(): void
    {
        $entity = new PrototypeClassGsubsub();
        self::assertSame('real', $entity->realOrCloned);
        $clone = clone $entity;
        self::assertSame('cloned!', $clone->realOrCloned);
    }
}
