<?php
namespace TYPO3\Flow\Tests\Unit\Mvc;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\Exception\StopActionException;
use TYPO3\Flow\Mvc\Exception\ForwardException;

/**
 * Testcase for the MVC Dispatcher
 */
class DispatcherTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function dispatchCallsTheControllersProcessRequestMethodUntilTheIsDispatchedFlagInTheRequestObjectIsSet() {
		$mockRequest = $this->getMock('TYPO3\Flow\Mvc\RequestInterface');
		$mockRequest->expects($this->at(0))->method('isDispatched')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(1))->method('isDispatched')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(2))->method('isDispatched')->will($this->returnValue(TRUE));

		$mockResponse = $this->getMock('TYPO3\Flow\Http\Response');

		$mockController = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerInterface', array('processRequest'));
		$mockController->expects($this->exactly(2))->method('processRequest')->with($mockRequest, $mockResponse);

		$dispatcher = $this->getMock('TYPO3\Flow\Mvc\Dispatcher', array('resolveController'), array(), '', FALSE);
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($mockController));
		$dispatcher->dispatch($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 */
	public function dispatchIgnoresStopExceptionsForFirstLevelActionRequests() {
		$mockRequest = $this->getMock('TYPO3\Flow\Mvc\RequestInterface');
		$mockRequest->expects($this->at(0))->method('isDispatched')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(2))->method('isDispatched')->will($this->returnValue(TRUE));
		$mockRequest->expects($this->atLeastOnce())->method('isMainRequest')->will($this->returnValue(TRUE));

		$response = new \TYPO3\Flow\Http\Response();
		$mockController = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerInterface', array('processRequest'));
		$mockController->expects($this->atLeastOnce())->method('processRequest')->will($this->throwException(new \TYPO3\Flow\Mvc\Exception\StopActionException()));

		$dispatcher = $this->getMock('TYPO3\Flow\Mvc\Dispatcher', array('resolveController'), array(), '', FALSE);
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($mockController));
		$dispatcher->dispatch($mockRequest, $response);
	}

	/**
	 * @test
	 */
	public function dispatchCatchesStopExceptionOfActionRequestsAndRollsBackToTheParentRequest() {
		$httpRequest = Request::create(new Uri('http://localhost'));
		$httpResponse = new Response();

		$mainRequest = $httpRequest->createActionRequest();
		$subRequest = new ActionRequest($mainRequest);

		$mainRequest->setDispatched(TRUE);

		$mockController = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerInterface', array('processRequest'));
		$mockController->expects($this->at(0))->method('processRequest')->will($this->returnCallback(
			function(ActionRequest $request) {
				$request->setDispatched(TRUE);
				throw new StopActionException();
			}
		));

		$dispatcher = $this->getMock('TYPO3\Flow\Mvc\Dispatcher', array('resolveController', 'emitAfterControllerInvocation'), array(), '', FALSE);
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($mockController));

		$dispatcher->dispatch($subRequest, $httpResponse);
	}

	/**
	 * @test
	 */
	public function dispatchContinuesWithNextRequestFoundInAForwardException() {
		$httpRequest = Request::create(new Uri('http://localhost'));
		$httpResponse = new Response();

		$mainRequest = $httpRequest->createActionRequest();
		$subRequest = new ActionRequest($mainRequest);
		$nextRequest = $httpRequest->createActionRequest();

		$mainRequest->setDispatched(TRUE);
		$mainRequest->setControllerSubPackageKey('main');
		$subRequest->setControllerSubPackageKey('sub');
		$nextRequest->setControllerSubPackageKey('next');

		$mockController = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerInterface', array('processRequest'));
		$mockController->expects($this->at(0))->method('processRequest')->will($this->returnCallback(
			function(ActionRequest $request) use($nextRequest) {
				$request->setDispatched(TRUE);
				$forwardException = new ForwardException();
				$forwardException->setNextRequest($nextRequest);
				throw $forwardException;
			}
		));
		$mockController->expects($this->at(1))->method('processRequest')->will($this->returnCallback(
			function(ActionRequest $request) use($nextRequest) {
					// NOTE: PhpUnit creates a clone of $nextRequest, thus $request is not the same instance as expected.
				if ($request == $nextRequest) {
					$nextRequest->setDispatched(TRUE);
				}
			}
		));

		$dispatcher = $this->getMock('TYPO3\Flow\Mvc\Dispatcher', array('resolveController', 'emitAfterControllerInvocation'), array(), '', FALSE);
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($mockController));

		$dispatcher->dispatch($subRequest, $httpResponse);
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Mvc\Exception\InfiniteLoopException
	 */
	public function dispatchThrowsAnInfiniteLoopExceptionIfTheRequestCouldNotBeDispachedAfter99Iterations() {
		$requestCallCounter = 0;
		$requestCallBack = function() use (&$requestCallCounter) {
			return ($requestCallCounter++ < 101) ? FALSE : TRUE;
		};
		$mockRequest = $this->getMock('TYPO3\Flow\Mvc\RequestInterface');
		$mockRequest->expects($this->any())->method('isDispatched')->will($this->returnCallBack($requestCallBack, '__invoke'));

		$mockResponse = $this->getMock('TYPO3\Flow\Http\Response');
		$mockController = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerInterface', array('processRequest'));

		$dispatcher = $this->getMock('TYPO3\Flow\Mvc\Dispatcher', array('resolveController', 'emitAfterControllerInvocation'), array(), '', FALSE);
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($mockController));
		$dispatcher->dispatch($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 */
	public function resolveControllerReturnsTheControllerSpecifiedInTheRequest() {
		$mockController = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerInterface');

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with($this->equalTo('TYPO3\TestPackage\SomeController'))->will($this->returnValue($mockController));

		$mockRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerPackageKey', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue('TYPO3\TestPackage\SomeController'));

		$dispatcher = $this->getAccessibleMock('TYPO3\Flow\Mvc\Dispatcher', array('dummy'));
		$dispatcher->injectObjectManager($mockObjectManager);

		$this->assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Controller\Exception\InvalidControllerException
	 */
	public function resolveControllerThrowsAnInvalidControllerExceptionIfTheResolvedControllerDoesNotImplementTheControllerInterface() {
		$mockController = $this->getMock('stdClass');

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with($this->equalTo('TYPO3\TestPackage\SomeController'))->will($this->returnValue($mockController));

		$mockRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerPackageKey', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue('TYPO3\TestPackage\SomeController'));

		$dispatcher = $this->getAccessibleMock('TYPO3\Flow\Mvc\Dispatcher', array('dummy'));
		$dispatcher->injectObjectManager($mockObjectManager);

		$this->assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Controller\Exception\InvalidControllerException
	 */
	public function resolveControllerThrowsAnInvalidControllerExceptionIfTheResolvedControllerDoesNotExist() {
		$mockController = $this->getMock('TYPO3\Flow\Mvc\Controller\NotFoundControllerInterface');

		$mockRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue(''));

		$dispatcher = $this->getAccessibleMock('TYPO3\Flow\Mvc\Dispatcher', array('dummy'));

		$dispatcher->_call('resolveController', $mockRequest);
	}

}
?>