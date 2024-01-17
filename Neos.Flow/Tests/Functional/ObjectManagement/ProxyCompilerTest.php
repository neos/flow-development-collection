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

use Neos\Flow\Annotations\Around;
use Neos\Flow\Annotations\Session;
use Neos\Flow\ObjectManagement\Exception\CannotBuildObjectException;
use Neos\Flow\ObjectManagement\Proxy\ProxyClass;
use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Flow\Reflection\ClassReflection;
use Neos\Flow\Reflection\PropertyReflection;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassExtendingClassWithPrivateConstructor;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassImplementingInterfaceWithConstructor;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassWithPrivateConstructor;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP81\BackedEnumWithMethod;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassA;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassK;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SampleAttribute;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the Proxy Compiler and related features
 */
class ProxyCompilerTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function proxyClassesStillContainAnnotationsFromItsOriginalClass(): void
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
    public function proxyClassesStillContainDocCommentsFromItsOriginalClass(): void
    {
        $class = new ClassReflection(Fixtures\ClassWithDocComments::class);
        $expectedResult = 'This is a example doc comment which should be copied' . chr(10) . 'to the proxy class.';
        $actualResult = $class->getDescription();

        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function proxiedMethodsStillContainReturnAnnotationFromOriginalClass(): void
    {
        $class = new ClassReflection(Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('getSingletonA');

        self::assertEquals(['SingletonClassA The singleton class A'], $method->getTagValues('return'));
    }

    /**
     * @test
     */
    public function proxiedMethodsStillContainParamDocumentationFromOriginalClass(): void
    {
        $class = new ClassReflection(Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        self::assertEquals(['string $someProperty The property value'], $method->getTagValues('param'));
    }

    /**
     * @test
     */
    public function proxiedMethodsDoContainAnnotationsOnlyOnce(): void
    {
        $class = new ClassReflection(Fixtures\PrototypeClassA::class);
        $method = $class->getMethod('setSomeProperty');

        self::assertEquals(['autoStart=true'], $method->getTagValues('session'));
    }

    /**
     * @test
     */
    public function proxiedMethodsStillContainMethodAttributesFromOriginalClass(): void
    {
        $class = new ClassReflection(Fixtures\ClassWithPhpAttributes::class);
        $actualAttributes = [];
        foreach ($class->getMethod('methodWithAttributes')->getAttributes() as $attribute) {
            $actualAttributes[] = [
                'name' => $attribute->getName(),
                'arguments' => $attribute->getArguments(),
            ];
        }
        $expectedAttributes = [
            [
                'name' => Around::class,
                'arguments' => ['pointcutExpression' => 'method(somethingImpossible())']
            ],
            [
                'name' => Session::class,
                'arguments' => ['autoStart' => false]
            ]
        ];
        self::assertEquals($expectedAttributes, $actualAttributes);
    }

    /**
     * @test
     */
    public function classesAnnotatedWithProxyDisableAreNotProxied(): void
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
    public function enumsAreNotProxied(): void
    {
        if (PHP_VERSION_ID <= 80100) {
            $this->markTestSkipped('Only for PHP.1 8 with Enums');
        }

        # PHP < 8.1 would fail compiling this test case if we used the syntax BackedEnumWithMethod::ESPRESSO->label()
        $this->assertSame('Espresso', BackedEnumWithMethod::getLabel(BackedEnumWithMethod::ESPRESSO));
    }

    /**
     * @test
     */
    public function setInstanceOfSubClassDoesNotOverrideParentClass(): void
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
     * @noinspection SuspiciousAssignmentsInspection
     */
    public function transientPropertiesAreNotSerializedOnSleep(): void
    {
        $prototypeF = $this->objectManager->get(Fixtures\PrototypeClassF::class);
        $prototypeF->setTransientProperty('foo');
        $prototypeF->setNonTransientProperty('bar');

        $serializedObject = serialize($prototypeF);
        $prototypeF = null;

        $prototypeF = unserialize($serializedObject);
        self::assertSame($prototypeF->getNonTransientProperty(), 'bar');
        self::assertNull($prototypeF->getTransientProperty());
    }

    /**
     * @test
     */
    public function proxiedFinalClassesAreStillFinal(): void
    {
        $reflectionClass = new ClassReflection(Fixtures\FinalClassWithDependencies::class);
        self::assertTrue($reflectionClass->isFinal());
    }

    /**
     * @test
     */
    public function proxiedReadonlyClassesAreStillReadonly(): void
    {
        $reflectionClass = new ClassReflection(Fixtures\ReadonlyClassWithDependencies::class);
        self::assertTrue($reflectionClass->isReadOnly());
    }

    /**
     * @see https://github.com/neos/flow-development-collection/issues/1835
     * @test
     */
    public function classKeywordIsIgnoredInsideClassBody(): void
    {
        $reflectionClass = new ClassReflection(Fixtures\ClassWithKeywordsInClassBody::class);
        self::assertEquals(Fixtures\ClassWithKeywordsInClassBody::class, $reflectionClass->getNamespaceName() . '\ClassWithKeywordsInClassBody');
    }

    /**
     * @test
     */
    public function attributesArePreserved(): void
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
     * @throws
     */
    public function proxyingClassImplementingInterfacesWithParametrizedConstructorsLeadsToException(): void
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
    public function complexPropertyTypesArePreserved(): void
    {
        if (PHP_MAJOR_VERSION < 8) {
            $this->markTestSkipped('Only for PHP 8 with UnionTypes');
        }
        $reflectionClass = new ClassReflection(Fixtures\PHP8\ClassWithUnionTypes::class);

        foreach ($reflectionClass->getProperties() as $property) {
            assert($property instanceof PropertyReflection);
            if ($property->getName() !== 'propertyA' && $property->getName() !== 'propertyB' && !str_starts_with($property->getName(), 'Flow_')) {
                self::assertInstanceOf(\ReflectionUnionType::class, $property->getType(), sprintf('Property "%s" is of type "%s"', $property->getName(), $property->getType()));
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
    public function complexMethodReturnTypesArePreserved(): void
    {
        if (PHP_MAJOR_VERSION < 8) {
            $this->markTestSkipped('Only for PHP 8 with UnionTypes');
        }
        $reflectionClass = new ClassReflection(Fixtures\PHP8\ClassWithUnionTypes::class);
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
    public function constructorPropertiesArePreserved(): void
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

    /**
     * @test
     */
    public function classWithPrivateConstructorCanBeProxied(): void
    {
        $anotherDependency = new PrototypeClassA();
        $object = ClassWithPrivateConstructor::createInParentClass('the argument', $anotherDependency);

        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertSame($anotherDependency, $object->anotherDependency);
    }

    /**
     * @test
     * @noinspection PhpExpressionResultUnusedInspection
     */
    public function privateConstructorOfProxiedClassCannotBeCalledFromOtherContexts(): void
    {
        $this->expectExceptionCode(1686153840);
        new ClassWithPrivateConstructor('the argument', new PrototypeClassA());
    }

    /**
     * @test
     * @noinspection UnnecessaryAssertionInspection
     */
    public function privateConstructorOfProxiedClassCanBeCalledFromProxiedSubClass(): void
    {
        $anotherDependency = new PrototypeClassA();
        $object = ClassExtendingClassWithPrivateConstructor::createInSubClass('the argument', $anotherDependency);

        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertInstanceOf(ClassWithPrivateConstructor::class, $object);
        self::assertInstanceOf(ClassExtendingClassWithPrivateConstructor::class, $object);
        self::assertSame($anotherDependency, $object->anotherDependency);
    }

    /**
     * @test
     * @noinspection UnnecessaryAssertionInspection
     */
    public function privateConstructorOfProxiedClassCanBeCalledFromAbstractParentClass(): void
    {
        $anotherDependency = new PrototypeClassA();
        $object = ClassWithPrivateConstructor::createInAbstractClass('the argument', $anotherDependency);

        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertInstanceOf(ClassWithPrivateConstructor::class, $object);
        self::assertNotInstanceOf(ClassExtendingClassWithPrivateConstructor::class, $object);
        self::assertSame($anotherDependency, $object->anotherDependency);
    }

    /**
     * @test
     */
    public function factoryMethodUsingSelfWorksEvenIfClassIsProxied(): void
    {
        $anotherDependency = new PrototypeClassA();
        $object = ClassWithPrivateConstructor::createUsingSelf('the argument', $anotherDependency);

        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertInstanceOf(ClassWithPrivateConstructor::class, $object);
        self::assertNotInstanceOf(ClassExtendingClassWithPrivateConstructor::class, $object);
        self::assertSame($anotherDependency, $object->anotherDependency);

        $expectedSelves = <<<PHP
            new self();
            self::class;
            self::create();
            function foo(self \$self): self {
                return \$self;
            }
        PHP;
        self::assertSame($expectedSelves, $object->getStringContainingALotOfSelves());
    }

    /**
     * @test
     */
    public function staticCompileWillResultInAFrozenReturnValue(): void
    {
        $object = new PrototypeClassK();
        self::assertSame($object->getToken(), $object->getToken());
    }
}
