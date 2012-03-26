<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\View;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC AbstractView
 *
 */
class AbstractViewTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function assignAddsValueToInternalVariableCollection() {
		$view = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\View\AbstractView', array('setControllerContext', 'render'));
		$view
			->assign('foo', 'FooValue')
			->assign('bar', 'BarValue');

		$expectedResult = array('foo' => 'FooValue', 'bar' => 'BarValue');
		$actualResult = $view->_get('variables');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function assignCanOverridePreviouslyAssignedValues() {
		$view = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\View\AbstractView', array('setControllerContext', 'render'));
		$view->assign('foo', 'FooValue');
		$view->assign('foo', 'FooValueOverridden');

		$expectedResult = array('foo' => 'FooValueOverridden');
		$actualResult = $view->_get('variables');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function assignMultipleAddsValuesToInternalVariableCollection() {
		$view = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\View\AbstractView', array('setControllerContext', 'render'));
		$view
			->assignMultiple(array('foo' => 'FooValue', 'bar' => 'BarValue'))
			->assignMultiple(array('baz' => 'BazValue'));

		$expectedResult = array('foo' => 'FooValue', 'bar' => 'BarValue', 'baz' => 'BazValue');
		$actualResult = $view->_get('variables');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function assignMultipleCanOverridePreviouslyAssignedValues() {
		$view = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\View\AbstractView', array('setControllerContext', 'render'));
		$view->assign('foo', 'FooValue');
		$view->assignMultiple(array('foo' => 'FooValueOverridden', 'bar' => 'BarValue'));

		$expectedResult = array('foo' => 'FooValueOverridden', 'bar' => 'BarValue');
		$actualResult = $view->_get('variables');
		$this->assertEquals($expectedResult, $actualResult);
	}
}
?>