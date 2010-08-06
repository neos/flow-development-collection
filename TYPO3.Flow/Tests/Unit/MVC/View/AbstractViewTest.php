<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\View;

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
 * Testcase for the MVC AbstractView
 *
 * @version $Id: EmptyViewTest.php 3837 2010-02-22 15:17:24Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractViewTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function assignAddsValueToInternalVariableCollection() {
		$view = $this->getAccessibleMock('F3\FLOW3\MVC\View\AbstractView', array('setControllerContext', 'render'));
		$view
			->assign('foo', 'FooValue')
			->assign('bar', 'BarValue');

		$expectedResult = array('foo' => 'FooValue', 'bar' => 'BarValue');
		$actualResult = $view->_get('variables');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function assignCanOverridePreviouslyAssignedValues() {
		$view = $this->getAccessibleMock('F3\FLOW3\MVC\View\AbstractView', array('setControllerContext', 'render'));
		$view->assign('foo', 'FooValue');
		$view->assign('foo', 'FooValueOverridden');

		$expectedResult = array('foo' => 'FooValueOverridden');
		$actualResult = $view->_get('variables');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function assignMultipleAddsValuesToInternalVariableCollection() {
		$view = $this->getAccessibleMock('F3\FLOW3\MVC\View\AbstractView', array('setControllerContext', 'render'));
		$view
			->assignMultiple(array('foo' => 'FooValue', 'bar' => 'BarValue'))
			->assignMultiple(array('baz' => 'BazValue'));

		$expectedResult = array('foo' => 'FooValue', 'bar' => 'BarValue', 'baz' => 'BazValue');
		$actualResult = $view->_get('variables');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function assignMultipleCanOverridePreviouslyAssignedValues() {
		$view = $this->getAccessibleMock('F3\FLOW3\MVC\View\AbstractView', array('setControllerContext', 'render'));
		$view->assign('foo', 'FooValue');
		$view->assignMultiple(array('foo' => 'FooValueOverridden', 'bar' => 'BarValue'));

		$expectedResult = array('foo' => 'FooValueOverridden', 'bar' => 'BarValue');
		$actualResult = $view->_get('variables');
		$this->assertEquals($expectedResult, $actualResult);
	}
}
?>