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
 * Testcase for the Pointcut Class Type Filter
 *
 */
class PointcutClassTypeFilterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function reduceTargetClassNamesFiltersAllClassesNotImplementingTheGivenInterface() {
		$interfaceName = uniqid('someTestInterface');
		eval ('interface ' . $interfaceName . ' {}');

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
		$mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with($interfaceName)->will($this->returnValue(array('TestPackage\Subpackage\Class1','TestPackage\Subpackage\SubSubPackage\Class3','SomeMoreClass')));

		$classTypeFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassTypeFilter($interfaceName);
		$classTypeFilter->injectReflectionService($mockReflectionService);

		$expectedClassNames = array(
			'TestPackage\Subpackage\Class1',
			'TestPackage\Subpackage\SubSubPackage\Class3'
		);
		sort($expectedClassNames);
		$expectedClassNamesIndex = new \TYPO3\FLOW3\AOP\Builder\ClassNameIndex();
		$expectedClassNamesIndex->setClassNames($expectedClassNames);

		$result = $classTypeFilter->reduceTargetClassNames($availableClassNamesIndex);

		$this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
	}

	/**
	 * @test
	 */
	public function reduceTargetClassNamesFiltersAllClassesExceptTheClassItselfAndAllItsSubclasses() {
		$testClassName = uniqid('someTestInterface');
		eval ('class ' . $testClassName . ' {}');

		$availableClassNames = array(
			$testClassName,
			'TestPackage\Subpackage\Class1',
			'TestPackage\Class2',
			'TestPackage\Subpackage\SubSubPackage\Class3',
			'TestPackage\Subpackage2\Class4'
		);
		sort($availableClassNames);
		$availableClassNamesIndex = new \TYPO3\FLOW3\AOP\Builder\ClassNameIndex();
		$availableClassNamesIndex->setClassNames($availableClassNames);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getAllSubClassNamesForClass')->with($testClassName)->will($this->returnValue(array('TestPackage\Subpackage\Class1','TestPackage\Subpackage\SubSubPackage\Class3','SomeMoreClass')));

		$classTypeFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassTypeFilter($testClassName);
		$classTypeFilter->injectReflectionService($mockReflectionService);

		$expectedClassNames = array(
			$testClassName,
			'TestPackage\Subpackage\Class1',
			'TestPackage\Subpackage\SubSubPackage\Class3'
		);
		sort($expectedClassNames);
		$expectedClassNamesIndex = new \TYPO3\FLOW3\AOP\Builder\ClassNameIndex();
		$expectedClassNamesIndex->setClassNames($expectedClassNames);

		$result = $classTypeFilter->reduceTargetClassNames($availableClassNamesIndex);

		$this->assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
	}
}
?>