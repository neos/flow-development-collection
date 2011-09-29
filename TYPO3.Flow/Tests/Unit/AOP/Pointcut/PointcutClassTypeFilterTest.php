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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesTellsIfTheOneOfTheInterfaceNamesTheClassImplementsMatchesTheGivenRegularExpression() {
		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('getInterfaceNamesImplementedByClass'), array(), '', FALSE, TRUE);
		$mockReflectionService->expects($this->any())->method('getInterfaceNamesImplementedByClass')->with('Foo')->will($this->returnValue(array('Bar', 'Baz', 'Fu', 'Uta')));

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassTypeFilter('.*ar');
		$filter->injectReflectionService($mockReflectionService);
		$this->assertTrue($filter->matches('Foo', '', '', 1));

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassTypeFilter('Fu');
		$filter->injectReflectionService($mockReflectionService);
		$this->assertTrue($filter->matches('Foo', '', '', 1));

		$filter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassTypeFilter('Rob');
		$filter->injectReflectionService($mockReflectionService);
		$this->assertFalse($filter->matches('Foo', '', '', 1));
	}
}
?>