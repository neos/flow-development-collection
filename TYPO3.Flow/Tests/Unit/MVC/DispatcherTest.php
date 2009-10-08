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
 * Testcase for the MVC Dispatcher
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DispatcherTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dispatchCallsTheControllersProcessRequestMethodUntilTheIsDispatchedFlagInTheRequestObjectIsSet() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$mockRequest->expects($this->at(0))->method('isDispatched')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(1))->method('isDispatched')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(2))->method('isDispatched')->will($this->returnValue(TRUE));

		$mockResponse = $this->getMock('F3\FLOW3\MVC\ResponseInterface');

		$mockController = $this->getMock('F3\FLOW3\MVC\Controller\ControllerInterface', array('processRequest', 'canProcessRequest'));
		$mockController->expects($this->exactly(2))->method('processRequest')->with($mockRequest, $mockResponse);

		$dispatcher = $this->getMock('F3\FLOW3\MVC\Dispatcher', array('resolveController'), array(), '', FALSE);
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($mockController));
		$dispatcher->dispatch($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\MVC\Exception\InfiniteLoop
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dispatchThrowsAnInfiniteLoopExceptionIfTheRequestCouldNotBeDispachedAfter99Iterations() {
		$requestCallCounter = 0;
		$requestCallBack = function() use (&$requestCallCounter) {
			return ($requestCallCounter++ < 101) ? FALSE : TRUE;
		};
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$mockRequest->expects($this->any())->method('isDispatched')->will($this->returnCallBack($requestCallBack, '__invoke'));

		$mockResponse = $this->getMock('F3\FLOW3\MVC\ResponseInterface');
		$mockController = $this->getMock('F3\FLOW3\MVC\Controller\ControllerInterface', array('processRequest', 'canProcessRequest'));

		$dispatcher = $this->getMock('F3\FLOW3\MVC\Dispatcher', array('resolveController'), array(), '', FALSE);
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($mockController));
		$dispatcher->dispatch($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveControllerReturnsTheNotFoundControllerDefinedInTheFLOW3SettingsAndInjectsCorrectExceptionIfTheResolvedControllerDoesNotExist() {
		$mockController = $this->getMock('F3\FLOW3\MVC\Controller\NotFoundControllerInterface', array(), array(), '', FALSE);
		$mockController->expects($this->once())->method('setException')->with($this->isInstanceOf('F3\FLOW3\MVC\Controller\Exception\InvalidController'));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->once())->method('getObject')->with($this->equalTo('F3\TestPackage\TheCustomNotFoundController'))->will($this->returnValue($mockController));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('TestPackage'));
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue(''));

		$mockPackageManager = $this->getMock('F3\FLOW3\Package\Manager', array(), array(), '', FALSE);
		$mockPackageManager->expects($this->once())->method('isPackageAvailable')->with($this->equalTo('TestPackage'))->will($this->returnValue(TRUE));
		$mockPackageManager->expects($this->once())->method('isPackageActive')->with($this->equalTo('TestPackage'))->will($this->returnValue(TRUE));

		$dispatcher = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Dispatcher'), array('dummy'), array($mockObjectManager), '', TRUE);
		$dispatcher->injectSettings(array('mvc' => array('notFoundController' => 'F3\TestPackage\TheCustomNotFoundController')));
		$dispatcher->injectPackageManager($mockPackageManager);

		$this->assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveControllerReturnsTheNotFoundControllerDefinedInTheFLOW3SettingsAndInjectsCorrectExceptionIfTheResolvedPackageDoesNotExist() {
		$mockController = $this->getMock('F3\FLOW3\MVC\Controller\NotFoundControllerInterface', array(), array(), '', FALSE);
		$mockController->expects($this->once())->method('setException')->with($this->isInstanceOf('F3\FLOW3\MVC\Controller\Exception\InvalidPackage'));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->once())->method('getObject')->with($this->equalTo('F3\TestPackage\TheCustomNotFoundController'))->will($this->returnValue($mockController));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('TestPackage'));

		$mockPackageManager = $this->getMock('F3\FLOW3\Package\Manager', array(), array(), '', FALSE);
		$mockPackageManager->expects($this->once())->method('isPackageAvailable')->with($this->equalTo('TestPackage'))->will($this->returnValue(FALSE));

		$dispatcher = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Dispatcher'), array('dummy'), array($mockObjectManager), '', TRUE);
		$dispatcher->injectSettings(array('mvc' => array('notFoundController' => 'F3\TestPackage\TheCustomNotFoundController')));
		$dispatcher->injectPackageManager($mockPackageManager);

		$this->assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveControllerReturnsTheNotFoundControllerDefinedInTheFLOW3SettingsAndInjectsCorrectExceptionIfTheResolvedPackageIsNotActive() {
		$mockController = $this->getMock('F3\FLOW3\MVC\Controller\NotFoundControllerInterface', array(), array(), '', FALSE);
		$mockController->expects($this->once())->method('setException')->with($this->isInstanceOf('F3\FLOW3\MVC\Controller\Exception\InactivePackage'));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->once())->method('getObject')->with($this->equalTo('F3\TestPackage\TheCustomNotFoundController'))->will($this->returnValue($mockController));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('TestPackage'));

		$mockPackageManager = $this->getMock('F3\FLOW3\Package\Manager', array(), array(), '', FALSE);
		$mockPackageManager->expects($this->once())->method('isPackageAvailable')->with($this->equalTo('TestPackage'))->will($this->returnValue(TRUE));
		$mockPackageManager->expects($this->once())->method('isPackageActive')->with($this->equalTo('TestPackage'))->will($this->returnValue(FALSE));

		$dispatcher = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Dispatcher'), array('dummy'), array($mockObjectManager), '', TRUE);
		$dispatcher->injectSettings(array('mvc' => array('notFoundController' => 'F3\TestPackage\TheCustomNotFoundController')));
		$dispatcher->injectPackageManager($mockPackageManager);

		$this->assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
	}
}
?>