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

use TYPO3\Flow\Http\Response as HttpResponse;
use TYPO3\Flow\Http\Request as HttpRequest;
use TYPO3\Flow\Log\SecurityLoggerInterface;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\ControllerInterface;
use TYPO3\Flow\Mvc\Dispatcher;
use TYPO3\Flow\Mvc\Exception\ForwardException;
use TYPO3\Flow\Mvc\Exception\StopActionException;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Authorization\FirewallInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Exception\AccessDeniedException;
use TYPO3\Flow\Security\Exception\AuthenticationRequiredException;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC Dispatcher
 */
class DispatcherTest extends UnitTestCase {

	/**
	 * @var Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockParentRequest;

	/**
	 * @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockActionRequest;

	/**
	 * @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockMainRequest;

	/**
	 * @var HttpRequest|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockHttpRequest;

	/**
	 * @var HttpResponse|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockHttpResponse;

	/**
	 * @var ControllerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockController;

	/**
	 * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockObjectManager;

	/**
	 * @var Context|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockSecurityContext;

	/**
	 * @var FirewallInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockFirewall;

	/**
	 * @var SecurityLoggerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockSecurityLogger;

	/**
	 * Sets up this test case
	 */
	public function setUp() {
		$this->dispatcher = $this->getMock('TYPO3\Flow\Mvc\Dispatcher', array('resolveController'), array(), '', FALSE);

		$this->mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$this->mockActionRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(FALSE));

