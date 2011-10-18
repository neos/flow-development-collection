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

require_once (FLOW3_PATH_FLOW3 . 'Tests/Unit/AOP/Fixtures/MethodsTaggedWithSomething.php');

/**
 * Testcase for the Pointcut Method-Tagged-With Filter
 *
 */
class PointcutMethodTaggedWithFilterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenTag() {
		$className = 'TYPO3\FLOW3\Tests\AOP\Fixture\MethodsTaggedWithSomething';

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'saveToCache', 'hasMethod'));
		$mockReflectionService->expects($this->any())->method('hasMethod')->will($this->returnValue(TRUE));

		$methodTaggedWithFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutMethodTaggedWithFilter('session');
		$methodTaggedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($methodTaggedWithFilter->matches(__CLASS__, 'someMethod', $className, 1));

		$methodTaggedWithFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutMethodTaggedWithFilter('session|internal');
		$methodTaggedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($methodTaggedWithFilter->matches(__CLASS__, 'someMethod', $className, 1));
		$this->assertTrue($methodTaggedWithFilter->matches(__CLASS__, 'someOtherMethod', $className, 2));

		$methodTaggedWithFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutMethodTaggedWithFilter('ext.*');
		$methodTaggedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertFalse($methodTaggedWithFilter->matches(__CLASS__, 'somethingCompletelyDifferent', $className, 1));
	}
}
?>