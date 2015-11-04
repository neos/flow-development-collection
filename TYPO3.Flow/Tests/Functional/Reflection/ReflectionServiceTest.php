<?php
namespace TYPO3\Flow\Tests\Functional\Reflection;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the Reflection Service features
 */
class ReflectionServiceTest extends FunctionalTestCase
{
    /**
     * @var \TYPO3\Flow\Reflection\ReflectionService
     */
    protected $reflectionService;

    public function setUp()
    {
        parent::setUp();
        $this->reflectionService = $this->objectManager->get(\TYPO3\Flow\Reflection\ReflectionService::class);
    }

    /**
     * @test
     */
    public function theReflectionServiceBuildsClassSchemataForEntities()
    {
        $classSchema = $this->reflectionService->getClassSchema(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture::class);

        $this->assertNotNull($classSchema);
        $this->assertSame(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture::class, $classSchema->getClassName());
    }

    /**
     * Test for https://jira.neos.io/browse/FLOW-316
     *
     * @test
     */
    public function classSchemaCanBeBuiltForAggregateRootsWithPlainOldPhpBaseClasses()
    {
        $this->reflectionService->getClassSchema(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityExtendingPlainObject::class);

        // dummy assertion to suppress PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function theReflectionServiceCorrectlyBuildsMethodTagsValues()
    {
        $actual = $this->reflectionService->getMethodTagsValues(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture::class, 'setName');

        $expected = array(
            'param' => array(
                'string $name'
            ),
            'return' => array(
                'void'
            ),
            'validate' => array(
                '$name", type="foo1',
                '$name", type="foo2'
            ),
            'skipcsrfprotection' => array()
        );
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function aggregateRootAssignmentsInHierarchiesAreCorrect()
    {
        $this->assertEquals(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Repository\SuperEntityRepository::class, $this->reflectionService->getClassSchema(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SuperEntity::class)->getRepositoryClassName());
        $this->assertEquals(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Repository\SuperEntityRepository::class, $this->reflectionService->getClassSchema(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity::class)->getRepositoryClassName());
        $this->assertEquals(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Repository\SubSubEntityRepository::class, $this->reflectionService->getClassSchema(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubEntity::class)->getRepositoryClassName());
        $this->assertEquals(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Repository\SubSubEntityRepository::class, $this->reflectionService->getClassSchema(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubSubEntity::class)->getRepositoryClassName());
    }

    /**
     * @test
     */
    public function propertyTypesAreExpandedWithUseStatements()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'reflectionService', 'var');
        $expected = array(\TYPO3\Flow\Reflection\ReflectionService::class);
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromAbstractBaseClassAreExpandedWithRelativeNamespaces()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'subSubEntity', 'var');
        $expected = array(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubEntity::class);
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromAbstractBaseClassAreExpandedWithUseStatements()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'superEntity', 'var');
        $expected = array(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SuperEntity::class);
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromSameSubpackageAreRetrievedCorrectly()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'annotatedClass', 'var');
        $expected = array(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClass::class);
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromNestedSubpackageAreRetrievedCorrectly()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements::class, 'subEntity', 'var');
        $expected = array(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity::class);
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function domainModelPropertyTypesAreExpandedWithUseStatementsInClassSchema()
    {
        $classSchema = $this->reflectionService->getClassSchema(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements::class);
        $this->assertEquals(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubEntity::class, $classSchema->getProperty('subSubEntity')['type']);

        $this->assertEquals(\TYPO3\Flow\Tests\Functional\Persistence\Fixtures\SubEntity::class, $classSchema->getProperty('propertyFromOtherNamespace')['type']);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionWorksWithFullyQualifiedClassName()
    {
        $methodParameters = $this->reflectionService->getMethodParameters(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements::class, 'fullyQualifiedClassName');

        $expectedType = \TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity::class;
        $actualType = $methodParameters['parameter']['type'];
        $this->assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionWorksWithAliasedClassName()
    {
        $methodParameters = $this->reflectionService->getMethodParameters(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements::class, 'aliasedClassName');

        $expectedType = \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\SubEntity::class;
        $actualType = $methodParameters['parameter']['type'];
        $this->assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionWorksWithRelativeClassName()
    {
        $methodParameters = $this->reflectionService->getMethodParameters(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements::class, 'relativeClassName');

        $expectedType = \TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity::class;
        $actualType = $methodParameters['parameter']['type'];
        $this->assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionDoesNotModifySimpleTypes()
    {
        $methodParameters = $this->reflectionService->getMethodParameters(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements::class, 'simpleType');

        $expectedType = 'float';
        $actualType = $methodParameters['parameter']['type'];
        $this->assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function integerPropertiesGetANormlizedType()
    {
        $className = \TYPO3\Flow\Tests\Functional\Reflection\Fixtures\DummyClassWithProperties::class;

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
        $className = \TYPO3\Flow\Tests\Functional\Reflection\Fixtures\DummyClassWithProperties::class;

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
        $methodParameters = $this->reflectionService->getMethodParameters(\TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClass::class, 'intAndIntegerParameters');

        foreach ($methodParameters as $methodParameter) {
            $this->assertEquals('integer', $methodParameter['type']);
        }
    }
}
