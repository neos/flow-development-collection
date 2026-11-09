<?php

declare(strict_types=1);

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

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Flow\Mvc\ActionRequest;
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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the MVC Dispatcher
 */
final class DispatcherTest extends UnitTestCase
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
     * @var ControllerInterface|MockObject
     */
    protected $mockController;

    /**
     * @var Context|MockObject
     */
    protected $mockSecurityContext;

    /**
     * @var FirewallInterface|MockObject
     */
    protected $mockFirewall;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        $this->dispatcher = $this->getMockBuilder(Dispatcher::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['resolveController'])
            ->getMock();

        $this->mockActionRequest = $this->createMock(ActionRequest::class);
        $this->mockActionRequest->method('isMainRequest')->willReturn(false);

        $this->mockParentRequest = $this->createMock(ActionRequest::class);
        $this->mockParentRequest->method('isMainRequest')->willReturn(true);
        $this->mockActionRequest->method('getParentRequest')->willReturn($this->mockParentRequest);
        $this->mockActionRequest->method('getMainRequest')->willReturn($this->createStub(ActionRequest::class));

        $mockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $this->mockActionRequest->method('getHttpRequest')->willReturn($mockHttpRequest);

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->method('getHttpRequest')->willReturn($this->mockHttpRequest);

        $this->mockController = $this->getMockBuilder(ControllerInterface::class)->onlyMethods(['processRequest'])->getMock();
        $this->dispatcher->expects(self::any())->method('resolveController')->withAnyParameters()->willReturn($this->mockController);

        $this->mockSecurityContext = $this->createMock(Context::class);

        $this->mockFirewall = $this->createMock(FirewallInterface::class);

        $mockSecurityLogger = $this->createMock(LoggerInterface::class);
        $mockLoggerFactory = $this->createMock(PsrLoggerFactoryInterface::class);
        $mockLoggerFactory->method('get')->with('securityLogger')->willReturn($mockSecurityLogger);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('get')->willReturnCallback(function ($className) use ($mockLoggerFactory) {
            if ($className === PsrLoggerFactoryInterface::class) {
                return $mockLoggerFactory;
            }
            return null;
        });

        $this->dispatcher->injectObjectManager($mockObjectManager);
        $this->dispatcher->injectSecurityContext($this->mockSecurityContext);
        $this->dispatcher->injectFirewall($this->mockFirewall);
    }

    #[Test]
    public function dispatchIgnoresStopExceptionsForFirstLevelActionRequests()
    {
        $this->mockController->expects($this->atLeastOnce())->method('processRequest')->willThrowException(StopActionException::createForResponse(new Response(), ''));

        $this->dispatcher->dispatch($this->mockParentRequest);
    }

    #[Test]
    public function dispatchCatchesStopExceptionOfActionRequestsAndRollsBackToTheParentRequest()
    {
        $this->mockController->expects($this->atLeastOnce())->method('processRequest')->willThrowException(StopActionException::createForResponse(new Response(), ''));

        $this->dispatcher->dispatch($this->mockActionRequest);
    }

    #[Test]
    public function dispatchContinuesWithNextRequestFoundInAForwardException()
    {
        /** @var ActionRequest|MockObject $nextRequest */
        $nextRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $nextRequest->method('isMainRequest')->willReturn(true);
        $stopException = StopActionException::createForResponse(new Response(), '');
        $forwardException = ForwardException::createForNextRequest($nextRequest, '');

        $matcher = self::exactly(2);

        $this->mockController->expects($matcher)->method('processRequest')->willReturnCallback(function (...$parameters) use ($matcher, $nextRequest, $stopException, $forwardException) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame($this->mockActionRequest, $parameters[0]);
                throw $forwardException;
            }
            // the dispatch loop must continue with the request carried by the ForwardException
            $this->assertSame($nextRequest, $parameters[0]);
            throw $stopException;
        });

        $this->dispatcher->dispatch($this->mockActionRequest);
    }

    #[Test]
    public function dispatchThrowsAnInfiniteLoopExceptionIfTheRequestCouldNotBeDispachedAfter99Iterations()
    {
        $forwardException = ForwardException::createForNextRequest($this->mockActionRequest, '');

        $this->mockController->expects(self::any())->method('processRequest')->with($this->mockActionRequest)->willThrowException($forwardException);

        $this->expectException(InfiniteLoopException::class);

        $this->dispatcher->dispatch($this->mockParentRequest);
    }

    #[Test]
    public function dispatchDoesNotBlockRequestsIfAuthorizationChecksAreDisabled()
    {
        $this->mockSecurityContext->method('areAuthorizationChecksDisabled')->willReturn(true);
        $this->mockFirewall->expects($this->never())->method('blockIllegalRequests');
        $this->mockController->expects(self::any())->method('processRequest')->with($this->mockActionRequest)->willReturn(new Response());

        $this->dispatcher->dispatch($this->mockActionRequest);
    }

    #[Test]
    public function dispatchInterceptsActionRequestsByDefault()
    {
        $this->mockFirewall->expects($this->once())->method('blockIllegalRequests')->with($this->mockActionRequest);
        $this->mockController->method('processRequest')->with($this->mockActionRequest)->willReturn(new Response());

        $this->dispatcher->dispatch($this->mockActionRequest);
    }

    #[Test]
    public function dispatchThrowsAuthenticationExceptions()
    {
        $this->expectException(AuthenticationRequiredException::class);
        $this->mockSecurityContext->expects($this->never())->method('setInterceptedRequest')->with($this->createStub(ActionRequest::class));

        $this->mockFirewall->expects($this->once())->method('blockIllegalRequests')->willThrowException(new AuthenticationRequiredException());

        $this->dispatcher->dispatch($this->mockActionRequest);
    }

    #[Test]
    public function dispatchRethrowsAccessDeniedException()
    {
        $this->expectException(AccessDeniedException::class);
        $this->mockFirewall->expects($this->once())->method('blockIllegalRequests')->willThrowException(new AccessDeniedException());

        $this->dispatcher->dispatch($this->mockActionRequest);
    }

    #[Test]
    public function resolveControllerReturnsTheControllerSpecifiedInTheRequest()
    {
        $mockController = $this->createStub(ControllerInterface::class);

        /** @var ObjectManagerInterface|MockObject $mockObjectManager */
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with(self::equalTo('Flow\TestPackage\SomeController'))->willReturn($mockController);

        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->onlyMethods(['getControllerPackageKey', 'getControllerObjectName'])->getMock();
        $mockRequest->method('getControllerObjectName')->willReturn('Flow\TestPackage\SomeController');

        /** @var Dispatcher|MockObject $dispatcher */
        $dispatcher = $this->getAccessibleMock(Dispatcher::class, []);
        $dispatcher->injectObjectManager($mockObjectManager);

        self::assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
    }

    #[Test]
    public function resolveControllerThrowsAnInvalidControllerExceptionIfTheResolvedControllerDoesNotImplementTheControllerInterface()
    {
        $this->expectException(InvalidControllerException::class);
        $mockController = $this->createStub('stdClass');

        /** @var ObjectManagerInterface|MockObject $mockObjectManager */
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with(self::equalTo('Flow\TestPackage\SomeController'))->willReturn($mockController);

        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->onlyMethods(['getControllerPackageKey', 'getControllerObjectName'])->getMock();
        $mockRequest->method('getControllerObjectName')->willReturn('Flow\TestPackage\SomeController');

        /** @var Dispatcher|MockObject $dispatcher */
        $dispatcher = $this->getAccessibleMock(Dispatcher::class, []);
        $dispatcher->injectObjectManager($mockObjectManager);

        self::assertEquals($mockController, $dispatcher->_call('resolveController', $mockRequest));
    }

    #[Test]
    public function resolveControllerThrowsAnInvalidControllerExceptionIfTheResolvedControllerDoesNotExist()
    {
        $this->expectException(InvalidControllerException::class);
        $mockHttpRequest = $this->createStub(ServerRequestInterface::class);
        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->onlyMethods(['getControllerObjectName', 'getHttpRequest'])->getMock();
        $mockRequest->method('getControllerObjectName')->willReturn('');
        $mockRequest->method('getHttpRequest')->willReturn($mockHttpRequest);

        $dispatcher = $this->getAccessibleMock(Dispatcher::class, []);

        $dispatcher->_call('resolveController', $mockRequest);
    }
}
