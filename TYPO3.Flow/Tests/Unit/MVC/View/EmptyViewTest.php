<?php
namespace F3\FLOW3\Tests\Unit\MVC\View;

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
 * Testcase for the MVC EmptyView
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class EmptyViewTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsEmptyString() {
		$view = new \F3\FLOW3\MVC\View\EmptyView();
		$this->assertEquals('', $view->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function callingNonExistingMethodsWontThrowAnException() {
		$view = new \F3\FLOW3\MVC\View\EmptyView();
		$view->nonExistingMethod();
			// dummy assertion to satisfy strict mode in PHPUnit
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function assignReturnsViewToAllowChaining() {
		$view = new \F3\FLOW3\MVC\View\EmptyView();
		$returnedView = $view->assign('foo', 'FooValue');
		$this->assertSame($view, $returnedView);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function assignMultipleReturnsViewToAllowChaining() {
		$view = new \F3\FLOW3\MVC\View\EmptyView();
		$returnedView = $view->assignMultiple(array('foo', 'FooValue'));
		$this->assertSame($view, $returnedView);
	}
}
?>