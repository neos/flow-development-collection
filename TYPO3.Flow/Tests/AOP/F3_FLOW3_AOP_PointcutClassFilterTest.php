<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::AOP;

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

require_once (FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Fixtures/F3_FLOW3_Fixture_DummyClass.php');
require_once (FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Fixtures/F3_FLOW3_Fixture_SecondDummyClass.php');

/**
 * Testcase for the Pointcut Class Filter
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::AOP::PointcutClassFilterTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PointcutClassFilterTest extends F3::Testing::BaseTestCase {

	/**
	 * Checks if the class filter fires on a concrete and simple class expression
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches_simpleClass() {
		$classFilter = new F3::FLOW3::AOP::PointcutClassFilter('F3::FLOW3::Fixture::DummyClass');
		$class = new F3::FLOW3::Reflection::ClassReflection('F3::FLOW3::Fixture::DummyClass');
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
		$classFilter = new F3::FLOW3::AOP::PointcutClassFilter('F3::FLOW3::Fixture::IDontExist');
		$class = new F3::FLOW3::Reflection::ClassReflection('F3::FLOW3::Fixture::DummyClass');
		$methods = $class->getMethods();
		$this->assertFalse($classFilter->matches($class, $methods[0], microtime()), 'The class filter did not return FALSE although the specified class doesn\'t match.');

		$classFilter = new F3::FLOW3::AOP::PointcutClassFilter('F3::FLOW3::Fixture::Dummy');
		$class = new F3::FLOW3::Reflection::ClassReflection('F3::FLOW3::Fixture::DummyClass');
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
		$classFilter = new F3::FLOW3::AOP::PointcutClassFilter('F3::FLOW3::Fixture::Dummy.*');
		$class = new F3::FLOW3::Reflection::ClassReflection('F3::FLOW3::Fixture::DummyClass');
		$methods = $class->getMethods();
		$this->assertTrue($classFilter->matches($class, $methods[0], microtime()), 'The class filter did not return TRUE although the specified class should match.');

		$class = new F3::FLOW3::Reflection::ClassReflection('F3::FLOW3::Fixture::SecondDummyClass');
		$this->assertFALSE($classFilter->matches($class, $methods[0], microtime()), 'The class filter did not return FALSE although the specified class should not match.');
	}

	/**
	 * Checks if the class filter fails on a simple class name with wildcard which shouldn't match
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches_failsOnWrongSimpleClassWithWildcard() {
		$classFilter = new F3::FLOW3::AOP::PointcutClassFilter('F3::FLOW3::Fixture::IDont.*');
		$class = new F3::FLOW3::Reflection::ClassReflection('F3::FLOW3::Fixture::DummyClass');
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
		$classFilter = new F3::FLOW3::AOP::PointcutClassFilter('F3::TestPackage::.*');
		$class = new F3::FLOW3::Reflection::ClassReflection('F3::TestPackage::FinalClass');
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
		$classFilter = new F3::FLOW3::AOP::PointcutClassFilter('F3::TestPackage::.*');
		$class = new F3::FLOW3::Reflection::ClassReflection('F3::TestPackage::ClassWithFinalConstructor');
		$method = $class->getMethod('__construct');
		$this->assertFalse($classFilter->matches($class, $method, microtime()), 'The class filter did not return FALSE although the specified class contains a final constructor.');
	}
}
?>