<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
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
 * @version $Id:\F3\FLOW3\AOP\PointcutClassFilterTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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