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
 * Testcase for the MVC EmptyView
 *
 */
class EmptyViewTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function renderReturnsEmptyString() {
		$view = new \TYPO3\FLOW3\Mvc\View\EmptyView();
		$this->assertEquals('', $view->render());
	}

	/**
	 * @test
	 */
	public function callingNonExistingMethodsWontThrowAnException() {
		$view = new \TYPO3\FLOW3\Mvc\View\EmptyView();
		$view->nonExistingMethod();
			// dummy assertion to satisfy strict mode in PHPUnit
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 */
	public function assignReturnsViewToAllowChaining() {
		$view = new \TYPO3\FLOW3\Mvc\View\EmptyView();
		$returnedView = $view->assign('foo', 'FooValue');
		$this->assertSame($view, $returnedView);
	}

	/**
	 * @test
	 */
	public function assignMultipleReturnsViewToAllowChaining() {
		$view = new \TYPO3\FLOW3\Mvc\View\EmptyView();
		$returnedView = $view->assignMultiple(array('foo', 'FooValue'));
		$this->assertSame($view, $returnedView);
	}
}
?>