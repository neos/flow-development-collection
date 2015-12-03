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
        $this->reflectionService = $this->objectManager->get('TYPO3\Flow\Reflection\ReflectionService');
    }

    /**
     * @test
     */
    public function theReflectionServiceBuildsClassSchemataForEntities()
    {
        $classSchema = $this->reflectionService->getClassSchema('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture');

        $this->assertNotNull($classSchema);
        $this->assertSame('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture', $classSchema->getClassName());
    }

    /**
     * @test
     */
    public function theReflectionServiceCorrectlyBuildsMethodTagsValues()
    {
        $actual = $this->reflectionService->getMethodTagsValues('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture', 'setName');

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
        $this->assertEquals('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Repository\SuperEntityRepository', $this->reflectionService->getClassSchema('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SuperEntity')->getRepositoryClassName());
        $this->assertEquals('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Repository\SuperEntityRepository', $this->reflectionService->getClassSchema('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity')->getRepositoryClassName());
        $this->assertEquals('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Repository\SubSubEntityRepository', $this->reflectionService->getClassSchema('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubEntity')->getRepositoryClassName());
        $this->assertEquals('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Repository\SubSubEntityRepository', $this->reflectionService->getClassSchema('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubSubEntity')->getRepositoryClassName());
    }

    /**
     * @test
     */
    public function propertyTypesAreExpandedWithUseStatements()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements', 'reflectionService', 'var');
        $expected = array('TYPO3\Flow\Reflection\ReflectionService');
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromAbstractBaseClassAreExpandedWithRelativeNamespaces()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements', 'subSubEntity', 'var');
        $expected = array('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubEntity');
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromAbstractBaseClassAreExpandedWithUseStatements()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements', 'superEntity', 'var');
        $expected = array('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SuperEntity');
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromSameSubpackageAreRetrievedCorrectly()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements', 'annotatedClass', 'var');
        $expected = array('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClass');
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function propertyTypesFromNestedSubpackageAreRetrievedCorrectly()
    {
        $varTagValues = $this->reflectionService->getPropertyTagValues('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements', 'subEntity', 'var');
        $expected = array('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity');
        $this->assertSame($expected, $varTagValues);
    }

    /**
     * @test
     */
    public function domainModelPropertyTypesAreExpandedWithUseStatementsInClassSchema()
    {
        $classSchema = $this->reflectionService->getClassSchema('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements');

        $classProperty = $classSchema->getProperty('subSubEntity');
        $this->assertEquals('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubEntity', $classProperty['type']);

        $otherClassProperty = $classSchema->getProperty('propertyFromOtherNamespace');
        $this->assertEquals('TYPO3\Flow\Tests\Functional\Persistence\Fixtures\SubEntity', $otherClassProperty['type']);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionWorksWithFullyQualifiedClassName()
    {
        $methodParameters = $this->reflectionService->getMethodParameters('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements', 'fullyQualifiedClassName');

        $expectedType = 'TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity';
        $actualType = $methodParameters['parameter']['type'];
        $this->assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionWorksWithAliasedClassName()
    {
        $methodParameters = $this->reflectionService->getMethodParameters('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements', 'aliasedClassName');

        $expectedType = 'TYPO3\Flow\Tests\Functional\Persistence\Fixtures\SubEntity';
        $actualType = $methodParameters['parameter']['type'];
        $this->assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionWorksWithRelativeClassName()
    {
        $methodParameters = $this->reflectionService->getMethodParameters('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements', 'relativeClassName');

        $expectedType = 'TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity';
        $actualType = $methodParameters['parameter']['type'];
        $this->assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function methodParameterTypeExpansionDoesNotModifySimpleTypes()
    {
        $methodParameters = $this->reflectionService->getMethodParameters('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements', 'simpleType');

        $expectedType = 'float';
        $actualType = $methodParameters['parameter']['type'];
        $this->assertSame($expectedType, $actualType);
    }

    /**
     * @test
     */
    public function integerPropertiesGetANormlizedType()
    {
        $className = 'TYPO3\Flow\Tests\Functional\Reflection\Fixtures\DummyClassWithProperties';

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
        $className = 'TYPO3\Flow\Tests\Functional\Reflection\Fixtures\DummyClassWithProperties';

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
        $methodParameters = $this->reflectionService->getMethodParameters('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClass', 'intAndIntegerParameters');

        foreach ($methodParameters as $methodParameter) {
            $this->assertEquals('integer', $methodParameter['type']);
        }
    }
}
