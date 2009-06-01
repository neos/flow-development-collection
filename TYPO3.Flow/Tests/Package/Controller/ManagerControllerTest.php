<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package\Controller;

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
 * @subpackage Package
 * @version $Id: ManagerTest.php 1987 2009-03-12 09:07:04Z robert $
 */

/**
 * Testcase for the Package Manager Controller (CLI)
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id: ManagerTest.php 1987 2009-03-12 09:07:04Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ManagerControllerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function indexActionReturnsHelperAction() {
		$controller = $this->getMock('F3\FLOW3\Package\Controller\ManagerController', array('helpAction'), array(), '', FALSE);
		$controller->expects($this->once())->method('helpAction')->will($this->returnValue('some help'));

		$response = $controller->indexAction();
		$this->assertEquals('some help', $response);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createActionChecksIfPackageKeyIsValidAndDoesntCreatePackageIfPackageKeyIsInvalid() {
		$packageManager = $this->getMock('F3\FLOW3\Package\ManagerInterface');
		$packageManager->expects($this->once())->method('isPackageKeyValid')->with('SomeNewPackage')->will($this->returnValue(FALSE));
		$packageManager->expects($this->never())->method('createPackage');

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Package\Controller\ManagerController'), array('dummy'), array(), '', FALSE);
		$controller->_set('packageManager', $packageManager);

		$controller->createAction('SomeNewPackage');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createActionChecksIfPackageKeyIsAvailableAndDoesntCreatePackageIfPackageExists() {
		$packageManager = $this->getMock('F3\FLOW3\Package\ManagerInterface');
		$packageManager->expects($this->any())->method('isPackageKeyValid')->will($this->returnValue(TRUE));
		$packageManager->expects($this->once())->method('isPackageAvailable')->with('SomeNewPackage')->will($this->returnValue(TRUE));
		$packageManager->expects($this->never())->method('createPackage');

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Package\Controller\ManagerController'), array('dummy'), array(), '', FALSE);
		$controller->_set('packageManager', $packageManager);

		$controller->createAction('SomeNewPackage');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createActionCreatesPackageIfPackageKeyIsOkay() {
		$packageManager = $this->getMock('F3\FLOW3\Package\ManagerInterface');
		$packageManager->expects($this->any())->method('isPackageKeyValid')->will($this->returnValue(TRUE));
		$packageManager->expects($this->any())->method('isPackageAvailable')->will($this->returnValue(FALSE));
		$packageManager->expects($this->once())->method('createPackage')->with('SomeNewPackage');

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Package\Controller\ManagerController'), array('dummy'), array(), '', FALSE);
		$controller->_set('packageManager', $packageManager);

		$controller->createAction('SomeNewPackage');
	}
}
?>