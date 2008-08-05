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

/**
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Testcase for the MVC Generic Request
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_RequestTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theDefaultPatternForBuildingTheControllerComponentNameIsPackageKeyControllerControllerName() {
		$request = new F3_FLOW3_MVC_Request();
		$request->setControllerPackageKey('TestPackage');
		$request->setControllerName('Foo');
		$this->assertEquals('F3_TestPackage_Controller_Foo', $request->getControllerComponentName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function thePatternForBuildingTheControllerComponentNameCanBeCustomized() {
		$request = new F3_FLOW3_MVC_Request();
		$request->setControllerPackageKey('TestPackage');
		$request->setControllerName('Foo');
		$request->setControllerComponentNamePattern('F3_@package_Bar_Baz_@controller');

		$this->assertEquals('F3_TestPackage_Bar_Baz_Foo', $request->getControllerComponentName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aSingleArgumentCanBeSetWithSetArgumentAndRetrievedWithGetArgument() {
		$request = new F3_FLOW3_MVC_Request();
		$request->setArgument('someArgumentName', 'theValue');
		$this->assertEquals('theValue', $request->getArgument('someArgumentName'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function multipleArgumentsCanBeSetWithSetArgumentsAndRetrievedWithGetArguments() {
		$arguments = new ArrayObject(array(
			'firstArgument' => 'firstValue',
			'dænishÅrgument' => 'görman välju',
			'3a' => '3v'
		));
		$request = new F3_FLOW3_MVC_Request();
		$request->setArguments($arguments);
		$this->assertEquals($arguments, $request->getArguments());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasArgumentTellsIfAnArgumentExists() {
		$request = new F3_FLOW3_MVC_Request();
		$request->setArgument('existingArgument', 'theValue');

		$this->assertTrue($request->hasArgument('existingArgument'));
		$this->assertFalse($request->hasArgument('notExistingArgument'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theControllerNameCanBeSetAndRetrieved() {
		$request = new F3_FLOW3_MVC_Request();
		$request->setControllerName('Some');
		$this->assertEquals('Some', $request->getControllerName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function thePackageKeyOfTheControllerCanBeSetAndRetrieved() {
		$request = new F3_FLOW3_MVC_Request();
		$request->setControllerPackageKey('TestPackage');
		$this->assertEquals('TestPackage', $request->getControllerPackageKey());
	}

	/**
	 * @test
	 * @expectedException F3_FLOW3_MVC_Exception_InvalidPackageKey
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidPackageKeysAreRejected() {
		$request = new F3_FLOW3_MVC_Request();
		$request->setControllerPackageKey('Some_Invalid_Key');
	}


	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theActionNameCanBeSetAndRetrieved() {
		$request = new F3_FLOW3_MVC_Request();
		$request->setControllerActionName('theAction');
		$this->assertEquals('theAction', $request->getControllerActionName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theRepresentationFormatCanBeSetAndRetrieved() {
		$request = new F3_FLOW3_MVC_Request();
		$request->setFormat('html');
		$this->assertEquals('html', $request->getFormat());
	}

	/**
	 * @test
	 * @expectedException F3_FLOW3_MVC_Exception_InvalidFormat
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidFormatsAreRejected() {
		$request = new F3_FLOW3_MVC_Request();
		$request->setFormat('.xml');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aFlagCanBeSetIfTheRequestNeedsToBeDispatchedAgain() {
		$request = new F3_FLOW3_MVC_Request();
		$this->assertFalse($request->isDispatched());

		$request->setDispatched(TRUE);
		$this->assertTrue($request->isDispatched());
	}
}

?>