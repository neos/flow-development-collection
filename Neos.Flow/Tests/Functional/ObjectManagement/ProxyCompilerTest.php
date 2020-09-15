<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Flow\Reflection\ClassReflection;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the Proxy Compiler and related features
 *
 */
class ProxyCompilerTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function proxyClassesStillContainAnnotationsFromItsOriginalClass()
    {
        $class = new ClassReflection(Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        self::assertTrue($class->implementsInterface(ProxyInterface::class));
        self::assertTrue($class->isTaggedWith('scope'));
        self::assertTrue($method->isTaggedWith('session'));
    }

    /**
     * @test
     */
    public function proxyClassesStillContainDocCommentsFromItsOriginalClass()
    {
        $class = new ClassReflection(Fixtures\ClassWithDocComments::class);
        $expectedResult = 'This is a example doc comment which should be copied' . chr(10) . 'to the proxy class.';
        $actualResult = $class->getDescription();

        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function proxiedMethodsStillContainReturnAnnotationFromOriginalClass()
    {
        $class = new ClassReflection(Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('getSingletonA');

        self::assertEquals(['SingletonClassA The singleton class A'], $method->getTagValues('return'));
    }

    /**
     * @test
     */
    public function proxiedMethodsStillContainParamDocumentationFromOriginalClass()
    {
        $class = new ClassReflection(Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        self::assertEquals(['string $someProperty The property value'], $method->getTagValues('param'));
    }

    /**
     * @test
     */
    public function proxiedMethodsDoContainAnnotationsOnlyOnce()
    {
        $class = new ClassReflection(Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        self::assertEquals(['autoStart=true'], $method->getTagValues('session'));
    }

    /**
     * @test
     */
    public function classesAnnotatedWithProxyDisableAreNotProxied()
    {
        $singletonB = $this->objectManager->get(Fixtures\SingletonClassB::class);
        $this->assertNotInstanceOf(ProxyInterface::class, $singletonB);
    }

    /**
     * @test
     */
    public function setInstanceOfSubClassDoesNotOverrideParentClass()
    {
        $singletonE = $this->objectManager->get(Fixtures\SingletonClassE::class);
        self::assertEquals(Fixtures\SingletonClassE::class, get_class($singletonE));

        $singletonEsub = $this->objectManager->get(Fixtures\SingletonClassEsub::class);
        self::assertEquals(Fixtures\SingletonClassEsub::class, get_class($singletonEsub));

        $singletonE2 = $this->objectManager->get(Fixtures\SingletonClassE::class);
        self::assertEquals(Fixtures\SingletonClassE::class, get_class($singletonE2));
        self::assertSame($singletonE, $singletonE2);
    }

    /**
     * @test
     */
    public function transientPropertiesAreNotSerializedOnSleep()
    {
        $prototypeF = $this->objectManager->get(Fixtures\PrototypeClassF::class);
        $prototypeF->setTransientProperty('foo');
        $prototypeF->setNonTransientProperty('bar');

        $serializedObject = serialize($prototypeF);
        $prototypeF = null;

        $prototypeF = unserialize($serializedObject);
        self::assertSame($prototypeF->getNonTransientProperty(), 'bar');
        self::assertSame($prototypeF->getTransientProperty(), null);
    }

    /**
     * @test
     */
    public function proxiedFinalClassesAreStillFinal()
    {
        $reflectionClass = new ClassReflection(Fixtures\FinalClassWithDependencies::class);
        self::assertTrue($reflectionClass->isFinal());
    }
}
