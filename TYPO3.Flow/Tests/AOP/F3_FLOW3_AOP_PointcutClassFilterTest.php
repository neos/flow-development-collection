<?php
declare(ENCODING = 'utf-8');

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

require_once (FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Fixtures/F3_FLOW3_Fixture_DummyClass.php');
require_once (FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Fixtures/F3_FLOW3_Fixture_SecondDummyClass.php');

/**
 * Testcase for the Pointcut Class Filter
 * 
 * @package		Framework
 * @version 	$Id:F3_FLOW3_AOP_PointcutClassFilterTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_PointcutClassFilterTest extends F3_Testing_BaseTestCase {

	/**
	 * Checks if the class filter fires on a concrete and simple class expression
	 * 
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches_simpleClass() {
		$classFilter = new F3_FLOW3_AOP_PointcutClassFilter('F3_FLOW3_Fixture_DummyClass');
		$class = new ReflectionClass('F3_FLOW3_Fixture_DummyClass');
		$methods = $class->getMethods();
		$this->assertTrue($classFilter->matches($class, $methods[0], microtime()), 'The class filter did not return TRUE although the specified class should match.');
	}

	/**
	 * Checks if the class filter fails on a simple but wrong class name
	 * 
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches_failsOnWrongSimpleClass() {
		$classFilter = new F3_FLOW3_AOP_PointcutClassFilter('F3_FLOW3_Fixture_IDontExist');
		$class = new ReflectionClass('F3_FLOW3_Fixture_DummyClass');		
		$methods = $class->getMethods();
		$this->assertFalse($classFilter->matches($class, $methods[0], microtime()), 'The class filter did not return FALSE although the specified class doesn\'t match.');

		$classFilter = new F3_FLOW3_AOP_PointcutClassFilter('F3_FLOW3_Fixture_Dummy');
		$class = new ReflectionClass('F3_FLOW3_Fixture_DummyClass');		
		$methods = $class->getMethods();
		$this->assertFalse($classFilter->matches($class, $methods[0], microtime()), 'The class filter did not return FALSE although the specified class doesn\'t match.');
	}

	/**
	 * Checks if the class filter fires on a simple class name with wildcard
	 * 
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches_simpleClassWithWildcard() {
		$classFilter = new F3_FLOW3_AOP_PointcutClassFilter('F3_FLOW3_Fixture_Dummy.*');
		$class = new ReflectionClass('F3_FLOW3_Fixture_DummyClass');		
		$methods = $class->getMethods();
		$this->assertTrue($classFilter->matches($class, $methods[0], microtime()), 'The class filter did not return TRUE although the specified class should match.');

		$class = new ReflectionClass('F3_FLOW3_Fixture_SecondDummyClass');
		$this->assertFALSE($classFilter->matches($class, $methods[0], microtime()), 'The class filter did not return FALSE although the specified class should not match.');
	}

	/**
	 * Checks if the class filter fails on a simple class name with wildcard which shouldn't match
	 * 
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches_failsOnWrongSimpleClassWithWildcard() {
		$classFilter = new F3_FLOW3_AOP_PointcutClassFilter('F3_FLOW3_Fixture_IDont.*');
		$class = new ReflectionClass('F3_FLOW3_Fixture_DummyClass');		
		$methods = $class->getMethods();
		$this->assertFalse($classFilter->matches($class, $methods[0], microtime()), 'The class filter did not return FALSE although the specified class doesn\'t match.');
	}

	/**
	 * Checks if the class filter ignores classes declared "final"
	 * 
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches_ignoresFinalClasses() {
		$classFilter = new F3_FLOW3_AOP_PointcutClassFilter('F3_TestPackage_.*');
		$class = new ReflectionClass('F3_TestPackage_FinalClass');		
		$methods = $class->getMethods();
		$this->assertFalse($classFilter->matches($class, $methods[0], microtime()), 'The class filter did not return FALSE although the specified final class should be ignored.');		
	}
	
	/**
	 * Checks if the class filter ignores classes with a constructor declared "final"
	 * 
	 * @test
	 * @author Robert Lemke <robert@typo3.org> und Karsten
	 */
	public function matches_ignoresClassWithFinalConstructor() {
		$classFilter = new F3_FLOW3_AOP_PointcutClassFilter('F3_TestPackage_.*');
		$class = new ReflectionClass('F3_TestPackage_ClassWithFinalConstructor');		
		$method = $class->getMethod('__construct');
		$this->assertFalse($classFilter->matches($class, $method, microtime()), 'The class filter did not return FALSE although the specified class contains a final constructor.');		
	}
}
?>