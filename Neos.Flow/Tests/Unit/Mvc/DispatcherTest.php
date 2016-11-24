<?php
namespace Neos\Flow\Tests\Unit\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Cli\Request;
use Neos\Flow\Http\Response as HttpResponse;
use Neos\Flow\Http\Request as HttpRequest;
use Neos\Flow\Log\SecurityLoggerInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerInterface;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authentication\EntryPointInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Authorization\FirewallInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC Dispatcher
 */
class DispatcherTest extends UnitTestCase
{
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
    public function setUp()
    {
        $this->dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->setMethods(['resolveController'])->getMock();

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(false));

        $this->mockParentRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockParentRequest));

        $this->mockMainRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));

        $this->mockHttpRequest = $this->getMockBuilder(HttpRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));

        $this->mockHttpResponse = $this->getMockBuilder(HttpResponse::class)->disableOriginalConstructor()->getMock();

        $this->mockController = $this->getMockBuilder(ControllerInterface::class)->setMethods(['processRequest'])->getMock();
        $this->dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($this->mockController));

        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->mockFirewall = $this->getMockBuilder(FirewallInterface::class)->getMock();

        $this->mockSecurityLogger = $this->getMockBuilder(SecurityLoggerInterface::class)->getMock();

        $this->mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();
        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback(function ($className) {
            if ($className === Context::class) {
                return $this->mockSecurityContext;
            } elseif ($className === FirewallInterface::class) {
                return $this->mockFirewall;
            } elseif ($className === SecurityLoggerInterface::class) {
                return $this->mockSecurityLogger;
            }
            return null;
        }));
        $this->inject($this->dispatcher, 'objectManager', $this->mockObjectManager);
    }

    /**
     * @test
     */
    public function dispatchCallsTheControllersProcessRequestMethodUntilTheIsDispatchedFlagInTheRequestObjectIsSet()
    {
        $this->mockActionRequest->expects($this->at(0))->method('isDispatched')->will($this->returnValue(false));
        $this->mockActionRequest->expects($this->at(1))->method('isDispatched')->will($this->returnValue(false));
        $this->mockActionRequest->expects($this->at(2))->method('isDispatched')->will($this->returnValue(true));

        $this->mockController->expects($this->exactly(2))->method('processRequest')->with($this->mockActionRequest, $this->mockHttpResponse);

        $this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
    }

    /**
     * @test
     */
    public function dispatchIgnoresStopExceptionsForFirstLevelActionRequests()
    {
        $this->mockParentRequest->expects($this->at(0))->method('isDispatched')->will($this->returnValue(false));
        $this->mockParentRequest->expects($this->at(2))->method('isDispatched')->will($this->returnValue(true));
        $this->mockParentRequest->expects($this->atLeastOnce())->method('isMainRequest')->will($this->returnValue(true));

        $this->mockController->expects($this->atLeastOnce())->method('processRequest')->will($this->throwException(new StopActionException()));

        $this->dispatcher->dispatch($this->mockParentRequest, $this->mockHttpResponse);
    }

    /**
     * @test
     */
    public function dispatchCatchesStopExceptionOfActionRequestsAndRollsBackToTheParentRequest()
    {
        $this->mockActionRequest->expects($this->atLeastOnce())->method('isDispatched')->will($this->returnValue(false));
        $this->mockParentRequest->expects($this->atLeastOnce())->method('isDispatched')->will($this->returnValue(true));

        $this->mockController->expects($this->atLeastOnce())->method('processRequest')->will($this->throwException(new StopActionException()));

        $this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
    }

    /**
     * @test
     */
    public function dispatchContinuesWithNextRequestFoundInAForwardException()
    {
        /** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $nextRequest */
        $nextRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $nextRequest->expects($this->atLeastOnce())->method('isDispatched')->will($this->returnValue(true));

        $this->mockParentRequest->expects($this->atLeastOnce())->method('isDispatched')->will($this->returnValue(false));

        $this->mockController->expects($this->at(0))->method('processRequest')->with($this->mockActionRequest)->will($this->throwException(new StopActionException()));

        $forwardException = new ForwardException();
        $forwardException->setNextRequest($nextRequest);
        $this->mockController->expects($this->at(1))->method('processRequest')->with($this->mockParentRequest)->will($this->throwException($forwardException));

        $this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\InfiniteLoopException
     */
    public function dispatchThrowsAnInfiniteLoopExceptionIfTheRequestCouldNotBeDispachedAfter99Iterations()
    {
        $requestCallCounter = 0;
        $requestCallBack = function () use (&$requestCallCounter) {
            return ($requestCallCounter++ < 101) ? false : true;
        };
        $this->mockParentRequest->expects($this->any())->method('isDispatched')->will($this->returnCallBack($requestCallBack, '__invoke'));

        $this->dispatcher->dispatch($this->mockParentRequest, $this->mockHttpResponse);
    }

    /**
     * @test
     */
    public function dispatchDoesNotBlockCliRequests()
    {
        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $mockCliRequest */
        $mockCliRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $mockCliRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(true));

        $this->mockSecurityContext->expects($this->never())->method('areAuthorizationChecksDisabled')->will($this->returnValue(true));
        $this->mockFirewall->expects($this->never())->method('blockIllegalRequests');

        $this->dispatcher->dispatch($mockCliRequest, $this->mockHttpResponse);
    }

    /**
     * @test
     */
    public function dispatchDoesNotBlockRequestsIfAuthorizationChecksAreDisabled()
    {
        $this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(true));

        $this->mockSecurityContext->expects($this->any())->method('areAuthorizationChecksDisabled')->will($this->returnValue(true));
        $this->mockFirewall->expects($this->never())->method('blockIllegalRequests');

        $this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
    }

    /**
     * @test
     */
    public function dispatchInterceptsActionRequestsByDefault()
    {
        $this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(true));

        $this->mockFirewall->expects($this->once())->method('blockIllegalRequests')->with($this->mockActionRequest);

        $this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\AuthenticationRequiredException
     */
    public function dispatchRethrowsAuthenticationRequiredExceptionIfSecurityContextDoesNotContainAnyAuthenticationToken()
    {
        $this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(true));

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue([]));

        $this->mockFirewall->expects($this->once())->method('blockIllegalRequests')->will($this->throwException(new AuthenticationRequiredException()));

        $this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
    }

    /**
     * @test
     */
    public function dispatchDoesNotSetInterceptedRequestIfAuthenticationTokensContainNoEntryPoint()
    {
        $this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(true));

        $mockAuthenticationToken = $this->getMockBuilder(TokenInterface::class)->getMock();
        $mockAuthenticationToken->expects($this->any())->method('getAuthenticationEntryPoint')->will($this->returnValue(null));
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue([$mockAuthenticationToken]));

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
    public function dispatchSetsInterceptedRequestIfSecurityContextContainsAuthenticationTokensWithEntryPoints()
    {
        $this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(true));

        $mockEntryPoint = $this->getMockBuilder(EntryPointInterface::class)->getMock();

        $mockAuthenticationToken = $this->getMockBuilder(TokenInterface::class)->getMock();
        $mockAuthenticationToken->expects($this->any())->method('getAuthenticationEntryPoint')->will($this->returnValue($mockEntryPoint));
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue([$mockAuthenticationToken]));

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
    public function dispatchCallsStartAuthenticationOnAllActiveEntryPoints()
    {
        $this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(true));

        $mockAuthenticationToken1 = $this->getMockBuilder(TokenInterface::class)->getMock();
        $mockEntryPoint1 = $this->getMockBuilder(EntryPointInterface::class)->getMock();
        $mockAuthenticationToken1->expects($this->any())->method('getAuthenticationEntryPoint')->will($this->returnValue($mockEntryPoint1));

        $mockAuthenticationToken2 = $this->getMockBuilder(TokenInterface::class)->getMock();
        $mockEntryPoint2 = $this->getMockBuilder(EntryPointInterface::class)->getMock();
        $mockAuthenticationToken2->expects($this->any())->method('getAuthenticationEntryPoint')->will($this->returnValue($mockEntryPoint2));

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue([$mockAuthenticationToken1, $mockAuthenticationToken2]));

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
     * @expectedException \Neos\Flow\Security\Exception\AccessDeniedException
     */
    public function dispatchRethrowsAccessDeniedException()
    {
        $this->mockActionRequest->expects($this->any())->method('isDispatched')->will($this->returnValue(true));

        $this->mockFirewall->expects($this->once())->method('blockIllegalRequests')->will($this->throwException(new AccessDeniedException()));

        $this->dispatcher->dispatch($this->mockActionRequest, $this->mockHttpResponse);
    }

    /**
     * @test
     */
    public function resolveControllerReturnsTheControllerSpecifiedInTheRequest()
    {
        $mockController = $this->createMock(ControllerInterface::class);

        /** @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockObjectManager */
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with($this->equalTo('Flow\TestPackage\SomeController'))->will($this->returnValue($mockController));

        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerPackageKey', 'getControllerObjectName'])->getMock();
        $mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue('Flow\TestPackage\SomeController'));

        /** @var Dispatcher|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->getAccessibleMock(Dispatcher::class, null);
        $dispatcher->injectObjectManager($mockObjectManager);

        $this->assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Controller\Exception\InvalidControllerException
     */
    public function resolveControllerThrowsAnInvalidControllerExceptionIfTheResolvedControllerDoesNotImplementTheControllerInterface()
    {
        $mockController = $this->createMock('stdClass');

        /** @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockObjectManager */
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with($this->equalTo('Flow\TestPackage\SomeController'))->will($this->returnValue($mockController));

        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerPackageKey', 'getControllerObjectName'])->getMock();
        $mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue('Flow\TestPackage\SomeController'));

        /** @var Dispatcher|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->getAccessibleMock(Dispatcher::class, ['dummy']);
        $dispatcher->injectObjectManager($mockObjectManager);

        $this->assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Controller\Exception\InvalidControllerException
     */
    public function resolveControllerThrowsAnInvalidControllerExceptionIfTheResolvedControllerDoesNotExist()
    {
        $mockHttpRequest = $this->getMockBuilder(HttpRequest::class)->disableOriginalConstructor()->getMock();
        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName', 'getHttpRequest'])->getMock();
        $mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue(''));
        $mockRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($mockHttpRequest));

        $dispatcher = $this->getAccessibleMock(Dispatcher::class, ['dummy']);

        $dispatcher->_call('resolveController', $mockRequest);
    }
}
