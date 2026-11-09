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
use Neos\Flow\Tests\Functional\Aop\Fixtures\Introduced01Interface;
use Neos\Flow\Tests\Functional\Aop\Fixtures\Name;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass01;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass02;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass03;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass04;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithFinalModifier;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithNeverReturnType;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithPhp7Features;
use Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithPhp8Features;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the AOP Framework class
 */
final class FrameworkTest extends FunctionalTestCase
{
    #[Test]
    public function resultOfSayHelloMethodIsModifiedByWorldAdvice(): void
    {
        $targetClass = new TargetClass01();
        self::assertSame('Hello World', $targetClass->sayHello());
    }

    /**
     * @throws
     */
    #[Test]
    public function adviceRecoversFromException(): void
    {
        $targetClass = new TargetClass01();
        try {
            $targetClass->sayHelloAndThrow(true);
        } catch (\Exception) {
        }
        self::assertSame('Hello World', $targetClass->sayHelloAndThrow(false));
    }

    #[Test]
    public function resultOfGreetMethodIsModifiedBySpecialNameAdvice(): void
    {
        $targetClass = new TargetClass01();
        self::assertSame('Hello, me', $targetClass->greet('Flow'));
        self::assertSame('Hello, Christopher', $targetClass->greet('Christopher'));
    }

    #[Test]
    public function containWithSplObjectStorageInRuntimeEvaluation(): void
    {
        $targetClass = new TargetClass01();
        $name = new Name('Flow');
        $otherName = new Name('Neos');
        $splObjectStorage = new \SplObjectStorage();
        $splObjectStorage->attach($name);
        $targetClass->setCurrentName($name);
        self::assertEquals('Hello, special guest', $targetClass->greetMany($splObjectStorage));
        $targetClass->setCurrentName();
        self::assertEquals('Hello, Flow', $targetClass->greetMany($splObjectStorage));
        $targetClass->setCurrentName($otherName);
        self::assertEquals('Hello, Flow', $targetClass->greetMany($splObjectStorage));
    }

    #[Test]
    public function constructorAdvicesAreInvoked(): void
    {
        $targetClass = new TargetClass01();
        self::assertSame('AVRO RJ100 is lousier than A-380', $targetClass->constructorResult);
    }

    #[Test]
    public function withinPointcutsAlsoAcceptClassNames(): void
    {
        $targetClass = new TargetClass01();
        self::assertSame('Flow is Rocket Science', $targetClass->sayWhatFlowIs(), 'TargetClass01');
        $childClass = new ChildClassOfTargetClass01();
        self::assertSame('Flow is not Rocket Science', $childClass->sayWhatFlowIs(), 'Child class of TargetClass01');
    }

    #[Test]
    public function adviceInformationIsAlsoBuiltWhenTheTargetClassIsDeserialized(): void
    {
        $className = TargetClass01::class;
        $targetClass = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{}');
        self::assertSame('Hello, me', $targetClass->greet('Flow'));
    }

    #[Test]
    public function afterReturningAdviceIsTakingEffect(): void
    {
        $targetClass = new TargetClass02();
        $targetClass->publicTargetMethod('foo');
        self::assertTrue($targetClass->afterReturningAdviceWasInvoked);
    }

    /**
     * Due to the way the proxy classes are rendered, lifecycle methods such as
     * initializeObject() were called twice if the constructor is advised by some
     * aspect. This test makes sure that any code after the AOP advice code is only
     * executed once.
     *
     * Test for bugfix #25610
     */
    #[Test]
    public function codeAfterTheAopCodeInTheProxyMethodIsOnlyCalledOnce(): void
    {
        $targetClass = new TargetClass01();
        self::assertEquals(1, $targetClass->initializeObjectCallCounter);
    }

    /**
     * Checks if the target class is protected, the advice is woven in any way.
     * The necessary advice is defined in BaseFunctionalityAspect.
     *
     * Test for bugfix #2581
     */
    #[Test]
    public function protectedMethodsCanAlsoBeAdvised(): void
    {
        $targetClass = new TargetClass02();
        $result = $targetClass->publicTargetMethod('foo');
        self::assertEquals('foo bar', $result);
    }

    #[Test]
    public function resultOfGreetObjectMethodIsModifiedByAdvice(): void
    {
        $targetClass = $this->objectManager->get(TargetClass01::class);
        $name = new Name('Neos');
        self::assertSame('Hello, old friend', $targetClass->greetObject($name), 'Aspect should greet with "old friend" if the name property equals "Neos"');
        $name = new Name('Christopher');
        self::assertSame('Hello, Christopher', $targetClass->greetObject($name));
    }

