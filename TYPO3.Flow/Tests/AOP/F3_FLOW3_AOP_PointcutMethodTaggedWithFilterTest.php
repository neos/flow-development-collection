<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

require_once('Fixture/F3_FLOW3_Tests_AOP_Fixture_MethodsTaggedWithSomething.php');

/**
 * Testcase for the Pointcut Method-Tagged-With Filter
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class PointcutMethodTaggedWithFilterTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function filterMatchesMethodsWithSimpleTag() {
		$filter = new \F3\FLOW3\AOP\PointcutMethodTaggedWithFilter('someMethod');
		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\FLOW3\Tests\AOP\Fixture\MethodsTaggedWithSomething');
		$methods = $class->getMethods();
		$this->assertTrue($filter->matches($class, $methods[0], microtime()));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function filterMatchesMethodsWithWildcardTag() {
		$filter = new \F3\FLOW3\AOP\PointcutMethodTaggedWithFilter('some.*');
		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\FLOW3\Tests\AOP\Fixture\MethodsTaggedWithSomething');
		$methods = $class->getMethods();
		$this->assertTrue($filter->matches($class, $methods[0], microtime()));
		$this->assertTrue($filter->matches($class, $methods[1], microtime()));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function filterCorrectlyIgnoresMethodsWithoutRequestedTag() {
		$filter = new \F3\FLOW3\AOP\PointcutMethodTaggedWithFilter('some.*');
		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\FLOW3\Tests\AOP\Fixture\MethodsTaggedWithSomething');
		$methods = $class->getMethods();
		$this->assertFalse($filter->matches($class, $methods[2], microtime()));
	}
}
?>