		$this->mockParentRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$this->mockActionRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockParentRequest));

		$this->mockMainRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$this->mockActionRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));

		$this->mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));

		$this->mockHttpResponse = $this->getMockBuilder('TYPO3\Flow\Http\Response')->disableOriginalConstructor()->getMock();

		$this->mockController = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerInterface', array('processRequest'));
		$this->dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($this->mockController));

		$this->mockSecurityContext = $this->getMockBuilder('TYPO3\Flow\Security\Context')->disableOriginalConstructor()->getMock();

		$this->mockFirewall = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\FirewallInterface')->getMock();

		$this->mockSecurityLogger = $this->getMockBuilder('TYPO3\Flow\Log\SecurityLoggerInterface')->getMock();

		$this->mockObjectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManagerInterface')->getMock();
		$this->mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback(function($className) {
			if ($className === 'TYPO3\Flow\Security\Context') {
				return $this->mockSecurityContext;
			} elseif ($className === 'TYPO3\Flow\Security\Authorization\FirewallInterface') {
				return $this->mockFirewall;
			} elseif ($className === 'TYPO3\Flow\Log\SecurityLoggerInterface') {
				return $this->mockSecurityLogger;
			}
			return NULL;
		}));
		$this->inject($this->dispatcher, 'objectManager', $this->mockObjectManager);
	}

	/**
	 * @test
	 */
	public function dispatchCallsTheControllersProcessRequestMethodUntilTheIsDispatchedFlagInTheRequestObjectIsSet() {
		$this->mockActionRequest->expects($this->at(0))->method('isDispatched')->will($this->returnValue(FALSE));
		$this->mockActionRequest->expects($this->at(1))->method('isDispatched')->will($this->returnValue(FALSE));
		$this->mockActionRequest->expects($this->at(2))->method('isDispatched')->will($this->returnValue(TRUE));

		$this->mockController->expects($this->exactly(2))->method('processRequest')->with($this->mockActionRequest, $this->mockHttpResponse);

		$this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
	}

	/**
	 * @test
	 */
	public function dispatchIgnoresStopExceptionsForFirstLevelActionRequests() {
		$this->mockParentRequest->expects($this->at(0))->method('isDispatched')->will($this->returnValue(FALSE));
		$this->mockParentRequest->expects($this->at(2))->method('isDispatched')->will($this->returnValue(TRUE));
		$this->mockParentRequest->expects($this->atLeastOnce())->method('isMainRequest')->will($this->returnValue(TRUE));

		$this->mockController->expects($this->atLeastOnce())->method('processRequest')->will($this->throwException(new StopActionException()));

		$this->dispatcher->dispatch($this->mockParentRequest, $this->mockHttpResponse);
	}

	/**
	 * @test
	 */
	public function dispatchCatchesStopExceptionOfActionRequestsAndRollsBackToTheParentRequest() {
		$this->mockActionRequest->expects($this->atLeastOnce())->method('isDispatched')->will($this->returnValue(FALSE));
		$this->mockParentRequest->expects($this->atLeastOnce())->method('isDispatched')->will($this->returnValue(TRUE));

		$this->mockController->expects($this->atLeastOnce())->method('processRequest')->will($this->throwException(new StopActionException()));

		$this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
	}

	/**
	 * @test
	 */
	public function dispatchContinuesWithNextRequestFoundInAForwardException() {
		/** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $nextRequest */
		$nextRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$nextRequest->expects($this->atLeastOnce())->method('isDispatched')->will($this->returnValue(TRUE));

		$this->mockParentRequest->expects($this->atLeastOnce())->method('isDispatched')->will($this->returnValue(FALSE));

		$this->mockController->expects($this->at(0))->method('processRequest')->with($this->mockActionRequest)->will($this->throwException(new StopActionException()));

		$forwardException = new ForwardException();
		$forwardException->setNextRequest($nextRequest);
		$this->mockController->expects($this->at(1))->method('processRequest')->with($this->mockParentRequest)->will($this->throwException($forwardException));

		$this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\InfiniteLoopException
	 */
	public function dispatchThrowsAnInfiniteLoopExceptionIfTheRequestCouldNotBeDispachedAfter99Iterations() {
		$requestCallCounter = 0;
		$requestCallBack = function() use (&$requestCallCounter) {
			return ($requestCallCounter++ < 101) ? FALSE : TRUE;
		};
		$this->mockParentRequest->expects($this->any())->method('isDispatched')->will($this->returnCallBack($requestCallBack, '__invoke'));

		$this->dispatcher->dispatch($this->mockParentRequest, $this->mockHttpResponse);
	}

	/**
	 * @test
	 */
	public function dispatchDoesNotBlockCliRequests() {
		/** @var \TYPO3\Flow\Cli\Request|\PHPUnit_Framework_MockObject_MockObject $mockCliRequest */
		$mockCliRequest = $this->getMockBuilder('TYPO3\Flow\Cli\Request')->disableOriginalConstructor()->getMock();
		$mockCliRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(TRUE));

		$this->mockSecurityContext->expects($this->never())->method('areAuthorizationChecksDisabled')->will($this->returnValue(TRUE));
		$this->mockFirewall->expects($this->never())->method('blockIllegalRequests');

		$this->dispatcher->dispatch($mockCliRequest, $this->mockHttpResponse);
	}

	/**
	 * @test
	 */
	public function dispatchDoesNotBlockRequestsIfAuthorizationChecksAreDisabled() {
		$this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(TRUE));

		$this->mockSecurityContext->expects($this->any())->method('areAuthorizationChecksDisabled')->will($this->returnValue(TRUE));
		$this->mockFirewall->expects($this->never())->method('blockIllegalRequests');

		$this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
	}

	/**
	 * @test
	 */
	public function dispatchInterceptsActionRequestsByDefault() {
		$this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(TRUE));

		$this->mockFirewall->expects($this->once())->method('blockIllegalRequests')->with($this->mockActionRequest);

		$this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Security\Exception\AuthenticationRequiredException
	 */
	public function dispatchRethrowsAuthenticationRequiredExceptionIfSecurityContextDoesNotContainAnyAuthenticationToken() {
		$this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(TRUE));

		$this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array()));

		$this->mockFirewall->expects($this->once())->method('blockIllegalRequests')->will($this->throwException(new AuthenticationRequiredException()));

		$this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
	}

	/**
	 * @test
	 */
	public function dispatchDoesNotSetInterceptedRequestIfAuthenticationTokensContainNoEntryPoint() {
		$this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(TRUE));

		$mockAuthenticationToken = $this->getMockBuilder('TYPO3\Flow\Security\Authentication\TokenInterface')->getMock();
		$mockAuthenticationToken->expects($this->any())->method('getAuthenticationEntryPoint')->will($this->returnValue(NULL));
		$this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockAuthenticationToken)));

		$this->mockSecurityContext->expects($this->never())->method('setInterceptedRequest')->with($this->mockMainRequest);

		$this->mockFirewall->expects($this->once())->method('blockIllegalRequests')->will($this->throwException(new AuthenticationRequiredException()));

		try {
			$this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
		} catch (AuthenticationRequiredException $exception) {
		}
	}

	/**
	 * @test
	 */
	public function dispatchSetsInterceptedRequestIfSecurityContextContainsAuthenticationTokensWithEntryPoints() {
		$this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(TRUE));

		$mockEntryPoint = $this->getMockBuilder('TYPO3\Flow\Security\Authentication\EntryPointInterface')->getMock();

		$mockAuthenticationToken = $this->getMockBuilder('TYPO3\Flow\Security\Authentication\TokenInterface')->getMock();
		$mockAuthenticationToken->expects($this->any())->method('getAuthenticationEntryPoint')->will($this->returnValue($mockEntryPoint));
		$this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockAuthenticationToken)));

		$this->mockSecurityContext->expects($this->atLeastOnce())->method('setInterceptedRequest')->with($this->mockMainRequest);

		$this->mockFirewall->expects($this->once())->method('blockIllegalRequests')->will($this->throwException(new AuthenticationRequiredException()));

		try {
			$this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
		} catch (AuthenticationRequiredException $exception) {
		}
	}

	/**
	 * @test
	 */
	public function dispatchCallsStartAuthenticationOnAllActiveEntryPoints() {
		$this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(TRUE));

		$mockAuthenticationToken1 = $this->getMockBuilder('TYPO3\Flow\Security\Authentication\TokenInterface')->getMock();
		$mockEntryPoint1 = $this->getMockBuilder('TYPO3\Flow\Security\Authentication\EntryPointInterface')->getMock();
		$mockAuthenticationToken1->expects($this->any())->method('getAuthenticationEntryPoint')->will($this->returnValue($mockEntryPoint1));

		$mockAuthenticationToken2 = $this->getMockBuilder('TYPO3\Flow\Security\Authentication\TokenInterface')->getMock();
		$mockEntryPoint2 = $this->getMockBuilder('TYPO3\Flow\Security\Authentication\EntryPointInterface')->getMock();
		$mockAuthenticationToken2->expects($this->any())->method('getAuthenticationEntryPoint')->will($this->returnValue($mockEntryPoint2));

		$this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockAuthenticationToken1, $mockAuthenticationToken2)));

		$this->mockFirewall->expects($this->once())->method('blockIllegalRequests')->will($this->throwException(new AuthenticationRequiredException()));

		$mockEntryPoint1->expects($this->once())->method('startAuthentication')->with($this->mockHttpRequest, $this->mockHttpResponse);
		$mockEntryPoint2->expects($this->once())->method('startAuthentication')->with($this->mockHttpRequest, $this->mockHttpResponse);

		try {
			$this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
		} catch (AuthenticationRequiredException $exception) {
		}
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Security\Exception\AccessDeniedException
	 */
	public function dispatchRethrowsAccessDeniedException() {
		$this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(TRUE));

		$this->mockFirewall->expects($this->once())->method('blockIllegalRequests')->will($this->throwException(new AccessDeniedException()));

		$this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
	}

	/**
	 * @test
	 */
	public function resolveControllerReturnsTheControllerSpecifiedInTheRequest() {
		$mockController = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerInterface');

		/** @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockObjectManager */
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with($this->equalTo('TYPO3\TestPackage\SomeController'))->will($this->returnValue($mockController));

		$mockRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerPackageKey', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue('TYPO3\TestPackage\SomeController'));

		/** @var Dispatcher|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
		$dispatcher = $this->getAccessibleMock('TYPO3\Flow\Mvc\Dispatcher', NULL);
		$dispatcher->injectObjectManager($mockObjectManager);

		$this->assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Controller\Exception\InvalidControllerException
	 */
	public function resolveControllerThrowsAnInvalidControllerExceptionIfTheResolvedControllerDoesNotImplementTheControllerInterface() {
		$mockController = $this->getMock('stdClass');

		/** @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockObjectManager */
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with($this->equalTo('TYPO3\TestPackage\SomeController'))->will($this->returnValue($mockController));

		$mockRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerPackageKey', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue('TYPO3\TestPackage\SomeController'));

		/** @var Dispatcher|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
		$dispatcher = $this->getAccessibleMock('TYPO3\Flow\Mvc\Dispatcher', array('dummy'));
		$dispatcher->injectObjectManager($mockObjectManager);

		$this->assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Controller\Exception\InvalidControllerException
	 */
	public function resolveControllerThrowsAnInvalidControllerExceptionIfTheResolvedControllerDoesNotExist() {
		$mockHttpRequest = $this->getMock('TYPO3\Flow\Http\Request', array(), array(), '', FALSE);
		$mockRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerObjectName', 'getHttpRequest'), array(), '', FALSE);
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue(''));
		$mockRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($mockHttpRequest));

		$dispatcher = $this->getAccessibleMock('TYPO3\Flow\Mvc\Dispatcher', array('dummy'));

		$dispatcher->_call('resolveController', $mockRequest);
	}

}
