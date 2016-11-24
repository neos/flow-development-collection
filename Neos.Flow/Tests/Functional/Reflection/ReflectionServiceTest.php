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
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Tests\Functional\Reflection;
use Neos\Flow\Tests\Functional\Persistence;

/**
 * Functional tests for the Reflection Service features
 */
class ReflectionServiceTest extends FunctionalTestCase
{
    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    public function setUp()
    {
        parent::setUp();
        $this->reflectionService = $this->objectManager->get(ReflectionService::class);
    }

    /**
     * @test
     */
    public function theReflectionServiceBuildsClassSchemataForEntities()
    {
        $classSchema = $this->reflectionService->getClassSchema(Reflection\Fixtures\ClassSchemaFixture::class);

        $this->assertNotNull($classSchema);
        $this->assertSame(Reflection\Fixtures\ClassSchemaFixture::class, $classSchema->getClassName());
    }

    /**
     * Test for https://jira.neos.io/browse/FLOW-316
     *
     * @test
     */
    public function classSchemaCanBeBuiltForAggregateRootsWithPlainOldPhpBaseClasses()
    {
        $this->reflectionService->getClassSchema(Reflection\Fixtures\Model\EntityExtendingPlainObject::class);

        // dummy assertion to suppress PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function theReflectionServiceCorrectlyBuildsMethodTagsValues()
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
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function aggregateRootAssignmentsInHierarchiesAreCorrect()
    {
        $this->assertEquals(Reflection\Fixtures\Repository\SuperEntityRepository::class, $this->reflectionService->getClassSchema(Reflection\Fixtures\Model\SuperEntity::class)->getRepositoryClassName());
        $this->assertEquals(Reflection\Fixtures\Repository\SuperEntityRepository::class, $this->reflectionService->getClassSchema(Reflection\Fixtures\Model\SubEntity::class)->getRepositoryClassName());
        $this->assertEquals(Reflection\Fixtures\Repository\SubSubEntityRepository::class, $this->reflectionService->getClassSchema(Reflection\Fixtures\Model\SubSubEntity::class)->getRepositoryClassName());
        $this->assertEquals(Reflection\Fixtures\Repository\SubSubEntityRepository::class, $this->reflectionService->getClassSchema(Reflection\Fixtures\Model\SubSubSubEntity::class)->getRepositoryClassName());
    }

    /**
     * @test
     */
    public function propertyTypesAreExpandedWithUseStatements()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'reflectionService', 'var');
        $expected = [ReflectionService::class];
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromAbstractBaseClassAreExpandedWithRelativeNamespaces()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'subSubEntity', 'var');
        $expected = [Reflection\Fixtures\Model\SubSubEntity::class];
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromAbstractBaseClassAreExpandedWithUseStatements()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'superEntity', 'var');
        $expected = [Reflection\Fixtures\Model\SuperEntity::class];
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromSameSubpackageAreRetrievedCorrectly()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'annotatedClass', 'var');
        $expected = [Reflection\Fixtures\AnnotatedClass::class];
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromNestedSubpackageAreRetrievedCorrectly()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'subEntity', 'var');
        $expected = [Reflection\Fixtures\Model\SubEntity::class];
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function domainModelPropertyTypesAreExpandedWithUseStatementsInClassSchema()
    {
        $classSchema = $this->reflectionService->getClassSchema(Reflection\Fixtures\Model\EntityWithUseStatements::class);
        $this->assertEquals(Reflection\Fixtures\Model\SubSubEntity::class, $classSchema->getProperty('subSubEntity')['type']);

        $this->assertEquals(Persistence\Fixtures\SubEntity::class, $classSchema->getProperty('propertyFromOtherNamespace')['type']);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionWorksWithFullyQualifiedClassName()
    {
        $methodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\Model\EntityWithUseStatements::class, 'fullyQualifiedClassName');

        $expectedType = Reflection\Fixtures\Model\SubEntity::class;
        $actualType = $methodParameters['parameter']['type'];
        $this->assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionWorksWithAliasedClassName()
    {
        $methodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\Model\EntityWithUseStatements::class, 'aliasedClassName');

        $expectedType = Persistence\Fixtures\SubEntity::class;
        $actualType = $methodParameters['parameter']['type'];
        $this->assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionWorksWithRelativeClassName()
    {
        $methodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\Model\EntityWithUseStatements::class, 'relativeClassName');

        $expectedType = Reflection\Fixtures\Model\SubEntity::class;
        $actualType = $methodParameters['parameter']['type'];
        $this->assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionDoesNotModifySimpleTypes()
    {
        $methodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\Model\EntityWithUseStatements::class, 'simpleType');

        $expectedType = 'float';
        $actualType = $methodParameters['parameter']['type'];
        $this->assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function integerPropertiesGetANormlizedType()
    {
        $className = Reflection\Fixtures\DummyClassWithProperties::class;

        $varTagValues = $this->reflectionService->getPropertyTagValues($className, 'intProperty', 'var');
        $this->assertCount(1, $varTagValues);
        $this->assertEquals('integer', $varTagValues[0]);

        $varTagValues = $this->reflectionService->getPropertyTagValues($className, 'integerProperty', 'var');
        $this->assertCount(1, $varTagValues);
        $this->assertEquals('integer', $varTagValues[0]);
    }

    /**
     * @test
     */
    public function booleanPropertiesGetANormlizedType()
    {
        $className = Reflection\Fixtures\DummyClassWithProperties::class;

        $varTagValues = $this->reflectionService->getPropertyTagValues($className, 'boolProperty', 'var');
        $this->assertCount(1, $varTagValues);
        $this->assertEquals('boolean', $varTagValues[0]);

        $varTagValues = $this->reflectionService->getPropertyTagValues($className, 'booleanProperty', 'var');
        $this->assertCount(1, $varTagValues);
        $this->assertEquals('boolean', $varTagValues[0]);
    }

    /**
     * @test
     */
    public function methodParametersGetNormalizedType()
    {
        $methodParameters = $this->reflectionService->getMethodParameters(Reflection\Fixtures\AnnotatedClass::class, 'intAndIntegerParameters');

        foreach ($methodParameters as $methodParameter) {
            $this->assertEquals('integer', $methodParameter['type']);
        }
    }
}
