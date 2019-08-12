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

use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for the AOP Framework class
 *
 */
class FrameworkTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function resultOfSayHelloMethodIsModifiedByWorldAdvice()
    {
        $targetClass = new Fixtures\TargetClass01();
        self::assertSame('Hello World', $targetClass->sayHello());
    }

    /**
     * @test
     */
    public function adviceRecoversFromException()
    {
        $targetClass = new Fixtures\TargetClass01();
        try {
            $targetClass->sayHelloAndThrow(true);
        } catch (\Exception $exception) {
        }
        self::assertSame('Hello World', $targetClass->sayHelloAndThrow(false));
    }

    /**
     * @test
     */
    public function resultOfGreetMethodIsModifiedBySpecialNameAdvice()
    {
        $targetClass = new Fixtures\TargetClass01();
        self::assertSame('Hello, me', $targetClass->greet('Flow'));
        self::assertSame('Hello, Christopher', $targetClass->greet('Christopher'));
    }

    /**
     * @test
     */
    public function containWithSplObjectStorageInRuntimeEvaluation()
    {
        $targetClass = new Fixtures\TargetClass01();
        $name = new Fixtures\Name('Flow');
        $otherName = new Fixtures\Name('Neos');
        $splObjectStorage = new \SplObjectStorage();
        $splObjectStorage->attach($name);
        $targetClass->setCurrentName($name);
        self::assertEquals('Hello, special guest', $targetClass->greetMany($splObjectStorage));
        $targetClass->setCurrentName(null);
        self::assertEquals('Hello, Flow', $targetClass->greetMany($splObjectStorage));
        $targetClass->setCurrentName($otherName);
        self::assertEquals('Hello, Flow', $targetClass->greetMany($splObjectStorage));
    }

    /**
     * @test
     */
    public function constructorAdvicesAreInvoked()
    {
        $targetClass = new Fixtures\TargetClass01();
        self::assertSame('AVRO RJ100 is lousier than A-380', $targetClass->constructorResult);
    }

    /**
     * @test
     */
    public function withinPointcutsAlsoAcceptClassNames()
    {
        $targetClass = new Fixtures\TargetClass01();
        self::assertSame('Flow is Rocket Science', $targetClass->sayWhatFlowIs(), 'TargetClass01');
        $childClass = new Fixtures\ChildClassOfTargetClass01();
        self::assertSame('Flow is not Rocket Science', $childClass->sayWhatFlowIs(), 'Child class of TargetClass01');
    }

    /**
     * @test
     */
    public function adviceInformationIsAlsoBuiltWhenTheTargetClassIsUnserialized()
    {
        $className = Fixtures\TargetClass01::class;
        $targetClass = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
        self::assertSame('Hello, me', $targetClass->greet('Flow'));
    }

    /**
     * @test
     */
    public function afterReturningAdviceIsTakingEffect()
    {
        $targetClass = new Fixtures\TargetClass02();
        $targetClass->publicTargetMethod('foo');
        self::assertTrue($targetClass->afterReturningAdviceWasInvoked);
    }

    /**
     * Due to the way the proxy classes are rendered, lifecycle methods such as
     * initializeObject() were called twice if the constructor is adviced by some
     * aspect. This test makes sure that any code after the AOP advice code is only
     * executed once.
     *
     * Test for bugfix #25610
     *
     * @test
     */
    public function codeAfterTheAopCodeInTheProxyMethodIsOnlyCalledOnce()
    {
        $targetClass = new Fixtures\TargetClass01();
        self::assertEquals(1, $targetClass->initializeObjectCallCounter);
    }

    /**
     * Checks if the target class is protected, the advice is woven in anyway.
     * The necessary advice is defined in BaseFunctionalityAspect.
     *
     * Test for bugfix #2581
     *
     * @test
     */
    public function protectedMethodsCanAlsoBeAdviced()
    {
        $targetClass = new Fixtures\TargetClass02();
        $result = $targetClass->publicTargetMethod('foo');
        self::assertEquals('foo bar', $result);
    }

    /**
     * @test
     */
    public function resultOfGreetObjectMethodIsModifiedByAdvice()
    {
        $targetClass = $this->objectManager->get(Fixtures\TargetClass01::class);
        $name = new Fixtures\Name('Neos');
        self::assertSame('Hello, old friend', $targetClass->greetObject($name), 'Aspect should greet with "old friend" if the name property equals "Neos"');
        $name = new Fixtures\Name('Christopher');
        self::assertSame('Hello, Christopher', $targetClass->greetObject($name));
    }

    /**
     * @test
     */
    public function thisIsSupportedInMethodRuntimeCondition()
    {
        $targetClass = $this->objectManager->get(Fixtures\TargetClass01::class);
        $name = new Fixtures\Name('Fusion');
        $targetClass->setCurrentName($name);
        self::assertSame('Hello, you', $targetClass->greetObject($name), 'Aspect should greet with "you" if the current name equals the name argument');

        $name = new Fixtures\Name('Christopher');
        $targetClass->setCurrentName(null);
        self::assertSame('Hello, Christopher', $targetClass->greetObject($name), 'Aspect should greet with given name if the current name is not equal to the name argument');
    }

    /**
     * @test
     */
    public function globalObjectsAreSupportedInMethodRuntimeCondition()
    {
        $targetClass = $this->objectManager->get(Fixtures\TargetClass01::class);
        self::assertSame('Hello, superstar', $targetClass->greet('Robbie'), 'Aspect should greet with "superstar" if the global context getNameOfTheWeek equals the given name');
        self::assertSame('Hello, Christopher', $targetClass->greet('Christopher'), 'Aspect should greet with given name if the global context getNameOfTheWeek does not equal the given name');
    }

    /**
     * An interface with a method which is not adviced and thus not implemented can be introduced.
     * The proxy class contains a place holder implementation of that introduced method.
     *
     * @test
     */
    public function interfaceWithMethodCanBeIntroduced()
    {
        $targetClass = new Fixtures\TargetClass03();

        self::assertInstanceOf(Fixtures\Introduced01Interface::class, $targetClass);
        self::assertTrue(method_exists($targetClass, 'introducedMethod01'));
        self::assertTrue(method_exists($targetClass, 'introducedMethodWithArguments'));
    }

    /**
     * @test
     */
    public function traitWithNewMethodCanBeIntroduced()
    {
        $targetClass = new Fixtures\TargetClass01();

        self::assertEquals('I\'m the traitor', call_user_func([$targetClass, 'introducedTraitMethod']));
    }

    /**
     * @test
     */
    public function introducedTraitMethodWontOverrideExistingMethods()
    {
        $targetClass = new Fixtures\TargetClass01();

        self::assertNotEquals('Hello from trait', $targetClass->sayHello());
        self::assertEquals('Hello World', $targetClass->sayHello());
    }

    /**
     * Public and protected properties can be introduced.
     *
     * @test
     */
    public function propertiesCanBeIntroduced()
    {
        $targetClass = new Fixtures\TargetClass03();

        self::assertTrue(property_exists(get_class($targetClass), 'introducedPublicProperty'));
        self::assertTrue(property_exists(get_class($targetClass), 'introducedProtectedProperty'));
    }

    /**
     * Public and protected properties can be introduced.
     *
     * @test
     */
    public function onlyPropertiesCanBeIntroduced()
    {
        $targetClass = new Fixtures\TargetClass04();

        self::assertTrue(property_exists(get_class($targetClass), 'introducedPublicProperty'));
        self::assertTrue(property_exists(get_class($targetClass), 'introducedProtectedProperty'));
    }

    /**
     * @test
     */
    public function methodArgumentsCanBeSetInTheJoinpoint()
    {
        $targetClass = new Fixtures\TargetClass01();
        $result = $targetClass->greet('Andi');
        self::assertEquals('Hello, Robert', $result, 'The method argument "name" has not been changed as expected by the "changeNameArgumentAdvice".');
    }

    /**
     * @test
     */
    public function introducedPropertiesCanHaveADefaultValue()
    {
        $targetClass = new Fixtures\TargetClass03();

        self::assertSame(null, $targetClass->introducedPublicProperty);
        self::assertSame('thisIsADefaultValueBelieveItOrNot', $targetClass->introducedProtectedPropertyWithDefaultValue);
    }

    /**
     * @test
     */
    public function methodWithStaticTypeDeclarationsCanBeAdvised()
    {
        if (version_compare(PHP_VERSION, '7.0.0') < 0) {
            $this->markTestSkipped('Requires PHP 7');
        }

        $targetClass = new Fixtures\TargetClassWithPhp7Features();

        self::assertSame('This is so NaN', $targetClass->methodWithStaticTypeDeclarations('The answer', 42, $targetClass));
    }

    /**
     * @test
     */
    public function finalClassesCanBeAdvised()
    {
        $targetClass = new Fixtures\TargetClassWithFinalModifier();
        self::assertSame('nothing is final!', $targetClass->someMethod());
    }

    /**
     * @test
     */
    public function finalMethodsCanBeAdvised()
    {
        $targetClass = new Fixtures\TargetClass01();
        self::assertSame('I am final. But, as said, nothing is final!', $targetClass->someFinalMethod());
    }

    /**
     * @test
     */
    public function finalMethodsStayFinalEvenIfTheyAreNotAdvised()
    {
        $targetClass = new Fixtures\TargetClass01();
        self::assertTrue((new \ReflectionMethod($targetClass, 'someOtherFinalMethod'))->isFinal());
    }

    /**
     * @test
     */
    public function methodWithStaticScalarReturnTypeDeclarationCanBeAdviced()
    {
        if (version_compare(PHP_VERSION, '7.0.0') < 0) {
            $this->markTestSkipped('Requires PHP 7');
        }

        $targetClass = new Fixtures\TargetClassWithPhp7Features();

        self::assertSame('adviced: it works', $targetClass->methodWithStaticScalarReturnTypeDeclaration());
    }

    /**
     * @test
     */
    public function methodWithStaticObjectReturnTypeDeclarationCanBeAdviced()
    {
        if (version_compare(PHP_VERSION, '7.0.0') < 0) {
            $this->markTestSkipped('Requires PHP 7');
        }

        $targetClass = new Fixtures\TargetClassWithPhp7Features();

        self::assertInstanceOf(Fixtures\TargetClassWithPhp7Features::class, $targetClass->methodWithStaticObjectReturnTypeDeclaration());
    }


    //  NOTE: The following tests are commented out for now because they break compatibility with PHP < 7.1
    //        We should re-activate them as soon as 7.1 is the minimal required PHP version for Flow
    //
    //    /**
    //     * @test
    //     */
    //    public function methodWithNullableScalarReturnTypeDeclarationCanBeAdviced()
    //    {
    //        if (version_compare(PHP_VERSION, '7.1.0') < 0) {
    //            $this->markTestSkipped('Requires PHP 7.1');
    //        }
    //
    //        $targetClass = new Fixtures\TargetClassWithPhp71Features();
    //
    //        self::assertSame('adviced: NULL', $targetClass->methodWithNullableScalarReturnTypeDeclaration());
    //    }
    //
    //    /**
    //     * @test
    //     */
    //    public function methodWithNullableObjectReturnTypeDeclarationCanBeAdviced()
    //    {
    //        if (version_compare(PHP_VERSION, '7.1.0') < 0) {
    //            $this->markTestSkipped('Requires PHP 7.1');
    //        }
    //
    //        $targetClass = new Fixtures\TargetClassWithPhp71Features();
    //
    //        self::assertNull($targetClass->methodWithNullableObjectReturnTypeDeclaration());
    //    }
}
