<?php
namespace TYPO3\Flow\Tests\Functional\Object;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Reflection\ClassReflection;
use TYPO3\Flow\Tests\Functional\Object\Fixtures\FinalClassWithDependencies;

/**
 * Functional tests for the Proxy Compiler and related features
 *
 */
class ProxyCompilerTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    /**
     * @test
     */
    public function proxyClassesStillContainAnnotationsFromItsOriginalClass()
    {
        $class = new ClassReflection(\TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        $this->assertTrue($class->implementsInterface(\TYPO3\Flow\Object\Proxy\ProxyInterface::class));
        $this->assertTrue($class->isTaggedWith('scope'));
        $this->assertTrue($method->isTaggedWith('session'));
    }

    /**
     * @test
     */
    public function proxyClassesStillContainDocCommentsFromItsOriginalClass()
    {
        $class = new ClassReflection(\TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassWithDocComments::class);
        $expectedResult = 'This is a example doc comment which should be copied' . chr(10) . 'to the proxy class.';
        $actualResult = $class->getDescription();

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function proxiedMethodsStillContainReturnAnnotationFromOriginalClass()
    {
        $class = new ClassReflection(\TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('getSingletonA');

        $this->assertEquals(array('\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA The singleton class A'), $method->getTagValues('return'));
    }

    /**
     * @test
     */
    public function proxiedMethodsStillContainParamDocumentationFromOriginalClass()
    {
        $class = new ClassReflection(\TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        $this->assertEquals(array('string $someProperty The property value'), $method->getTagValues('param'));
    }

    /**
     * @test
     */
    public function proxiedMethodsDoContainAnnotationsOnlyOnce()
    {
        $class = new ClassReflection(\TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        $this->assertEquals(array('autoStart=true'), $method->getTagValues('session'));
    }

    /**
     * @test
     */
    public function classesAnnotatedWithProxyDisableAreNotProxied()
    {
        $singletonB = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB::class);
        $this->assertNotInstanceOf(\TYPO3\Flow\Object\Proxy\ProxyInterface::class, $singletonB);
    }

    /**
     * @test
     */
    public function setInstanceOfSubClassDoesNotOverrideParentClass()
    {
        $singletonE = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassE::class);
        $this->assertEquals(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassE::class, get_class($singletonE));

        $singletonEsub = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassEsub::class);
        $this->assertEquals(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassEsub::class, get_class($singletonEsub));

        $singletonE2 = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassE::class);
        $this->assertEquals(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassE::class, get_class($singletonE2));
        $this->assertSame($singletonE, $singletonE2);
    }

    /**
     * @test
     */
    public function transientPropertiesAreNotSerializedOnSleep()
    {
        $prototypeF = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassF::class);
        $prototypeF->setTransientProperty('foo');
        $prototypeF->setNonTransientProperty('bar');

        $serializedObject = serialize($prototypeF);
        $prototypeF = null;

        $prototypeF = unserialize($serializedObject);
        $this->assertSame($prototypeF->getNonTransientProperty(), 'bar');
        $this->assertSame($prototypeF->getTransientProperty(), null);
    }

    /**
     * @test
     */
    public function proxiedFinalClassesAreStillFinal()
    {
        $reflectionClass = new ClassReflection(FinalClassWithDependencies::class);
        $this->assertTrue($reflectionClass->isFinal());
    }
}
