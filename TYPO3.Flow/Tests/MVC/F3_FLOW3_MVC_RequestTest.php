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
 * @version $Id:$
 */

/**
 * Testcase for the MVC Generic Request
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_RequestTest extends F3_Testing_BaseTestCase {

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
		$request->setControllerName('F3_FLOW3_Some_Controller');
		$this->assertEquals('F3_FLOW3_Some_Controller', $request->getControllerName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theActionNameCanBeSetAndRetrieved() {
		$request = new F3_FLOW3_MVC_Request();
		$request->setActionName('theAction');
		$this->assertEquals('theAction', $request->getActionName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theRequestObjectCanBeLockedAgainstChanges() {
		$request = new F3_FLOW3_MVC_Request();

		$request->setActionName('someAction');
		$request->lock();
		try {
			$request->setActionName('anotherAction');
			$this->fail('No exception was thrown.');
		} catch (F3_FLOW3_MVC_Exception_RequestObjectAlreadyLocked $exception) {

		}
	}
}

?>