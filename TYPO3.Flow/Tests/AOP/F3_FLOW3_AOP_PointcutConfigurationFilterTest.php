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
 * @version $Id: F3_FLOW3_AOP_PointcutMethodTaggedWithFilter.php 1645 2008-12-16 16:52:05Z robert $
 */

/**
 * Testcase for the Pointcut Configuration Filter
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\AOP\PointcutClassFilterTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PointcutConfigurationFilterTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function filterMatchesOnConfigurationOptionsSetToTrue() {
		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\FLOW3\Fixture\DummyClass');
		$methods = $class->getMethods();
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings['custom']['package']['my']['configuration']['option'] = TRUE;

		$mockConfigurationManager->expects($this->atLeastOnce())->method('getSettings')->will($this->returnValue($settings));

		$filter = new \F3\FLOW3\AOP\PointcutConfigurationFilter($mockConfigurationManager, 'custom: package: my: configuration: option');

		$this->assertTrue($filter->matches($class, $methods[0], microtime()));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function filterDoesNotMatchOnConfigurationOptionsSetToFalse() {
		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\FLOW3\Fixture\DummyClass');
		$methods = $class->getMethods();
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings['custom']['package']['my']['configuration']['option'] = FALSE;

		$mockConfigurationManager->expects($this->atLeastOnce())->method('getSettings')->will($this->returnValue($settings));

		$filter = new \F3\FLOW3\AOP\PointcutConfigurationFilter($mockConfigurationManager, 'custom: package: my: configuration: option');

		$this->assertFalse($filter->matches($class, $methods[0], microtime()));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function filterDoesNotMatchOnNotExistingConfigurationOption() {
		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\FLOW3\Fixture\DummyClass');
		$methods = $class->getMethods();
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings['custom']['package']['my']['configuration']['option'] = TRUE;

		$mockConfigurationManager->expects($this->atLeastOnce())->method('getSettings')->will($this->returnValue($settings));

		$filter = new \F3\FLOW3\AOP\PointcutConfigurationFilter($mockConfigurationManager, 'custom: package: my: configuration: notExistingOption');

		$this->assertFalse($filter->matches($class, $methods[0], microtime()));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function filterDoesNotMatchOnConfigurationOptionsThatAreNotBoolean() {
		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\FLOW3\Fixture\DummyClass');
		$methods = $class->getMethods();
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings['custom']['package']['my']['configuration']['option'] = 'not a boolean';

		$mockConfigurationManager->expects($this->atLeastOnce())->method('getSettings')->will($this->returnValue($settings));

		$filter = new \F3\FLOW3\AOP\PointcutConfigurationFilter($mockConfigurationManager, 'custom: package: my: configuration: option');

		$this->assertFalse($filter->matches($class, $methods[0], microtime()));
	}
}
?>