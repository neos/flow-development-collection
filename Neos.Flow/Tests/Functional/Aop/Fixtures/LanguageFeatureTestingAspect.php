<?php

namespace Neos\Flow\Tests\Functional\Aop\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Generator;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * An aspect for testing advices on methods which use more recent PHP language
 * features: generators, intersection types, DNF types, first class callables,
 * magic methods and methods coming from a regular trait.
 */
#[Flow\Aspect]
class LanguageFeatureTestingAspect
{
    /**
     * Records the arguments the label advice was invoked with, indexed by the
     * class name of the proxy the advice was applied to.
     *
     * @var array<string, int>
     */
    public static array $numberOfLabelAdviceInvocations = [];

    /**
     * A before advice on a generator method: the advice must be invoked, the
     * generator itself must still be iterable.
     */
    #[Flow\Before('method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithGeneratorMethods->generateGreetings())')]
    public function beforeGeneratorMethodAdvice(JoinPointInterface $joinPoint): void
    {
        $proxy = $joinPoint->getProxy();
        assert($proxy instanceof TargetClassWithGeneratorMethods);
        $proxy->beforeAdviceWasInvoked = true;
    }

    /**
     * An around advice on a generator method: the advice must pass the generator
     * object through unchanged.
     */
    #[Flow\Around('method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithGeneratorMethods->generateNumbers())')]
    public function aroundGeneratorMethodAdvice(JoinPointInterface $joinPoint): Generator
    {
        $proxy = $joinPoint->getProxy();
        assert($proxy instanceof TargetClassWithGeneratorMethods);
        $proxy->aroundAdviceWasInvoked = true;
        $joinPoint->setMethodArgument('upperBound', $joinPoint->getMethodArgument('upperBound') + 1);
        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * An around advice on a method with a pure intersection type parameter and
     * return type.
     */
    #[Flow\Around('method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithIntersectionTypes->passThrough())')]
    public function intersectionTypePassThroughAdvice(JoinPointInterface $joinPoint): CountableFixtureInterface&LabelableFixtureInterface
    {
        $joinPoint->setMethodArgument('subject', new CountableAndLabelableFixture('advised', 2));
        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * An around advice on a method with a pure intersection type parameter
     */
    #[Flow\Around('method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithIntersectionTypes->describe())')]
    public function intersectionTypeDescribeAdvice(JoinPointInterface $joinPoint): string
    {
        return 'advised: ' . $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * An around advice on a method with a DNF typed parameter and return type
     */
    #[Flow\Around('method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithDnfTypes->passThrough())')]
    public function dnfTypePassThroughAdvice(JoinPointInterface $joinPoint): (CountableFixtureInterface&LabelableFixtureInterface)|null
    {
        $subject = $joinPoint->getMethodArgument('subject');
        if ($subject !== null) {
            $joinPoint->setMethodArgument('subject', new CountableAndLabelableFixture('advised', 2));
        }
        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * An around advice on a method with a DNF typed parameter
     */
    #[Flow\Around('method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithDnfTypes->describe())')]
    public function dnfTypeDescribeAdvice(JoinPointInterface $joinPoint): string
    {
        return 'advised: ' . $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * A pointcut expression whose class name pattern also matches an enum
     * (LabeledFixtureEnum) besides a regular class (LabeledFixtureThing).
     * Enums cannot be proxied, therefore only the regular class must be advised.
     */
    #[Flow\Around('method(Neos\Flow\Tests\Functional\Aop\Fixtures\Labeled.*->get.*())')]
    public function labelAdvice(JoinPointInterface $joinPoint): string
    {
        self::$numberOfLabelAdviceInvocations[$joinPoint->getClassName()] = (self::$numberOfLabelAdviceInvocations[$joinPoint->getClassName()] ?? 0) + 1;
        return 'advised ' . $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * An around advice on a method which is called through a first class callable
     */
    #[Flow\Around('method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassForFirstClassCallables->shout())')]
    public function firstClassCallableAdvice(JoinPointInterface $joinPoint): string
    {
        return $joinPoint->getAdviceChain()->proceed($joinPoint) . '!';
    }

    /**
     * An around advice on the magic __toString() method
     */
    #[Flow\Around('method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithToStringMethod->__toString())')]
    public function toStringAdvice(JoinPointInterface $joinPoint): string
    {
        return 'advised ' . $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * An around advice on a method which the target class got from a regularly
     * used trait
     */
    #[Flow\Around('method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassUsingATrait->greetFromTrait())')]
    public function traitMethodAdvice(JoinPointInterface $joinPoint): string
    {
        return $joinPoint->getAdviceChain()->proceed($joinPoint) . ', from the trait';
    }
}
