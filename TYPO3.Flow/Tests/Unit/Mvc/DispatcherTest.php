<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc;

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
 * Testcase for the MVC Dispatcher
 *
 */
class DispatcherTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function dispatchCallsTheControllersProcessRequestMethodUntilTheIsDispatchedFlagInTheRequestObjectIsSet() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\RequestInterface');
		$mockRequest->expects($this->at(0))->method('isDispatched')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(1))->method('isDispatched')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(2))->method('isDispatched')->will($this->returnValue(TRUE));

		$mockResponse = $this->getMock('TYPO3\FLOW3\MVC\ResponseInterface');

		$mockController = $this->getMock('TYPO3\FLOW3\Mvc\Controller\ControllerInterface', array('processRequest', 'canProcessRequest'));
		$mockController->expects($this->exactly(2))->method('processRequest')->with($mockRequest, $mockResponse);

		$dispatcher = $this->getMock('TYPO3\FLOW3\Mvc\Dispatcher', array('resolveController'), array(), '', FALSE);
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($mockController));
		$dispatcher->dispatch($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 */
	public function dispatchIgnoresStopExceptionsByDefault() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\RequestInterface');
		$mockRequest->expects($this->at(0))->method('isDispatched')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(1))->method('isDispatched')->will($this->returnValue(TRUE));

		$mockResponse = $this->getMock('TYPO3\FLOW3\MVC\ResponseInterface');
		$mockController = $this->getMock('TYPO3\FLOW3\MVC\Controller\ControllerInterface', array('processRequest', 'canProcessRequest'));
		$mockController->expects($this->atLeastOnce())->method('processRequest')->will($this->throwException(new \TYPO3\FLOW3\MVC\Exception\StopActionException()));

		$dispatcher = $this->getMock('TYPO3\FLOW3\Mvc\Dispatcher', array('resolveController'), array(), '', FALSE);
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($mockController));
		$dispatcher->dispatch($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Mvc\Exception\StopActionException
	 */
	public function dispatchRethrowsStopExceptionsForSubRequests() {
		$mockSubRequest = $this->getMock('TYPO3\FLOW3\Mvc\Web\SubRequest', array(), array(), '', FALSE);
		$mockSubRequest->expects($this->at(0))->method('isDispatched')->will($this->returnValue(FALSE));
		$mockSubRequest->expects($this->at(1))->method('isDispatched')->will($this->returnValue(TRUE));

		$mockResponse = $this->getMock('TYPO3\FLOW3\Http\Response');
		$mockController = $this->getMock('TYPO3\FLOW3\Mvc\Controller\ControllerInterface', array('processRequest', 'canProcessRequest'));
		$mockController->expects($this->atLeastOnce())->method('processRequest')->will($this->throwException(new \TYPO3\FLOW3\Mvc\Exception\StopActionException()));

		$dispatcher = $this->getMock('TYPO3\FLOW3\Mvc\Dispatcher', array('resolveController', 'emitAfterControllerInvocation'), array(), '', FALSE);
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($mockController));
		$dispatcher->dispatch($mockSubRequest, $mockResponse);
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Mvc\Exception\InfiniteLoopException
	 */
	public function dispatchThrowsAnInfiniteLoopExceptionIfTheRequestCouldNotBeDispachedAfter99Iterations() {
		$requestCallCounter = 0;
		$requestCallBack = function() use (&$requestCallCounter) {
			return ($requestCallCounter++ < 101) ? FALSE : TRUE;
		};
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\RequestInterface');
		$mockRequest->expects($this->any())->method('isDispatched')->will($this->returnCallBack($requestCallBack, '__invoke'));

		$mockResponse = $this->getMock('TYPO3\FLOW3\Http\Response');
		$mockController = $this->getMock('TYPO3\FLOW3\Mvc\Controller\ControllerInterface', array('processRequest', 'canProcessRequest'));

		$dispatcher = $this->getMock('TYPO3\FLOW3\Mvc\Dispatcher', array('resolveController', 'emitAfterControllerInvocation'), array(), '', FALSE);
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($mockController));
		$dispatcher->dispatch($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 */
	public function resolveControllerReturnsTheControllerSpecifiedInTheRequest() {
		$mockController = $this->getMock('TYPO3\FLOW3\Mvc\Controller\ControllerInterface');

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with($this->equalTo('TYPO3\TestPackage\SomeController'))->will($this->returnValue($mockController));

		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('getControllerPackageKey', 'getControllerObjectName'));
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue('TYPO3\TestPackage\SomeController'));

		$dispatcher = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Dispatcher', array('dummy'));
		$dispatcher->injectObjectManager($mockObjectManager);

		$this->assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Controller\Exception\InvalidControllerException
	 */
	public function resolveControllerThrowsAnInvalidControllerExceptionIfTheResolvedControllerDoesNotImplementTheControllerInterface() {
		$mockController = $this->getMock('stdClass');

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with($this->equalTo('TYPO3\TestPackage\SomeController'))->will($this->returnValue($mockController));

		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('getControllerPackageKey', 'getControllerObjectName'));
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue('TYPO3\TestPackage\SomeController'));

		$dispatcher = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Dispatcher', array('dummy'));
		$dispatcher->injectObjectManager($mockObjectManager);

		$this->assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
	}

	/**
	 * @test
	 */
	public function resolveControllerReturnsTheNotFoundControllerDefinedInTheFLOW3SettingsAndInjectsCorrectExceptionIfTheResolvedControllerDoesNotExist() {
		$mockController = $this->getMock('TYPO3\FLOW3\Mvc\Controller\NotFoundControllerInterface');
		$mockController->expects($this->once())->method('setException')->with($this->isInstanceOf('TYPO3\FLOW3\Mvc\Controller\Exception\InvalidControllerException'));

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with($this->equalTo('TYPO3\TestPackage\TheCustomNotFoundController'))->will($this->returnValue($mockController));

		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('getControllerPackageKey', 'getControllerObjectName'));
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue(''));

		$dispatcher = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Dispatcher', array('dummy'));
		$dispatcher->injectObjectManager($mockObjectManager);
		$dispatcher->injectSettings(array('mvc' => array('notFoundController' => 'TYPO3\TestPackage\TheCustomNotFoundController')));

		$this->assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Controller\Exception\InvalidControllerException
	 */
	public function resolveControllerThrowsInvalidControllerExceptionIfTheNotFoundControllerDefinedInTheFLOW3SettingsDoesNotImplementTheNotFoundControllerInterface() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with($this->equalTo('TYPO3\TestPackage\TheCustomNotFoundController'))->will($this->returnValue(new \stdClass()));

		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('getControllerObjectName'));
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue(''));

		$dispatcher = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Dispatcher', array('dummy'));
		$dispatcher->injectObjectManager($mockObjectManager);
		$dispatcher->injectSettings(array('mvc' => array('notFoundController' => 'TYPO3\TestPackage\TheCustomNotFoundController')));

		$dispatcher->_call('resolveController', $mockRequest);
	}

}
?>