<?php
namespace TYPO3\FLOW3\Tests\Unit\AOP\Pointcut;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Pointcut Method-Annotated-With Filter
 *
 */
class PointcutMethodAnnotatedWithFilterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenAnnotation() {
		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('getMethodAnnotations'), array(), '', FALSE, TRUE);
		$mockReflectionService->expects($this->any())->method('getMethodAnnotations')->with(__CLASS__, __FUNCTION__, 'Acme\Some\Annotation')->will($this->onConsecutiveCalls(array('SomeAnnotation'), array()));

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutMethodAnnotatedWithFilter('Acme\Some\Annotation');
		$filter->injectReflectionService($mockReflectionService);

		$this->assertTrue($filter->matches(__CLASS__, __FUNCTION__, __CLASS__, 1234));
		$this->assertFalse($filter->matches(__CLASS__, __FUNCTION__, __CLASS__, 1234));
	}

	/**
	 * @test
	 */
	public function matchesReturnsFalseIfMethodDoesNotExistOrDeclardingClassHasNotBeenSpecified() {
		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE, TRUE);

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutMethodAnnotatedWithFilter('Acme\Some\Annotation');
		$filter->injectReflectionService($mockReflectionService);

		$this->assertFalse($filter->matches(__CLASS__, __FUNCTION__, NULL, 1234));
		$this->assertFalse($filter->matches(__CLASS__, 'foo', __CLASS__, 1234));
	}

	/**
	 * @test
	 */
	public function reduceTargetClassNamesFiltersAllClassesNotHavingAMethodWithTheGivenAnnotation() {
		$availableClassNames = array(
			'TestPackage\Subpackage\Class1',
			'TestPackage\Class2',
			'TestPackage\Subpackage\SubSubPackage\Class3',
			'TestPackage\Subpackage2\Class4'
		);
		sort($availableClassNames);
		$availableClassNamesIndex = new \TYPO3\FLOW3\AOP\Builder\ClassNameIndex();
		$availableClassNamesIndex->setClassNames($availableClassNames);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassesContainingMethodsAnnotatedWith')->with('SomeAnnotationClass')->will($this->returnValue(array('TestPackage\Subpackage\Class1','TestPackage\Subpackage\SubSubPackage\Class3','SomeMoreClass')));

		$methodAnnotatedWithFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutMethodAnnotatedWithFilter('SomeAnnotationClass');
		$methodAnnotatedWithFilter->injectReflectionService($mockReflectionService);

		$expectedClassNames = array(
			'TestPackage\Subpackage\Class1',
			'TestPackage\Subpackage\SubSubPackage\Class3'
		);
		sort($expectedClassNames);
		$expectedClassNamesIndex = new \TYPO3\FLOW3\AOP\Builder\ClassNameIndex();
		$expectedClassNamesIndex->setClassNames($expectedClassNames);

		$result = $methodAnnotatedWithFilter->reduceTargetClassNames($availableClassNamesIndex);

		$this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
	}
}
?>