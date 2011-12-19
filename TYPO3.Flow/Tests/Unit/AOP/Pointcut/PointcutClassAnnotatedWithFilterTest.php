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
 * Testcase for the Pointcut Class-Annotated-With Filter
 *
 */
class PointcutClassAnnotatedWithFilterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenAnnotation() {
		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('getClassAnnotations'), array(), '', FALSE, TRUE);
		$mockReflectionService->expects($this->any())->method('getClassAnnotations')->with('Acme\Some\Class', 'Acme\Some\Annotation')->will($this->onConsecutiveCalls(array('SomeAnnotation'), array()));

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassAnnotatedWithFilter('Acme\Some\Annotation');
		$filter->injectReflectionService($mockReflectionService);

		$this->assertTrue($filter->matches('Acme\Some\Class', 'foo', 'Acme\Some\Other\Class', 1234));
		$this->assertFalse($filter->matches('Acme\Some\Class', 'foo', 'Acme\Some\Other\Class', 1234));
	}
}
?>