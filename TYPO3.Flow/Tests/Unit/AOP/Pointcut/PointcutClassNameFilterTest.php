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

require_once (FLOW3_PATH_FLOW3 . 'Tests/Unit/Fixtures/DummyClass.php');
require_once (FLOW3_PATH_FLOW3 . 'Tests/Unit/Fixtures/SecondDummyClass.php');

/**
 * Testcase for the Pointcut Class Filter
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PointcutClassNameFilterTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * Checks if the class filter fires on a concrete and simple class expression
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenClassName() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('isClassFinal')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->any())->method('isMethodFinal')->will($this->returnValue(FALSE));

		$classFilter = new \F3\FLOW3\AOP\Pointcut\PointcutClassNameFilter('F3\Virtual\Foo\Bar');
		$classFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($classFilter->matches('F3\Virtual\Foo\Bar', '', '', 1), 'No. 1');

		$classFilter = new \F3\FLOW3\AOP\Pointcut\PointcutClassNameFilter('.*Virtual.*');
		$classFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($classFilter->matches('F3\Virtual\Foo\Bar', '', '', 1), 'No. 2');

		$classFilter = new \F3\FLOW3\AOP\Pointcut\PointcutClassNameFilter('F3\Firtual.*');
		$classFilter->injectReflectionService($mockReflectionService);
		$this->assertFalse($classFilter->matches('F3\Virtual\Foo\Bar', '', '', 1), 'No. 3');
	}

	/**
	 * Checks if the class filter ignores classes declared "final"
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesIgnoresFinalClasses() {
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval("
			final class $className { }"
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);

		$classFilter = new \F3\FLOW3\AOP\Pointcut\PointcutClassNameFilter('F3\Virtual\Foo\Bar');
		$classFilter->injectReflectionService($mockReflectionService);

		$this->assertFalse($classFilter->matches($className, '', '', 1));
	}

	/**
	 * Checks if the class filter ignores classes declared "final"
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesIgnoresClassesWithFinalConstructors() {
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval("
			class $className {
				final public function __construct() {}
			}"
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);

		$classFilter = new \F3\FLOW3\AOP\Pointcut\PointcutClassNameFilter('F3\Virtual\Foo\Bar');
		$classFilter->injectReflectionService($mockReflectionService);

		$this->assertFalse($classFilter->matches($className, '', '', 1));
	}
}
?>