    #[Test]
    public function thisIsSupportedInMethodRuntimeCondition(): void
    {
        $targetClass = $this->objectManager->get(TargetClass01::class);
        $name = new Name('Fusion');
        $targetClass->setCurrentName($name);
        self::assertSame('Hello, you', $targetClass->greetObject($name), 'Aspect should greet with "you" if the current name equals the name argument');

        $name = new Name('Christopher');
        $targetClass->setCurrentName();
        self::assertSame('Hello, Christopher', $targetClass->greetObject($name), 'Aspect should greet with given name if the current name is not equal to the name argument');
    }

    #[Test]
    public function globalObjectsAreSupportedInMethodRuntimeCondition(): void
    {
        $targetClass = $this->objectManager->get(TargetClass01::class);
        self::assertSame('Hello, superstar', $targetClass->greet('Robbie'), 'Aspect should greet with "superstar" if the global context getNameOfTheWeek equals the given name');
        self::assertSame('Hello, Christopher', $targetClass->greet('Christopher'), 'Aspect should greet with given name if the global context getNameOfTheWeek does not equal the given name');
    }

    /**
     * An interface with a method which is not advised and thus not implemented can be introduced.
     * The proxy class contains a placeholder implementation of that introduced method.
     */
    #[Test]
    public function interfaceWithMethodCanBeIntroduced(): void
    {
        $targetClass = new TargetClass03();

        self::assertInstanceOf(Introduced01Interface::class, $targetClass);
        self::assertTrue(method_exists($targetClass, 'introducedMethod01'));
        self::assertTrue(method_exists($targetClass, 'introducedMethodWithArguments'));
    }

    /**
     * @noinspection VariableFunctionsUsageInspection
     */
    #[Test]
    public function traitWithNewMethodCanBeIntroduced(): void
    {
        $targetClass = new TargetClass01();

        self::assertEquals('I\'m the traitor', call_user_func([$targetClass, 'introducedTraitMethod']));
    }

    #[Test]
    public function introducedTraitMethodWontOverrideExistingMethods(): void
    {
        $targetClass = new TargetClass01();

        self::assertNotEquals('Hello from trait', $targetClass->sayHello());
        self::assertEquals('Hello World', $targetClass->sayHello());
    }

    /**
     * Public and protected properties can be introduced.
     */
    #[Test]
    public function propertiesCanBeIntroduced(): void
    {
        $targetClass = new TargetClass03();

        self::assertTrue(property_exists(get_class($targetClass), 'introducedPublicProperty'));
        self::assertTrue(property_exists(get_class($targetClass), 'introducedProtectedProperty'));
    }

    /**
     * Public and protected properties can be introduced.
     */
    #[Test]
    public function onlyPropertiesCanBeIntroduced(): void
    {
        $targetClass = new TargetClass04();

        self::assertTrue(property_exists(get_class($targetClass), 'introducedPublicProperty'));
        self::assertTrue(property_exists(get_class($targetClass), 'introducedProtectedProperty'));
    }

    #[Test]
    public function methodArgumentsCanBeSetInTheJoinPoint(): void
    {
        $targetClass = new TargetClass01();
        $result = $targetClass->greet('Andi');
        self::assertEquals('Hello, Robert', $result, 'The method argument "name" has not been changed as expected by the "changeNameArgumentAdvice".');
    }

    /**
     * @noinspection PhpUndefinedFieldInspection
     */
    #[Test]
    public function introducedPropertiesCanHaveADefaultValue(): void
    {
        $targetClass = new TargetClass03();

        self::assertNull($targetClass->introducedPublicProperty);
        self::assertSame('thisIsADefaultValueBelieveItOrNot', $targetClass->introducedProtectedPropertyWithDefaultValue);
    }

    #[Test]
    public function methodWithStaticTypeDeclarationsCanBeAdvised(): void
    {
        $targetClass = new TargetClassWithPhp7Features();

        self::assertSame('This is so NaN', $targetClass->methodWithStaticTypeDeclarations('The answer', 42, $targetClass));
    }

    #[Test]
    public function finalClassesCanBeAdvised(): void
    {
        $targetClass = new TargetClassWithFinalModifier();
        self::assertSame('nothing is final!', $targetClass->someMethod());
    }

    #[Test]
    public function finalMethodsCanBeAdvised(): void
    {
        $targetClass = new TargetClass01();
        self::assertSame('I am final. But, as said, nothing is final!', $targetClass->someFinalMethod());
    }

