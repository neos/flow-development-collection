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

require_once (FLOW3_PATH_FLOW3 . 'Tests/Unit/AOP/Fixtures/ClassTaggedWithSomething.php');

/**
 * Testcase for the Pointcut Class-Tagged-With Filter
 *
 */
class PointcutClassTaggedWithFilterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenTag() {
		$className = 'TYPO3\FLOW3\Tests\AOP\Fixture\ClassTaggedWithSomething';

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);

		$classTaggedWithFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassTaggedWithFilter('something');
		$classTaggedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($classTaggedWithFilter->matches($className, '', '', 1));

		$classTaggedWithFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassTaggedWithFilter('some.*');
		$classTaggedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($classTaggedWithFilter->matches($className, '', '', 1));

		$classTaggedWithFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassTaggedWithFilter('any.*');
		$classTaggedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertFalse($classTaggedWithFilter->matches($className, '', '', 1));
	}
}
?>