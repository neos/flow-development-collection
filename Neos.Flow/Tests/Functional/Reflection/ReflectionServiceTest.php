<?php
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

use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\Functional\Persistence;
use Neos\Flow\Tests\Functional\Reflection;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the Reflection Service features
 */
class ReflectionServiceTest extends FunctionalTestCase
{
    protected ReflectionService $reflectionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reflectionService = $this->objectManager->get(ReflectionService::class);
    }

    /**
     * @test
     */
    public function theReflectionServiceBuildsClassSchemataForEntities(): void
    {
        $classSchema = $this->reflectionService->getClassSchema(Reflection\Fixtures\ClassSchemaFixture::class);

        self::assertNotNull($classSchema);
        self::assertSame(Reflection\Fixtures\ClassSchemaFixture::class, $classSchema->getClassName());
    }

    /**
     * Test for https://jira.neos.io/browse/FLOW-316
     *
     * @test
     * @doesNotPerformAssertions
     */
    public function classSchemaCanBeBuiltForAggregateRootsWithPlainOldPhpBaseClasses(): void
    {
        $this->reflectionService->getClassSchema(Reflection\Fixtures\Model\EntityExtendingPlainObject::class);
    }

    /**
     * @test
     * @throws
     */
    public function theReflectionServiceCorrectlyBuildsMethodTagsValues(): void
    {
        $actual = $this->reflectionService->getMethodTagsValues(Reflection\Fixtures\ClassSchemaFixture::class, 'setName');

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

    /**
     * @test
     */
    public function aggregateRootAssignmentsInHierarchiesAreCorrect(): void
    {
        self::assertEquals(Reflection\Fixtures\Repository\SuperEntityRepository::class, $this->reflectionService->getClassSchema(Reflection\Fixtures\Model\SuperEntity::class)->getRepositoryClassName());
        self::assertEquals(Reflection\Fixtures\Repository\SuperEntityRepository::class, $this->reflectionService->getClassSchema(Reflection\Fixtures\Model\SubEntity::class)->getRepositoryClassName());
        self::assertEquals(Reflection\Fixtures\Repository\SubSubEntityRepository::class, $this->reflectionService->getClassSchema(Reflection\Fixtures\Model\SubSubEntity::class)->getRepositoryClassName());
        self::assertEquals(Reflection\Fixtures\Repository\SubSubEntityRepository::class, $this->reflectionService->getClassSchema(Reflection\Fixtures\Model\SubSubSubEntity::class)->getRepositoryClassName());
    }

    /**
     * @test
     */
    public function propertyTypesAreExpandedWithUseStatements(): void
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'reflectionService', 'var');
        $expected = [ReflectionService::class];
        self::assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromAbstractBaseClassAreExpandedWithRelativeNamespaces(): void
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'subSubEntity', 'var');
        $expected = [Reflection\Fixtures\Model\SubSubEntity::class];
        self::assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromAbstractBaseClassAreExpandedWithUseStatements(): void
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'superEntity', 'var');
        $expected = [Reflection\Fixtures\Model\SuperEntity::class];
        self::assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromSameSubpackageAreRetrievedCorrectly(): void
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'annotatedClass', 'var');
        $expected = [Reflection\Fixtures\AnnotatedClass::class];
        self::assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromNestedSubpackageAreRetrievedCorrectly(): void
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'subEntity', 'var');
        $expected = [Reflection\Fixtures\Model\SubEntity::class];
        self::assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function domainModelPropertyTypesAreExpandedWithUseStatementsInClassSchema(): void
    {
        $classSchema = $this->reflectionService->getClassSchema(Reflection\Fixtures\Model\EntityWithUseStatements::class);
        self::assertEquals(Reflection\Fixtures\Model\SubSubEntity::class, $classSchema->getProperty('subSubEntity')['type']);

        self::assertEquals(Persistence\Fixtures\SubEntity::class, $classSchema->getProperty('propertyFromOtherNamespace')['type']);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionWorksWithFullyQualifiedClassName(): void
    {
        $methodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\Model\EntityWithUseStatements::class, 'fullyQualifiedClassName');

        $expectedType = Reflection\Fixtures\Model\SubEntity::class;
        $actualType = $methodParameters['parameter']['type'];
        self::assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionWorksWithAliasedClassName(): void
    {
        $methodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\Model\EntityWithUseStatements::class, 'aliasedClassName');

        $expectedType = Persistence\Fixtures\SubEntity::class;
        $actualType = $methodParameters['parameter']['type'];
        self::assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionWorksWithRelativeClassName(): void
    {
        $methodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\Model\EntityWithUseStatements::class, 'relativeClassName');

        $expectedType = Reflection\Fixtures\Model\SubEntity::class;
        $actualType = $methodParameters['parameter']['type'];
        self::assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionWorksWithNullable(): void
    {
        $methodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\Model\EntityWithUseStatements::class, 'nullableClassName');

        $expectedType = Reflection\Fixtures\Model\SubEntity::class . '|null';
        $actualType = $methodParameters['parameter']['type'];
        self::assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionDoesNotModifySimpleTypes(): void
    {
        $methodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\Model\EntityWithUseStatements::class, 'simpleType');

        $expectedType = 'float';
        $actualType = $methodParameters['parameter']['type'];
        self::assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function integerPropertiesGetANormlizedType()
    {
        $className = Reflection\Fixtures\DummyClassWithProperties::class;

        $varTagValues = $this->reflectionService->getPropertyTagValues($className, 'intProperty', 'var');
        self::assertCount(1, $varTagValues);
        self::assertEquals('integer', $varTagValues[0]);

        $varTagValues = $this->reflectionService->getPropertyTagValues($className, 'integerProperty', 'var');
        self::assertCount(1, $varTagValues);
        self::assertEquals('integer', $varTagValues[0]);
    }

    /**
     * @test
     */
    public function booleanPropertiesGetANormlizedType(): void
    {
        $className = Reflection\Fixtures\DummyClassWithProperties::class;

        $varTagValues = $this->reflectionService->getPropertyTagValues($className, 'boolProperty', 'var');
        self::assertCount(1, $varTagValues);
        self::assertEquals('boolean', $varTagValues[0]);

        $varTagValues = $this->reflectionService->getPropertyTagValues($className, 'booleanProperty', 'var');
        self::assertCount(1, $varTagValues);
        self::assertEquals('boolean', $varTagValues[0]);
    }

    /**
     * @test
     */
    public function methodParametersGetNormalizedType(): void
    {
        $methodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\AnnotatedClass::class, 'intAndIntegerParameters');

        foreach ($methodParameters as $methodParameter) {
            self::assertEquals('integer', $methodParameter['type']);
        }
    }

    /**
     * @test
     */
    public function nullableMethodParametersWorkCorrectly(): void
    {
        $nativeNullableMethodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\AnnotatedClass::class, 'nativeNullableParameter');
        $annotatedNullableMethodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\AnnotatedClass::class, 'annotatedNullableParameter');
        $reverseAnnotatedNullableMethodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\AnnotatedClass::class, 'reverseAnnotatedNullableParameter');
        $annotatedAndNativeNullableMethodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\AnnotatedClass::class, 'annotatedAndNativeNullableParameter');

        self::assertTrue($nativeNullableMethodParameters['nullable']['allowsNull']);
        self::assertTrue($annotatedNullableMethodParameters['nullable']['allowsNull']);
        self::assertTrue($reverseAnnotatedNullableMethodParameters['nullable']['allowsNull']);
        self::assertTrue($annotatedAndNativeNullableMethodParameters['nullable']['allowsNull']);

        self::assertEquals(Reflection\Fixtures\AnnotatedClass::class, $nativeNullableMethodParameters['nullable']['type']);
        self::assertEquals(Reflection\Fixtures\AnnotatedClass::class . '|null', $annotatedNullableMethodParameters['nullable']['type']);
        self::assertEquals(Reflection\Fixtures\AnnotatedClass::class . '|null', $reverseAnnotatedNullableMethodParameters['nullable']['type']);
        self::assertEquals(Reflection\Fixtures\AnnotatedClass::class . '|null', $annotatedAndNativeNullableMethodParameters['nullable']['type']);
    }

    /**
     * @test
     */
    public function scalarTypeHintsWorkCorrectly(): void
    {
        $methodWithTypeHintsParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\DummyClassWithTypeHints::class, 'methodWithScalarTypeHints');

        self::assertEquals('int', $methodWithTypeHintsParameters['integer']['type']);
        self::assertEquals('string', $methodWithTypeHintsParameters['string']['type']);
    }

    /**
     * @test
     */
    public function arrayTypeHintsWorkCorrectly(): void
    {
        $methodWithTypeHintsParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\DummyClassWithTypeHints::class, 'methodWithArrayTypeHint');
        self::assertEquals('array', $methodWithTypeHintsParameters['array']['type']);
    }

    /**
     * @test
     */
    public function annotatedArrayTypeHintsWorkCorrectly(): void
    {
        $methodWithTypeHintsParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\DummyClassWithTypeHints::class, 'methodWithArrayTypeHintAndAnnotation');
        self::assertEquals('array<string>', $methodWithTypeHintsParameters['array']['type']);
    }

    /**
     * @test
     */
    public function unionReturnTypesWorkCorrectly(): void
    {
        $returnTypeA = $this->reflectionService->getMethodDeclaredReturnType(Reflection\Fixtures\PHP8\DummyClassWithUnionTypeHints::class, 'methodWithUnionReturnTypeA');
        $returnTypeB = $this->reflectionService->getMethodDeclaredReturnType(Reflection\Fixtures\PHP8\DummyClassWithUnionTypeHints::class, 'methodWithUnionReturnTypesB');
        $returnTypeC = $this->reflectionService->getMethodDeclaredReturnType(Reflection\Fixtures\PHP8\DummyClassWithUnionTypeHints::class, 'methodWithUnionReturnTypesC');

        self::assertEquals('string|false', $returnTypeA);
        self::assertEquals('\Neos\Flow\Tests\Functional\Reflection\Fixtures\PHP8\DummyClassWithUnionTypeHints|false', $returnTypeB);
        self::assertEquals('?\Neos\Flow\Tests\Functional\Reflection\Fixtures\PHP8\DummyClassWithUnionTypeHints', $returnTypeC);
    }

    /**
     * @test
     */
    public function readonlyClassIsDetectedCorrectly(): void
    {
        $isReadonly = $this->reflectionService->isClassReadOnly(Reflection\Fixtures\DummyReadonlyClass::class);
        self::assertTrue($isReadonly);
    }
}
