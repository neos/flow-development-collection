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

use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\ControllerInterface;
use Neos\Flow\Mvc\Controller\Exception\InvalidControllerException;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\InfiniteLoopException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authorization\FirewallInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

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
     * @var ActionRequest|MockObject
     */
    protected $mockParentRequest;

    /**
     * @var ActionRequest|MockObject
     */
    protected $mockActionRequest;

    /**
     * @var ActionRequest|MockObject
     */
    protected $mockMainRequest;

    /**
     * @var ServerRequestInterface|MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var ActionResponse
     */
    protected $actionResponse;

    /**
     * @var ControllerInterface|MockObject
     */
    protected $mockController;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $mockObjectManager;

    /**
     * @var Context|MockObject
     */
    protected $mockSecurityContext;

    /**
     * @var FirewallInterface|MockObject
     */
    protected $mockFirewall;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $mockSecurityLogger;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        $this->dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->setMethods(['resolveController'])->getMock();

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->method('isMainRequest')->willReturn(false);

        $this->mockParentRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->method('getParentRequest')->willReturn($this->mockParentRequest);

        $this->mockMainRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->method('getMainRequest')->willReturn($this->mockMainRequest);

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->method('getHttpRequest')->willReturn($this->mockHttpRequest);

        $this->actionResponse = new ActionResponse();

        $this->mockController = $this->getMockBuilder(ControllerInterface::class)->setMethods(['processRequest'])->getMock();
        $this->dispatcher->method('resolveController')->willReturn($this->mockController);

        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->mockFirewall = $this->getMockBuilder(FirewallInterface::class)->getMock();

        $this->mockSecurityLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $mockLoggerFactory = $this->getMockBuilder(PsrLoggerFactoryInterface::class)->getMock();
        $mockLoggerFactory->expects(self::any())->method('get')->with('securityLogger')->willReturn($this->mockSecurityLogger);

        $this->mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();
        $this->mockObjectManager->method('get')->will(self::returnCallBack(function ($className) use ($mockLoggerFactory) {
            if ($className === PsrLoggerFactoryInterface::class) {
                return $mockLoggerFactory;
            }
            return null;
        }));

        $this->dispatcher->injectObjectManager($this->mockObjectManager);
        $this->dispatcher->injectSecurityContext($this->mockSecurityContext);
        $this->dispatcher->injectFirewall($this->mockFirewall);
    }

    /**
     * @test
     */
    public function dispatchCallsTheControllersProcessRequestMethodUntilTheIsDispatchedFlagInTheRequestObjectIsSet()
    {
        $this->mockActionRequest->expects(self::exactly(3))->method('isDispatched')->willReturnOnConsecutiveCalls(false, false, true);

        $this->mockController->expects(self::exactly(2))->method('processRequest')->with($this->mockActionRequest);

        $this->dispatcher->dispatch($this->mockActionRequest, $this->actionResponse);
    }

    /**
     * @test
     */
    public function dispatchIgnoresStopExceptionsForFirstLevelActionRequests()
    {
        $this->mockParentRequest->expects(self::exactly(2))->method('isDispatched')->willReturnOnConsecutiveCalls(false, true);
        $this->mockParentRequest->expects(self::once())->method('isMainRequest')->willReturn(true);

        $this->mockController->expects(self::atLeastOnce())->method('processRequest')->will(self::throwException(new StopActionException()));

        $this->dispatcher->dispatch($this->mockParentRequest, $this->actionResponse);
    }

    /**
     * @test
     */
    public function dispatchCatchesStopExceptionOfActionRequestsAndRollsBackToTheParentRequest()
    {
        $this->mockActionRequest->expects(self::atLeastOnce())->method('isDispatched')->willReturn(false);
        $this->mockParentRequest->expects(self::atLeastOnce())->method('isDispatched')->willReturn(true);

        $this->mockController->expects(self::atLeastOnce())->method('processRequest')->will(self::throwException(new StopActionException()));

        $this->dispatcher->dispatch($this->mockActionRequest, $this->actionResponse);
    }

    /**
     * @test
     */
    public function dispatchContinuesWithNextRequestFoundInAForwardException()
    {
        /** @var ActionRequest|MockObject $nextRequest */
        $nextRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $nextRequest->expects(self::atLeastOnce())->method('isDispatched')->willReturn(true);
        $forwardException = new ForwardException();
        $forwardException->setNextRequest($nextRequest);

        $this->mockParentRequest->expects(self::atLeastOnce())->method('isDispatched')->willReturn(false);

        $this->mockController->expects(self::exactly(2))->method('processRequest')
            ->withConsecutive([$this->mockActionRequest], [$this->mockParentRequest])
            ->willReturnOnConsecutiveCalls(self::throwException(new StopActionException()), self::throwException($forwardException));

        $this->dispatcher->dispatch($this->mockActionRequest, $this->actionResponse);
    }

    /**
     * @test
     */
    public function dispatchThrowsAnInfiniteLoopExceptionIfTheRequestCouldNotBeDispachedAfter99Iterations()
    {
        $this->expectException(InfiniteLoopException::class);
        $requestCallCounter = 0;
        $requestCallBack = function () use (&$requestCallCounter) {
            return ($requestCallCounter++ < 101) ? false : true;
        };
        $this->mockParentRequest->method('isDispatched')->will(self::returnCallback($requestCallBack, '__invoke'));

        $this->dispatcher->dispatch($this->mockParentRequest, $this->actionResponse);
    }

    /**
     * @test
     */
    public function dispatchDoesNotBlockRequestsIfAuthorizationChecksAreDisabled()
    {
        $this->mockActionRequest->method('isDispatched')->willReturn(true);

        $this->mockSecurityContext->method('areAuthorizationChecksDisabled')->willReturn(true);
        $this->mockFirewall->expects(self::never())->method('blockIllegalRequests');

        $this->dispatcher->dispatch($this->mockActionRequest, $this->actionResponse);
    }

    /**
     * @test
     */
    public function dispatchInterceptsActionRequestsByDefault()
    {
        $this->mockActionRequest->method('isDispatched')->willReturn(true);

        $this->mockFirewall->expects(self::once())->method('blockIllegalRequests')->with($this->mockActionRequest);

        $this->dispatcher->dispatch($this->mockActionRequest, $this->actionResponse);
    }

    /**
     * @test
     */
    public function dispatchThrowsAuthenticationExceptions()
    {
        $this->expectException(AuthenticationRequiredException::class);
        $this->mockActionRequest->method('isDispatched')->willReturn(true);

        $this->mockSecurityContext->expects(self::never())->method('setInterceptedRequest')->with($this->mockMainRequest);

        $this->mockFirewall->expects(self::once())->method('blockIllegalRequests')->will(self::throwException(new AuthenticationRequiredException()));

        $this->dispatcher->dispatch($this->mockActionRequest, $this->actionResponse);
    }

    /**
     * @test
     */
    public function dispatchRethrowsAccessDeniedException()
    {
        $this->expectException(AccessDeniedException::class);
        $this->mockActionRequest->method('isDispatched')->willReturn(true);

        $this->mockFirewall->expects(self::once())->method('blockIllegalRequests')->will(self::throwException(new AccessDeniedException()));

        $this->dispatcher->dispatch($this->mockActionRequest, $this->actionResponse);
    }

    /**
     * @test
     */
    public function resolveControllerReturnsTheControllerSpecifiedInTheRequest()
    {
        $mockController = $this->createMock(ControllerInterface::class);

        /** @var ObjectManagerInterface|MockObject $mockObjectManager */
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('get')->with(self::equalTo('Flow\TestPackage\SomeController'))->willReturn($mockController);

        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerPackageKey', 'getControllerObjectName'])->getMock();
        $mockRequest->method('getControllerObjectName')->willReturn('Flow\TestPackage\SomeController');

        /** @var Dispatcher|MockObject $dispatcher */
        $dispatcher = $this->getAccessibleMock(Dispatcher::class, null);
        $dispatcher->injectObjectManager($mockObjectManager);

        self::assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
    }

    /**
     * @test
     */
    public function resolveControllerThrowsAnInvalidControllerExceptionIfTheResolvedControllerDoesNotImplementTheControllerInterface()
    {
        $this->expectException(InvalidControllerException::class);
        $mockController = $this->createMock('stdClass');

        /** @var ObjectManagerInterface|MockObject $mockObjectManager */
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('get')->with(self::equalTo('Flow\TestPackage\SomeController'))->willReturn($mockController);

        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerPackageKey', 'getControllerObjectName'])->getMock();
        $mockRequest->method('getControllerObjectName')->willReturn('Flow\TestPackage\SomeController');

        /** @var Dispatcher|MockObject $dispatcher */
        $dispatcher = $this->getAccessibleMock(Dispatcher::class, ['dummy']);
        $dispatcher->injectObjectManager($mockObjectManager);

        self::assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
    }

    /**
     * @test
     */
    public function resolveControllerThrowsAnInvalidControllerExceptionIfTheResolvedControllerDoesNotExist()
    {
        $this->expectException(InvalidControllerException::class);
        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName', 'getHttpRequest'])->getMock();
        $mockRequest->method('getControllerObjectName')->willReturn('');
        $mockRequest->method('getHttpRequest')->willReturn($mockHttpRequest);

        $dispatcher = $this->getAccessibleMock(Dispatcher::class, ['dummy']);

        $dispatcher->_call('resolveController', $mockRequest);
    }
}
