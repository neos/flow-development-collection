<?php
namespace TYPO3\FLOW3\Tests\Unit\Package\Controller;

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
 * Testcase for the Package Manager Controller (CLI)
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PackageManagerControllerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function indexActionReturnsHelperAction() {
		$controller = $this->getMock('TYPO3\FLOW3\Package\Controller\PackageManagerController', array('helpAction'), array(), '', FALSE);
		$controller->expects($this->once())->method('helpAction')->will($this->returnValue('some help'));

		$response = $controller->indexAction();
		$this->assertEquals('some help', $response);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createActionChecksIfPackageKeyIsValidAndDoesntCreatePackageIfPackageKeyIsInvalid() {
		$mockPackageManager = $this->getMock('TYPO3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('isPackageKeyValid')->with('SomeNewPackage')->will($this->returnValue(FALSE));
		$mockPackageManager->expects($this->never())->method('createPackage');

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Package\Controller\PackageManagerController', array('dummy'), array(), '', FALSE);
		$controller->_set('packageManager', $mockPackageManager);

		$controller->createAction('SomeNewPackage');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createActionChecksIfPackageKeyIsAvailableAndDoesntCreatePackageIfPackageExists() {
		$mockPackageManager = $this->getMock('TYPO3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->any())->method('isPackageKeyValid')->will($this->returnValue(TRUE));
		$mockPackageManager->expects($this->once())->method('isPackageAvailable')->with('SomeNewPackage')->will($this->returnValue(TRUE));
		$mockPackageManager->expects($this->never())->method('createPackage');

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Package\Controller\PackageManagerController', array('dummy'), array(), '', FALSE);
		$controller->_set('packageManager', $mockPackageManager);

		$controller->createAction('SomeNewPackage');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createActionCreatesPackageIfPackageKeyIsOkay() {
		$mockPackage = $this->getMock('TYPO3\FLOW3\Package\Package', array(), array(), '', FALSE);

		$mockPackageManager = $this->getMock('TYPO3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->any())->method('isPackageKeyValid')->will($this->returnValue(TRUE));
		$mockPackageManager->expects($this->any())->method('isPackageAvailable')->will($this->returnValue(FALSE));
		$mockPackageManager->expects($this->once())->method('createPackage')->with('SomeNewPackage')->will($this->returnValue($mockPackage));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Package\Controller\PackageManagerController', array('dummy'), array(), '', FALSE);
		$controller->_set('packageManager', $mockPackageManager);

		$controller->createAction('SomeNewPackage');
	}
}
?>