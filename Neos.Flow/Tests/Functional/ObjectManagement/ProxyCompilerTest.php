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

use Neos\Flow\ObjectManagement\Exception\CannotBuildObjectException;
use Neos\Flow\ObjectManagement\Proxy\ProxyClass;
use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Flow\Reflection\ClassReflection;
use Neos\Flow\Reflection\MethodReflection;
use Neos\Flow\Reflection\PropertyReflection;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassImplementingInterfaceWithConstructor;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP81\BackedEnumWithMethod;
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
     * This test would fail with a fatal error, if Flow would try to build a proxy class for the given Enum:
     *
     * PHP Fatal error:  Cannot declare class Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP8\BackedEnumWithMethod,
     * because the name is already in use in …/Flow_Object_Classes/Neos_Flow_Tests_Functional_ObjectManagement_Fixtures_PHP8_BackedEnumWithMethod.php on line 47
     *
     * @test
     */
    public function enumsAreNotProxied()
    {
        if (version_compare(PHP_VERSION, '8.1', '<=')) {
            $this->markTestSkipped('Only for PHP.1 8 with Enums');
        }

        # PHP < 8.1 would fail compiling this test case if we used the syntax BackedEnumWithMethod::ESPRESSO->label()
        $this->assertSame('Espresso', BackedEnumWithMethod::getLabel(BackedEnumWithMethod::ESPRESSO));
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

    /**
     * @test
     */
    public function attributesArePreserved()
    {
        if (PHP_MAJOR_VERSION < 8) {
            $this->markTestSkipped('Only for PHP 8 with Attributes');
        }
        $reflectionClass = new ClassReflection(Fixtures\ClassWithPhpAttributes::class);
        $attributes = $reflectionClass->getAttributes();
        self::assertCount(2, $attributes);
        self::assertEquals(Fixtures\SampleAttribute::class, $attributes[0]->getName());
        self::assertEquals(Fixtures\ClassWithPhpAttributes::class, $attributes[0]->getArguments()[0]);
    }

    /**
     * @test
     * @see https://github.com/neos/flow-development-collection/issues/2554
     */
    public function proxyingClassImplementingInterfacesWithParametrizedConstructorsLeadsToException()
    {
        $this->expectException(CannotBuildObjectException::class);
        $proxyClass = new ProxyClass(ClassImplementingInterfaceWithConstructor::class);
        $proxyClass->injectReflectionService($this->objectManager->get(ReflectionService::class));
        $proxyClass->getConstructor()->addPreParentCallCode('// some code');
        $proxyClass->render();
    }

    /**
     * @test
     */
    public function complexPropertyTypesArePreserved()
    {
        if (PHP_MAJOR_VERSION < 8) {
            $this->markTestSkipped('Only for PHP 8 with UnionTypes');
        }
        $reflectionClass = new ClassReflection(Fixtures\PHP8\ClassWithUnionTypes::class);
        /** @var PropertyReflection $property */
        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->getName() !== 'propertyA' && $property->getName() !== 'propertyB') {
                self::assertInstanceOf(\ReflectionUnionType::class, $property->getType(), $property->getName() . ': ' . $property->getType());
            }
        }
        self::assertEquals(
            $reflectionClass->getProperty('propertyA')->getType(),
            $reflectionClass->getProperty('propertyB')->getType(),
            '?string is equal to string|null'
        );
    }

    /**
     * @test
     */
    public function complexMethodReturnTypesArePreserved()
    {
        if (PHP_MAJOR_VERSION < 8) {
            $this->markTestSkipped('Only for PHP 8 with UnionTypes');
        }
        $reflectionClass = new ClassReflection(Fixtures\PHP8\ClassWithUnionTypes::class);
        /** @var MethodReflection $method */
        foreach ($reflectionClass->getMethods() as $method) {
            if (str_starts_with($method->getName(), 'get') &&
                !str_ends_with($method->getName(), 'PropertyA') &&
                !str_ends_with($method->getName(), 'PropertyB')) {
                self::assertInstanceOf(\ReflectionUnionType::class, $method->getReturnType(), $method->getName() . ': ' . $method->getReturnType());
            }
        }
        self::assertEquals(
            $reflectionClass->getMethod('getPropertyA')->getReturnType(),
            $reflectionClass->getMethod('getPropertyB')->getReturnType(),
            '?string is equal to string|null'
        );
    }

    /**
     * @test
     */
    public function constructorPropertiesArePreserved()
    {
        if (PHP_MAJOR_VERSION < 8) {
            $this->markTestSkipped('Only for PHP 8 with Constructor properties');
        }
        $reflectionClass = new ClassReflection(Fixtures\PHP8\ClassWithConstructorProperties::class);
        /** @var PropertyReflection $property */
        self::assertTrue($reflectionClass->hasProperty('propertyA'));
        self::assertTrue($reflectionClass->hasProperty('propertyB'));
        self::assertTrue($reflectionClass->hasProperty('propertyC'));

        self::assertEquals('?string', (string)$reflectionClass->getProperty('propertyA')->getType());
        self::assertEquals('?int', (string)$reflectionClass->getProperty('propertyB')->getType());
        self::assertEquals('?DateTime', (string)$reflectionClass->getProperty('propertyC')->getType());
    }
}
