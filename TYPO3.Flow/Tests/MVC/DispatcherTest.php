<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC;

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
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Testcase for the MVC Dispatcher
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DispatcherTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatchCallsTheControllersProcessRequestMethodUntilTheIsDispatchedFlagInTheRequestObjectIsSet() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request');
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue('FooController'));
		$mockRequest->expects($this->at(0))->method('isDispatched')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(2))->method('isDispatched')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(4))->method('isDispatched')->will($this->returnValue(TRUE));

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Response');

		$mockController = $this->getMock('F3\FLOW3\MVC\Controller\ControllerInterface', array('processRequest'));
		$mockController->expects($this->exactly(2))->method('processRequest')->with($mockRequest, $mockResponse);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->exactly(2))->method('getObject')->with('FooController')->will($this->returnValue($mockController));

		$dispatcher = $this->getMock('F3\FLOW3\MVC\Dispatcher', array('dummy'), array($mockObjectManager), '', TRUE);
		$dispatcher->dispatch($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\MVC\Exception\InfiniteLoop
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatchThrowsAnInfiniteLoopExceptionIfTheRequestCouldNotBeDispachedAfter99Iterations() {
		$requestCallCounter = 0;
		$requestCallBack = function() use (&$requestCallCounter) {
			return ($requestCallCounter++ < 101) ? FALSE : TRUE;
		};
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request');
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue('FooController'));
		$mockRequest->expects($this->any())->method('isDispatched')->will($this->returnCallBack($requestCallBack, '__invoke'));

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Response');
		$mockController = $this->getMock('F3\FLOW3\MVC\Controller\ControllerInterface', array('processRequest'));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('getObject')->with('FooController')->will($this->returnValue($mockController));

		$dispatcher = $this->getMock('F3\FLOW3\MVC\Dispatcher', array('dummy'), array($mockObjectManager), '', TRUE);
		$dispatcher->dispatch($mockRequest, $mockResponse);
	}
}
?>