    /**
     * @throws
     */
    #[Test]
    public function finalMethodsStayFinalEvenIfTheyAreNotAdvised(): void
    {
        $targetClass = new TargetClass01();
        self::assertTrue((new \ReflectionMethod($targetClass, 'someOtherFinalMethod'))->isFinal());
    }

    #[Test]
    public function methodWithStaticScalarReturnTypeDeclarationCanBeAdvised(): void
    {
        $targetClass = new TargetClassWithPhp7Features();

        self::assertSame('advised: it works', $targetClass->methodWithStaticScalarReturnTypeDeclaration());
    }

    /**
     * @noinspection UnnecessaryAssertionInspection
     */
    #[Test]
    public function methodWithStaticObjectReturnTypeDeclarationCanBeAdvised(): void
    {
        $targetClass = new TargetClassWithPhp7Features();

        self::assertInstanceOf(TargetClassWithPhp7Features::class, $targetClass->methodWithStaticObjectReturnTypeDeclaration());
    }

    #[Test]
    public function methodWithNullableScalarReturnTypeDeclarationCanBeAdvised(): void
    {
        $targetClass = new TargetClassWithPhp7Features();

        self::assertSame('advised: NULL', $targetClass->methodWithNullableScalarReturnTypeDeclaration());
    }

    #[Test]
    public function methodWithNullableObjectReturnTypeDeclarationCanBeAdvised(): void
    {
        $targetClass = new TargetClassWithPhp7Features();

        self::assertNull($targetClass->methodWithNullableObjectReturnTypeDeclaration());
    }

    #[Test]
    public function methodWithUnionTypesCanBeAdvised(): void
    {
        $targetClass = new TargetClassWithPhp8Features();

        # Note: In order to prove that the advice is actually executed, the advice BaseFunctionalityTestingAspect::methodWithUnionTypes()
        #       modifies the second method argument and sets it to 123.
        self::assertSame(
            sprintf('advised: %s and %s and %s', 'Neos', 123, TargetClassWithPhp8Features::class),
            $targetClass->methodWithUnionTypes('Neos', 42, $targetClass)
        );
    }

    /**
     * @see https://github.com/neos/flow-development-collection/issues/2899
     */
    #[Test]
    public function methodWithReturnTypeMixedIsGeneratedCorrectly(): void
    {
        $targetClass = new TargetClassWithPhp8Features();

        # Note: In order to prove that the advice is actually executed, the advice BaseFunctionalityTestingAspect::invokeWithMixedReturnAdvice()
        #       modifies the flag and sets it to true
        self::assertSame('Flag is set', $targetClass->__invoke('Flag is set', 42, false));
    }

    /**
     * @see https://github.com/neos/flow-development-collection/issues/3027
     * @throws
     */
    #[Test]
    public function methodsWithReturnPhp8SimpleReturnTypesAreGeneratedCorrectly(): void
    {
        $targetClass = new TargetClassWithPhp8Features();

        # Note: In order to prove that the advice is actually executed, the advice BaseFunctionalityTestingAspect::methodsWithPhp8SimpleReturnTypesAdvice()
        #       modifies the flag and sets it to true
        self::assertTrue($targetClass->alwaysTrue());
        self::assertFalse($targetClass->alwaysFalse());

        # This needs https://github.com/laminas/laminas-code/pull/186 to be merged in order to work:
        # self::assertNull($targetClass->alwaysNull());

        $this->expectExceptionCode(1686132896);
        $targetClass->alwaysNever();
    }

    #[Test]
    public function methodWithNeverReturnTypeCanBeAdvised(): void
    {
        $targetClass = new TargetClassWithNeverReturnType();

        try {
            $targetClass->methodThatThrows();
        } catch (\Exception) {
        } finally {
            self::assertTrue($targetClass->beforeAdviceWasInvoked, 'Before advice should be invoked for never-returning method methodThatThrows()');
            self::assertTrue($targetClass->afterThrowingAdviceWasInvoked, 'AfterThrowing advice should be invoked for never-returning method methodThatThrows()');
        }
    }

    #[Test]
    public function methodWithNeverReturnTypeCanBeAroundAdvised(): void
    {
        $target = new TargetClassWithNeverReturnType();

        try {
            $target->aroundAdvicedMethodThatThrows();
        } catch (\Exception $e) {
            self::assertSame(1761036724, $e->getCode());
        } finally {
            self::assertTrue($target->aroundAdviceWasInvoked, 'Around advice should be invoked for never-returning method aroundAdvicedMethodThatThrows()');
        }
    }
}
