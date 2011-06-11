<?php
namespace F3\FLOW3\Tests\Unit\AOP\Pointcut;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once (FLOW3_PATH_FLOW3 . 'Tests/Unit/AOP/Fixtures/ClassTaggedWithSomething.php');

/**
 * Testcase for the Pointcut Class-Tagged-With Filter
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PointcutClassTaggedWithFilterTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenTag() {
		$className = 'F3\FLOW3\Tests\AOP\Fixture\ClassTaggedWithSomething';

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);

		$classTaggedWithFilter = new \F3\FLOW3\AOP\Pointcut\PointcutClassTaggedWithFilter('something');
		$classTaggedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($classTaggedWithFilter->matches($className, '', '', 1));

		$classTaggedWithFilter = new \F3\FLOW3\AOP\Pointcut\PointcutClassTaggedWithFilter('some.*');
		$classTaggedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($classTaggedWithFilter->matches($className, '', '', 1));

		$classTaggedWithFilter = new \F3\FLOW3\AOP\Pointcut\PointcutClassTaggedWithFilter('any.*');
		$classTaggedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertFalse($classTaggedWithFilter->matches($className, '', '', 1));
	}
}
?>