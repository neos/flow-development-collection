<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Reflection;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Reflection\ClassSchema;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\Functional\Persistence;
use Neos\Flow\Tests\Functional\Reflection;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClass;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\DummyClassWithProperties;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\DummyClassWithTypeHints;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\DummyReadonlyClass;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityExtendingPlainObject;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubEntity;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubSubEntity;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\Model\SuperEntity;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\PHP8\DummyClassWithDisjunctiveNormalFormTypes;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\PHP8\DummyClassWithUnionTypeHints;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\Repository\SubSubEntityRepository;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\Repository\SuperEntityRepository;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;

/**
 * Functional tests for the Reflection Service features
 */
final class ReflectionServiceTest extends FunctionalTestCase
{
    protected ReflectionService $reflectionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reflectionService = $this->objectManager->get(ReflectionService::class);
    }

    #[Test]
    public function theReflectionServiceBuildsClassSchemataForEntities(): void
    {
        $classSchema = $this->reflectionService->getClassSchema(ClassSchemaFixture::class);

        self::assertNotNull($classSchema);
        $this->assertInstanceOf(ClassSchema::class, $classSchema);
        self::assertSame(ClassSchemaFixture::class, $classSchema->getClassName());
    }

    /**
     * Test for https://jira.neos.io/browse/FLOW-316
     */
    #[Test]
    #[DoesNotPerformAssertions]
    public function classSchemaCanBeBuiltForAggregateRootsWithPlainOldPhpBaseClasses(): void
    {
        $this->reflectionService->getClassSchema(EntityExtendingPlainObject::class);
    }

    /**
     * @throws
     * @deprecated since 8.4
     */
    #[Test]
    public function theReflectionServiceCorrectlyBuildsMethodTagsValues(): void
    {
        $actual = $this->reflectionService->getMethodTagsValues(ClassSchemaFixture::class, 'setName');

        $expected = [
            'param' => [
                'string $name'
            ],
            'return' => [
                'void'
            ],
            'validate' => [
                '$name", type="foo1',
                '$name", type="foo2'
            ],
            'skipcsrfprotection' => []
        ];
        self::assertSame($expected, $actual);
    }

    #[Test]
    public function aggregateRootAssignmentsInHierarchiesAreCorrect(): void
    {
        self::assertEquals(SuperEntityRepository::class, $this->reflectionService->getClassSchema(SuperEntity::class)->getRepositoryClassName());
        self::assertEquals(SuperEntityRepository::class, $this->reflectionService->getClassSchema(SubEntity::class)->getRepositoryClassName());
        self::assertEquals(SubSubEntityRepository::class, $this->reflectionService->getClassSchema(SubSubEntity::class)->getRepositoryClassName());
        self::assertEquals(SubSubEntityRepository::class, $this->reflectionService->getClassSchema(SubSubSubEntity::class)->getRepositoryClassName());
    }

    #[Test]
    public function propertyTypesAreExpandedWithUseStatements(): void
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(AnnotatedClassWithUseStatements::class, 'reflectionService', 'var');
        $expected = [ReflectionService::class];
        self::assertSame($expected, $varTagValues);
    }

    #[Test]
    public function propertyTypesFromAbstractBaseClassAreExpandedWithRelativeNamespaces(): void
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(AnnotatedClassWithUseStatements::class, 'subSubEntity', 'var');
        $expected = [SubSubEntity::class];
        self::assertSame($expected, $varTagValues);
    }

    #[Test]
    public function propertyTypesFromAbstractBaseClassAreExpandedWithUseStatements(): void
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(AnnotatedClassWithUseStatements::class, 'superEntity', 'var');
        $expected = [SuperEntity::class];
        self::assertSame($expected, $varTagValues);
    }

    #[Test]
    public function propertyTypesFromSameSubpackageAreRetrievedCorrectly(): void
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(AnnotatedClassWithUseStatements::class, 'annotatedClass', 'var');
        $expected = [AnnotatedClass::class];
        self::assertSame($expected, $varTagValues);
    }

    #[Test]
    public function propertyTypesFromNestedSubpackageAreRetrievedCorrectly(): void
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(AnnotatedClassWithUseStatements::class, 'subEntity', 'var');
        $expected = [SubEntity::class];
        self::assertSame($expected, $varTagValues);
    }

    #[Test]
    public function domainModelPropertyTypesAreExpandedWithUseStatementsInClassSchema(): void
    {
        $classSchema = $this->reflectionService->getClassSchema(EntityWithUseStatements::class);
        $this->assertInstanceOf(ClassSchema::class, $classSchema);
        self::assertEquals(SubSubEntity::class, $classSchema->getProperty('subSubEntity')['type']);

        self::assertEquals(Persistence\Fixtures\SubEntity::class, $classSchema->getProperty('propertyFromOtherNamespace')['type']);
    }

    #[Test]
    public function methodParameterTypeExpansionWorksWithFullyQualifiedClassName(): void
    {
        $methodParameters = $this->reflectionService->getMethodParameters(EntityWithUseStatements::class, 'fullyQualifiedClassName');

        $expectedType = SubEntity::class;
        $actualType = $methodParameters['parameter']['type'];
        self::assertSame($expectedType, $actualType);
    }

    #[Test]
    public function methodParameterTypeExpansionWorksWithAliasedClassName(): void
    {
        $methodParameters = $this->reflectionService->getMethodParameters(EntityWithUseStatements::class, 'aliasedClassName');

        $expectedType = Persistence\Fixtures\SubEntity::class;
        $actualType = $methodParameters['parameter']['type'];
        self::assertSame($expectedType, $actualType);
    }

    #[Test]
    public function methodParameterTypeExpansionWorksWithRelativeClassName(): void
    {
        $methodParameters = $this->reflectionService->getMethodParameters(EntityWithUseStatements::class, 'relativeClassName');

        $expectedType = SubEntity::class;
        $actualType = $methodParameters['parameter']['type'];
        self::assertSame($expectedType, $actualType);
    }

    #[Test]
    public function methodParameterTypeExpansionWorksWithNullable(): void
    {
        $methodParameters = $this->reflectionService->getMethodParameters(EntityWithUseStatements::class, 'nullableClassName');

        $expectedType = SubEntity::class . '|null';
        $actualType = $methodParameters['parameter']['type'];
        self::assertSame($expectedType, $actualType);
    }

    /**
     * @see https://github.com/neos/flow-development-collection/issues/3423
     */
    #[Test]
    public function methodParameterTypeExpansionWorksWithParamsWithPartialAnnotationCoverage()
    {
        $methodParameters = $this->reflectionService->getMethodParameters(EntityWithUseStatements::class, 'multipleParamsWithPartialAnnotationCoverage');
        $expectedResult = [
            'param1' => [
                'position' => 0,
                'optional' => false,
                'type' => SubEntity::class,
                'class' => SubEntity::class,
                'array' => false,
                'byReference' => false,
                'allowsNull' => false,
                'defaultValue' => null,
                'scalarDeclaration' => false,
                'annotations' => [],
            ],
            'param2' => [
                'position' => 1,
                'optional' => false,
                'type' => 'array<' . SubSubEntity::class . '>',
                'class' => null,
                'array' => true,
                'byReference' => false,
                'allowsNull' => false,
                'defaultValue' => null,
                'scalarDeclaration' => false,
                'annotations' => [],
            ],
            'param3' => [
                'position' => 2,
                'optional' => true,
                'type' => SubSubSubEntity::class,
                'class' => SubSubSubEntity::class,
                'array' => false,
                'byReference' => false,
                'allowsNull' => true,
                'defaultValue' => null,
                'scalarDeclaration' => false,
                'annotations' => [],
            ],
        ];
        self::assertSame($expectedResult, $methodParameters);
    }

    #[Test]
    public function methodParameterTypeExpansionDoesNotModifySimpleTypes(): void
    {
        $methodParameters = $this->reflectionService->getMethodParameters(EntityWithUseStatements::class, 'simpleType');

        $expectedType = 'float';
        $actualType = $methodParameters['parameter']['type'];
        self::assertSame($expectedType, $actualType);
    }

    #[Test]
    public function integerPropertiesGetANormlizedType()
    {
        $className = DummyClassWithProperties::class;

        $varTagValues = $this->reflectionService->getPropertyTagValues($className, 'intProperty', 'var');
        self::assertCount(1, $varTagValues);
        self::assertEquals('integer', $varTagValues[0]);

        $varTagValues = $this->reflectionService->getPropertyTagValues($className, 'integerProperty', 'var');
        self::assertCount(1, $varTagValues);
        self::assertEquals('integer', $varTagValues[0]);
    }

    #[Test]
    public function booleanPropertiesGetANormlizedType(): void
    {
        $className = DummyClassWithProperties::class;

        $varTagValues = $this->reflectionService->getPropertyTagValues($className, 'boolProperty', 'var');
        self::assertCount(1, $varTagValues);
        self::assertEquals('boolean', $varTagValues[0]);

        $varTagValues = $this->reflectionService->getPropertyTagValues($className, 'booleanProperty', 'var');
        self::assertCount(1, $varTagValues);
        self::assertEquals('boolean', $varTagValues[0]);
    }

    #[Test]
    public function methodParametersGetNormalizedType(): void
    {
        $methodParameters = $this->reflectionService->getMethodParameters(AnnotatedClass::class, 'intAndIntegerParameters');

        foreach ($methodParameters as $methodParameter) {
            self::assertEquals('integer', $methodParameter['type']);
        }
    }

    #[Test]
    public function nullableMethodParametersWorkCorrectly(): void
    {
        $nativeNullableMethodParameters = $this->reflectionService->getMethodParameters(AnnotatedClass::class, 'nativeNullableParameter');
        $annotatedNullableMethodParameters = $this->reflectionService->getMethodParameters(AnnotatedClass::class, 'annotatedNullableParameter');
        $reverseAnnotatedNullableMethodParameters = $this->reflectionService->getMethodParameters(AnnotatedClass::class, 'reverseAnnotatedNullableParameter');
        $annotatedAndNativeNullableMethodParameters = $this->reflectionService->getMethodParameters(AnnotatedClass::class, 'annotatedAndNativeNullableParameter');

        self::assertTrue($nativeNullableMethodParameters['nullable']['allowsNull']);
        self::assertTrue($annotatedNullableMethodParameters['nullable']['allowsNull']);
        self::assertTrue($reverseAnnotatedNullableMethodParameters['nullable']['allowsNull']);
        self::assertTrue($annotatedAndNativeNullableMethodParameters['nullable']['allowsNull']);

        self::assertEquals(AnnotatedClass::class, $nativeNullableMethodParameters['nullable']['type']);
        self::assertEquals(AnnotatedClass::class . '|null', $annotatedNullableMethodParameters['nullable']['type']);
        self::assertEquals(AnnotatedClass::class . '|null', $reverseAnnotatedNullableMethodParameters['nullable']['type']);
        self::assertEquals(AnnotatedClass::class . '|null', $annotatedAndNativeNullableMethodParameters['nullable']['type']);
    }

    #[Test]
    public function scalarTypeHintsWorkCorrectly(): void
    {
        $methodWithTypeHintsParameters = $this->reflectionService->getMethodParameters(DummyClassWithTypeHints::class, 'methodWithScalarTypeHints');

        self::assertEquals('int', $methodWithTypeHintsParameters['integer']['type']);
        self::assertEquals('string', $methodWithTypeHintsParameters['string']['type']);
    }

    #[Test]
    public function arrayTypeHintsWorkCorrectly(): void
    {
        $methodWithTypeHintsParameters = $this->reflectionService->getMethodParameters(DummyClassWithTypeHints::class, 'methodWithArrayTypeHint');
        self::assertEquals('array', $methodWithTypeHintsParameters['array']['type']);
    }

    #[Test]
    public function annotatedArrayTypeHintsWorkCorrectly(): void
    {
        $methodWithTypeHintsParameters = $this->reflectionService->getMethodParameters(DummyClassWithTypeHints::class, 'methodWithArrayTypeHintAndAnnotation');
        self::assertEquals('array<string>', $methodWithTypeHintsParameters['array']['type']);
    }

    #[Test]
    public function unionReturnTypesWorkCorrectly(): void
    {
        $returnTypeA = $this->reflectionService->getMethodDeclaredReturnType(DummyClassWithUnionTypeHints::class, 'methodWithUnionReturnTypeA');
        $returnTypeB = $this->reflectionService->getMethodDeclaredReturnType(DummyClassWithUnionTypeHints::class, 'methodWithUnionReturnTypesB');
        $returnTypeC = $this->reflectionService->getMethodDeclaredReturnType(DummyClassWithUnionTypeHints::class, 'methodWithUnionReturnTypesC');

        self::assertSame('string|false', $returnTypeA);
        self::assertSame('\Neos\Flow\Tests\Functional\Reflection\Fixtures\PHP8\DummyClassWithUnionTypeHints|false', $returnTypeB);
        self::assertSame('?\Neos\Flow\Tests\Functional\Reflection\Fixtures\PHP8\DummyClassWithUnionTypeHints', $returnTypeC);
    }

    #[Test]
    public function disjunctiveNormalFormTypesWorkCorrectly(): void
    {
        $parameters = $this->reflectionService->getMethodParameters(DummyClassWithDisjunctiveNormalFormTypes::class, 'dnfTypesA');
        self::assertEquals(
            DummyReadonlyClass::class .
            '|(' .
            DummyClassWithTypeHints::class .
            '&' .
            DummyClassWithUnionTypeHints::class .
            ')|null',
            $parameters['theParameter']['type']
        );

        $parameters = $this->reflectionService->getMethodParameters(DummyClassWithDisjunctiveNormalFormTypes::class, 'dnfTypesB');
        self::assertEquals(
            DummyReadonlyClass::class .
            '|(' .
            DummyClassWithTypeHints::class .
            '&' .
            DummyClassWithUnionTypeHints::class .
            ')|(' .
            DummyClassWithTypeHints::class .
            '&' .
            DummyClassWithProperties::class .
            ')|null',
            $parameters['theParameter']['type']
        );
    }

    #[Test]
    public function readonlyClassIsDetectedCorrectly(): void
    {
        $isReadonly = $this->reflectionService->isClassReadOnly(DummyReadonlyClass::class);
        self::assertTrue($isReadonly);
    }
}
