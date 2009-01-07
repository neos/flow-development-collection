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

require_once('Fixture/F3_FLOW3_Tests_AOP_Fixture_ClassTaggedWithSomething.php');

/**
 * Testcase for the Pointcut Class-Tagged-With Filter
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\AOP\PointcutClassFilterTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class PointcutClassTaggedWithFilterTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function filterMatchesClassWithSimpleTag() {
		$classTaggedWithFilter = new \F3\FLOW3\AOP\PointcutClassTaggedWithFilter('something');
		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\FLOW3\Tests\AOP\Fixture\ClassTaggedWithSomething');
		$methods = $class->getMethods();
		$this->assertTrue($classTaggedWithFilter->matches($class, $methods[0], microtime()));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function filterMatchesClassWithTagWithWildcard() {
		$classTaggedWithFilter = new \F3\FLOW3\AOP\PointcutClassTaggedWithFilter('some.*');
		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\FLOW3\Tests\AOP\Fixture\ClassTaggedWithSomething');
		$methods = $class->getMethods();
		$this->assertTrue($classTaggedWithFilter->matches($class, $methods[0], microtime()));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function filterCorrectlyDoesntMatchClassWithoutSpecifiedTag() {
		$classTaggedWithFilter = new \F3\FLOW3\AOP\PointcutClassTaggedWithFilter('any.*');
		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\FLOW3\Tests\AOP\Fixture\ClassTaggedWithSomething');
		$methods = $class->getMethods();
		$this->assertFALSE($classTaggedWithFilter->matches($class, $methods[0], microtime()));
	}

}
?>