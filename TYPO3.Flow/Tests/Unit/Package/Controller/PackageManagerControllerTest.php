<?php
namespace TYPO3\Flow\Tests\Unit\Package\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Package Manager Controller (CLI)
 *
 */
class PackageManagerControllerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function indexActionReturnsHelperAction() {
		$controller = $this->getMock('TYPO3\Flow\Package\Controller\PackageManagerController', array('helpAction'), array(), '', FALSE);
		$controller->expects($this->once())->method('helpAction')->will($this->returnValue('some help'));

		$response = $controller->indexAction();
		$this->assertEquals('some help', $response);
	}

	/**
	 * @test
	 */
	public function createActionChecksIfPackageKeyIsValidAndDoesntCreatePackageIfPackageKeyIsInvalid() {
		$mockPackageManager = $this->getMock('TYPO3\Flow\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('isPackageKeyValid')->with('SomeNewPackage')->will($this->returnValue(FALSE));
		$mockPackageManager->expects($this->never())->method('createPackage');

		$controller = $this->getAccessibleMock('TYPO3\Flow\Package\Controller\PackageManagerController', array('dummy'), array(), '', FALSE);
		$controller->_set('packageManager', $mockPackageManager);

		$controller->createAction('SomeNewPackage');
	}

	/**
	 * @test
	 */
	public function createActionChecksIfPackageKeyIsAvailableAndDoesntCreatePackageIfPackageExists() {
		$mockPackageManager = $this->getMock('TYPO3\Flow\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->any())->method('isPackageKeyValid')->will($this->returnValue(TRUE));
		$mockPackageManager->expects($this->once())->method('isPackageAvailable')->with('SomeNewPackage')->will($this->returnValue(TRUE));
		$mockPackageManager->expects($this->never())->method('createPackage');

		$controller = $this->getAccessibleMock('TYPO3\Flow\Package\Controller\PackageManagerController', array('dummy'), array(), '', FALSE);
		$controller->_set('packageManager', $mockPackageManager);

		$controller->createAction('SomeNewPackage');
	}

	/**
	 * @test
	 */
	public function createActionCreatesPackageIfPackageKeyIsOkay() {
		$mockPackage = $this->getMock('TYPO3\Flow\Package\Package', array(), array(), '', FALSE);

		$mockPackageManager = $this->getMock('TYPO3\Flow\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->any())->method('isPackageKeyValid')->will($this->returnValue(TRUE));
		$mockPackageManager->expects($this->any())->method('isPackageAvailable')->will($this->returnValue(FALSE));
		$mockPackageManager->expects($this->once())->method('createPackage')->with('SomeNewPackage')->will($this->returnValue($mockPackage));

		$controller = $this->getAccessibleMock('TYPO3\Flow\Package\Controller\PackageManagerController', array('dummy'), array(), '', FALSE);
		$controller->_set('packageManager', $mockPackageManager);

		$controller->createAction('SomeNewPackage');
	}
}
?>