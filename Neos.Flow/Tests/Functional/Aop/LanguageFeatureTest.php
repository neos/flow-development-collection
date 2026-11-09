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
use Neos\Flow\Aop\ProxyInterface as AopProxyInterface;
use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Flow\Tests\Functional\Aop\Fixtures\CountableAndLabelableFixture;
use Neos\Flow\Tests\Functional\Aop\Fixtures\LabeledFixtureEnum;
use Neos\Flow\Tests\Functional\Aop\Fixtures\LabeledFixtureThing;
use Neos\Flow\Tests\Functional\Aop\Fixtures\LanguageFeatureTestingAspect;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassForFirstClassCallables;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassUsingATrait;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithDnfTypes;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithGeneratorMethods;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithIntersectionTypes;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithToStringMethod;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for advices on methods which use more recent PHP language features
 */
class LanguageFeatureTest extends FunctionalTestCase
{
    #[Test]
    public function generatorMethodCanBeAdvisedByABeforeAdvice(): void
    {
        $targetClass = new TargetClassWithGeneratorMethods();

        $generator = $targetClass->generateGreetings('Flow');

        self::assertTrue($targetClass->beforeAdviceWasInvoked, 'Before advice should be invoked for generator method generateGreetings()');
        self::assertSame(['Hello, Flow', 'Hi, Flow', 'Hey, Flow'], iterator_to_array($generator));
    }

    /**
     * The around advice must pass the generator object through, so that the
     * caller can still iterate over it.
     */
    #[Test]
    public function generatorMethodCanBeAdvisedByAnAroundAdvice(): void
    {
        $targetClass = new TargetClassWithGeneratorMethods();

        # Note: In order to prove that the advice is actually executed, the advice
        #       LanguageFeatureTestingAspect::aroundGeneratorMethodAdvice() increases the upper bound by one.
        $generator = $targetClass->generateNumbers(3);

        self::assertTrue($targetClass->aroundAdviceWasInvoked, 'Around advice should be invoked for generator method generateNumbers()');
        self::assertInstanceOf(\Generator::class, $generator);
        self::assertSame([1, 2, 3, 4], iterator_to_array($generator));
    }

    #[Test]
    public function methodWithIntersectionTypedArgumentCanBeAdvised(): void
    {
        $targetClass = new TargetClassWithIntersectionTypes();

        self::assertSame('advised: fixture:1', $targetClass->describe(new CountableAndLabelableFixture('fixture')));
    }

    #[Test]
    public function methodWithIntersectionTypedArgumentAndReturnTypeCanBeAdvised(): void
    {
        $targetClass = new TargetClassWithIntersectionTypes();

        # Note: In order to prove that the advice is actually executed, the advice
        #       LanguageFeatureTestingAspect::intersectionTypePassThroughAdvice() replaces the given argument.
        $result = $targetClass->passThrough(new CountableAndLabelableFixture('original'));

        self::assertSame('advised', $result->label());
        self::assertSame(2, $result->countItems());
    }

    #[Test]
    public function intersectionTypesStayEnforceableInAdvisedMethods(): void
    {
        $targetClass = new TargetClassWithIntersectionTypes();

        $this->expectException(\TypeError::class);
        /** @noinspection PhpParamsInspection */
        $targetClass->describe(new \stdClass());
    }

    #[Test]
    public function methodWithDnfTypedArgumentCanBeAdvised(): void
    {
        $targetClass = new TargetClassWithDnfTypes();

        self::assertSame('advised: nothing', $targetClass->describe(null));
        self::assertSame('advised: fixture:1', $targetClass->describe(new CountableAndLabelableFixture('fixture')));
    }

    #[Test]
    public function methodWithDnfTypedArgumentAndReturnTypeCanBeAdvised(): void
    {
        $targetClass = new TargetClassWithDnfTypes();

        self::assertNull($targetClass->passThrough(null));

        # Note: In order to prove that the advice is actually executed, the advice
        #       LanguageFeatureTestingAspect::dnfTypePassThroughAdvice() replaces the given argument.
        $result = $targetClass->passThrough(new CountableAndLabelableFixture('original'));

        self::assertNotNull($result);
        self::assertSame('advised', $result->label());
        self::assertSame(2, $result->countItems());
    }

    /**
     * A pointcut expression whose class name pattern also matches an enum must
     * not break the compile run – enums simply cannot be proxied.
     */
    #[Test]
    public function pointcutExpressionMatchingEnumMethodsLeavesTheEnumUntouched(): void
    {
        LanguageFeatureTestingAspect::$numberOfLabelAdviceInvocations = [];

        self::assertSame('Aircraft', LabeledFixtureEnum::Aircraft->getLabel(), 'Enum methods must not be advised');
        self::assertSame('Spacecraft', LabeledFixtureEnum::from('spacecraft')->getLabel());
        self::assertNotInstanceOf(ProxyInterface::class, LabeledFixtureEnum::Aircraft);
        self::assertNotInstanceOf(AopProxyInterface::class, LabeledFixtureEnum::Aircraft);
        self::assertSame([], LanguageFeatureTestingAspect::$numberOfLabelAdviceInvocations);
    }

    #[Test]
    public function pointcutExpressionMatchingEnumMethodsStillAdvisesRegularClasses(): void
    {
        LanguageFeatureTestingAspect::$numberOfLabelAdviceInvocations = [];

        $targetClass = new LabeledFixtureThing();

        self::assertSame('advised Thing', $targetClass->getLabel());
        self::assertSame([LabeledFixtureThing::class => 1], LanguageFeatureTestingAspect::$numberOfLabelAdviceInvocations);
    }

    #[Test]
    public function adviceIsAlsoInvokedWhenTheMethodIsCalledThroughAFirstClassCallable(): void
    {
        $targetClass = new TargetClassForFirstClassCallables();

        $callable = $targetClass->shout(...);

        self::assertSame('HELLO!', $callable('hello'));
        self::assertSame(1, $targetClass->numberOfInvocations, 'The original method should have been invoked exactly once');
    }

    #[Test]
    public function magicToStringMethodCanBeAdvised(): void
    {
        $targetClass = new TargetClassWithToStringMethod();

        self::assertSame('advised plain', (string)$targetClass);
        self::assertSame('advised plain', "{$targetClass}");
    }

    #[Test]
    public function methodComingFromARegularTraitCanBeAdvised(): void
    {
        $targetClass = new TargetClassUsingATrait();

        self::assertSame('Hello, Flow, from the trait', $targetClass->greetFromTrait('Flow'));
    }
}
