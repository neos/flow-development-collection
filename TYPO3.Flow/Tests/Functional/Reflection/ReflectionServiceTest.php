<?php
namespace TYPO3\Flow\Tests\Functional\Reflection;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the Reflection Service features
 */
class ReflectionServiceTest extends FunctionalTestCase {

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	public function setUp() {
		parent::setUp();
		$this->reflectionService = $this->objectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	}

	/**
	 * @test
	 */
	public function theReflectionServiceBuildsClassSchemataForEntities() {
		$classSchema = $this->reflectionService->getClassSchema('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture');

		$this->assertNotNull($classSchema);
		$this->assertSame('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture', $classSchema->getClassName());
	}

	/**
	 * @test
	 */
	public function theReflectionServiceCorrectlyBuildsMethodTagsValues() {
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
	public function aggregateRootAssignmentsInHierarchiesAreCorrect() {
		$this->assertEquals('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Repository\SuperEntityRepository', $this->reflectionService->getClassSchema('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SuperEntity')->getRepositoryClassName());
		$this->assertEquals('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Repository\SuperEntityRepository', $this->reflectionService->getClassSchema('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity')->getRepositoryClassName());
		$this->assertEquals('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Repository\SubSubEntityRepository', $this->reflectionService->getClassSchema('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubEntity')->getRepositoryClassName());
		$this->assertEquals('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Repository\SubSubEntityRepository', $this->reflectionService->getClassSchema('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubSubEntity')->getRepositoryClassName());
	}

	/**
	 * @test
	 */
	public function propertyTypesAreExpandedWithUseStatements() {
		$varTagValues = $this->reflectionService->getPropertyTagValues('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements', 'reflectionService', 'var');
		$expected = array('TYPO3\Flow\Reflection\ReflectionService');
		$this->assertSame($expected, $varTagValues);
	}

	/**
	 * @test
	 */
	public function propertyTypesFromAbstractBaseClassAreExpandedWithRelativeNamespaces() {
		$varTagValues = $this->reflectionService->getPropertyTagValues('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements', 'subSubEntity', 'var');
		$expected = array('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubEntity');
		$this->assertSame($expected, $varTagValues);
	}

	/**
	 * @test
	 */
	public function propertyTypesFromAbstractBaseClassAreExpandedWithUseStatements() {
		$varTagValues = $this->reflectionService->getPropertyTagValues('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements', 'superEntity', 'var');
		$expected = array('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SuperEntity');
		$this->assertSame($expected, $varTagValues);
	}

	/**
	 * @test
	 */
	public function propertyTypesFromSameSubpackageAreRetrievedCorrectly() {
		$varTagValues = $this->reflectionService->getPropertyTagValues('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements', 'annotatedClass', 'var');
		$expected = array('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClass');
		$this->assertSame($expected, $varTagValues);
	}

	/**
	 * @test
	 */
	public function propertyTypesFromNestedSubpackageAreRetrievedCorrectly() {
		$varTagValues = $this->reflectionService->getPropertyTagValues('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\AnnotatedClassWithUseStatements', 'subEntity', 'var');
		$expected = array('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity');
		$this->assertSame($expected, $varTagValues);
	}

	/**
	 * @test
	 */
	public function domainModelPropertyTypesAreExpandedWithUseStatementsInClassSchema() {
		$classSchema = $this->reflectionService->getClassSchema('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements');
		$expected = array(
			'type' => 'TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubSubEntity',
			'elementType' => NULL,
			'lazy' => FALSE
		);
		$this->assertEquals($expected, $classSchema->getProperty('subSubEntity'));

		$expected = array(
			'type' => 'TYPO3\Flow\Tests\Functional\Persistence\Fixtures\SubEntity',
			'elementType' => NULL,
			'lazy' => FALSE
		);
		$this->assertEquals($expected, $classSchema->getProperty('propertyFromOtherNamespace'));
	}

	/**
	 * @test
	 */
	public function methodParameterTypeExpansionWorksWithFullyQualifiedClassName() {
		$methodParameters = $this->reflectionService->getMethodParameters('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements', 'fullyQualifiedClassName');

		$expectedType = 'TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity';
		$actualType = $methodParameters['parameter']['type'];
		$this->assertSame($expectedType, $actualType);
	}

	/**
	 * @test
	 */
	public function methodParameterTypeExpansionWorksWithAliasedClassName() {
		$methodParameters = $this->reflectionService->getMethodParameters('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements', 'aliasedClassName');

		$expectedType = 'TYPO3\Flow\Tests\Functional\Persistence\Fixtures\SubEntity';
		$actualType = $methodParameters['parameter']['type'];
		$this->assertSame($expectedType, $actualType);
	}

	/**
	 * @test
	 */
	public function methodParameterTypeExpansionWorksWithRelativeClassName() {
		$methodParameters = $this->reflectionService->getMethodParameters('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements', 'relativeClassName');

		$expectedType = 'TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity';
		$actualType = $methodParameters['parameter']['type'];
		$this->assertSame($expectedType, $actualType);
	}

	/**
	 * @test
	 */
	public function methodParameterTypeExpansionDoesNotModifySimpleTypes() {
		$methodParameters = $this->reflectionService->getMethodParameters('TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\EntityWithUseStatements', 'simpleType');

		$expectedType = 'float';
		$actualType = $methodParameters['parameter']['type'];
		$this->assertSame($expectedType, $actualType);
	}
}
