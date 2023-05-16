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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * An aspect for testing the basic functionality of the AOP framework
 *
 * Note: In this class we use old-fashioned PHPDoc annotations with or without fully
 *       qualified class names and PHP attributes, because we want to test all variations.
 *
 * @Flow\Introduce("class(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass03)", interfaceName="Neos\Flow\Tests\Functional\Aop\Fixtures\Introduced01Interface")
 * @Flow\Aspect
 */
class BaseFunctionalityTestingAspect
{
    /**
     * @Flow\Introduce("class(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass03)")
     * @var string
     */
    protected $introducedProtectedProperty;

    /**
     * @Flow\Introduce("class(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass03)")
     * @var array
     */
    public $introducedPublicProperty;

    /**
     * @Flow\Introduce("class(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass03)")
     * @var string
     */
    public $introducedProtectedPropertyWithDefaultValue = 'thisIsADefaultValueBelieveItOrNot';

    /**
     * @Flow\Around("method(public Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->__construct())")
     * @param JoinPointInterface $joinPoint
     * @return void
     */
    public function lousyConstructorAdvice(JoinPointInterface $joinPoint): void
    {
        $joinPoint->getAdviceChain()->proceed($joinPoint);
        $proxy = $joinPoint->getProxy();
        assert($proxy instanceof TargetClass01);
        $proxy->constructorResult .= ' is lousier than A-380';
    }

    /**
     * @Flow\Around("within(Neos\Flow\Tests\Functional\Aop\Fixtures\SayHelloInterface) && method(.*->sayHello())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function worldAdvice(JoinPointInterface $joinPoint): string
    {
        return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' World';
    }

    /**
     * @Flow\Around("within(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01) && method(.*->sayWhatFlowIs())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function rocketScienceAdvice(JoinPointInterface $joinPoint): string
    {
        return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' Rocket Science';
    }

    /**
     * @Flow\Around("within(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01) && method(.*->someStaticMethod())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function tryToWrapStaticMethodAdvice(JoinPointInterface $joinPoint): string
    {
        return 'failed';
    }

    /**
     * @Flow\Around("method(public Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->saySomethingSmart())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function somethingSmartAdvice(JoinPointInterface $joinPoint): string
    {
        return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' For big twos and small fives!';
    }

    /**
     * @Flow\Around("method(public Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->sayHelloAndThrow())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function throwWorldAdvice(JoinPointInterface $joinPoint): string
    {
        return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' World';
    }

    /**
     * @Flow\Around("method(public Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->greet(name === 'Flow'))")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function specialNameAdvice(JoinPointInterface $joinPoint): string
    {
        return 'Hello, me';
    }

    /**
     * @Flow\Around("method(public Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->greet())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function changeNameArgumentAdvice(JoinPointInterface $joinPoint): string
    {
        if ($joinPoint->getMethodArgument('name') === 'Andi') {
            $joinPoint->setMethodArgument('name', 'Robert');
        }
        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * @Flow\Around("method(public Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->greetMany(names contains this.currentName))")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function manyNamesAdvice(JoinPointInterface $joinPoint): string
    {
        return 'Hello, special guest';
    }

    /**
     * @Flow\AfterReturning("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass02->publicTargetMethod())")
     * @param JoinPointInterface $joinPoint
     * @return void
     */
    public function anAfterReturningAdvice(JoinPointInterface $joinPoint): void
    {
        $proxy = $joinPoint->getProxy();
        assert($proxy instanceof TargetClass02);
        $proxy->afterReturningAdviceWasInvoked = true;
    }

    /**
     * @Flow\Around("method(protected Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass02->protectedTargetMethod())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function anAdviceForAProtectedTargetMethod(JoinPointInterface $joinPoint): string
    {
        return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' bar';
    }

    /**
     * @Flow\Around("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->greetObject(name.name === 'Neos'))")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function propertyOnMethodArgumentAdvice(JoinPointInterface $joinPoint): string
    {
        return 'Hello, old friend';
    }

    /**
     * @Flow\Around("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->greetObject(name === this.currentName))")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function thisOnMethodArgumentAdvice(JoinPointInterface $joinPoint): string
    {
        return 'Hello, you';
    }

    /**
     * @Flow\Around("method(public Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->greet(name === current.testContext.nameOfTheWeek))")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function globalNameAdvice(JoinPointInterface $joinPoint): string
    {
        return 'Hello, superstar';
    }

    /**
     * @Flow\Around("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass03->introducedMethod01())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function introducedMethod01Implementation(JoinPointInterface $joinPoint): string
    {
        return 'Implemented';
    }

    /**
     * @Flow\Around("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass03->introducedMethodWithArguments())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function introducedMethodWithArgumentsImplementation(JoinPointInterface $joinPoint): string
    {
        return 'Implemented';
    }

    /**
     * @Flow\Around("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithPhp7Features->methodWithStaticTypeDeclarations())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function methodWithStaticTypeDeclarationsAdvice(JoinPointInterface $joinPoint): string
    {
        return 'This is so NaN';
    }

    /**
     * @Flow\Around("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithFinalModifier->someMethod())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function methodOfFinalClassAdvice(JoinPointInterface $joinPoint): string
    {
        $originalValue = $joinPoint->getAdviceChain()->proceed($joinPoint);
        return 'nothing is ' . $originalValue . '!';
    }

    /**
     * @Flow\Around("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->someFinalMethod())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function methodWithFinalModifierAdvice(JoinPointInterface $joinPoint): string
    {
        $originalValue = $joinPoint->getAdviceChain()->proceed($joinPoint);
        return $originalValue . ' But, as said, nothing is final!';
    }

    /**
     * @Flow\Around("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithPhp7Features->methodWithStaticScalarReturnTypeDeclaration())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function methodWithStaticScalarReturnTypeDeclarationAdvice(JoinPointInterface $joinPoint): string
    {
        return 'advised: ' . $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * @Flow\Around("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithPhp7Features->methodWithStaticObjectReturnTypeDeclaration())")
     * @param JoinPointInterface $joinPoint
     * @return TargetClassWithPhp7Features
     */
    public function methodWithStaticObjectReturnTypeDeclarationAdvice(JoinPointInterface $joinPoint): TargetClassWithPhp7Features
    {
        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * @Flow\Around("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithPhp7Features->methodWithNullableScalarReturnTypeDeclaration())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function methodWithNullableScalarReturnTypeDeclarationAdvice(JoinPointInterface $joinPoint): string
    {
        $originalResult = $joinPoint->getAdviceChain()->proceed($joinPoint);
        return 'advised: ' . ($originalResult ?? 'NULL');
    }

    /**
     * @Flow\Around("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithPhp7Features->methodWithNullableObjectReturnTypeDeclaration())")
     * @param JoinPointInterface $joinPoint
     * @return null
     */
    public function methodWithNullableObjectReturnTypeDeclarationAdvice(JoinPointInterface $joinPoint): null
    {
        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }
}
