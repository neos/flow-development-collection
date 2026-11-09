<?php

declare(strict_types=1);

namespace Neos\FluidAdaptor\Tests\Unit\Core\Widget;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use GuzzleHttp\Psr7\Response;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionRequestFactory;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder;
use Neos\FluidAdaptor\Core\Widget\AjaxWidgetMiddleware;
use Neos\FluidAdaptor\Core\Widget\WidgetContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Testcase for AjaxWidgetMiddleware
 *
 */
final class AjaxWidgetMiddlewareTest extends UnitTestCase
{
    /**
     * @var AjaxWidgetMiddleware
     */
    protected $ajaxWidgetMiddleware;

    /**
     * @var RequestHandlerInterface|MockObject
     */
    protected $mockRequestHandler;

    /**
     * @var ServerRequestInterface|MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var ResponseInterface|MockObject
     */
    protected $mockHttpResponse;

    /**
     * @var AjaxWidgetContextHolder|MockObject
     */
    protected $mockAjaxWidgetContextHolder;

    /**
     * @var HashService|MockObject
     */
    protected $mockHashService;

    /**
     * @var Dispatcher|MockObject
     */
    protected $mockDispatcher;

    /**
     * @var MockObject|ActionRequestFactory
     */
    protected $mockActionRequestFactory;

    /**
     */
    protected function setUp(): void
    {
        $this->ajaxWidgetMiddleware = new AjaxWidgetMiddleware();

        $this->mockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $this->mockHttpResponse = new Response();
        $this->mockHttpRequest->method('getQueryParams')->willreturn([]);
        $this->mockHttpRequest->method('getUploadedFiles')->willreturn([]);

        $this->mockRequestHandler = $this->createMock(RequestHandlerInterface::class);
        $this->mockRequestHandler->method('handle')->willReturn($this->mockHttpResponse);

        $this->mockAjaxWidgetContextHolder = $this->createMock(AjaxWidgetContextHolder::class);
        $this->inject($this->ajaxWidgetMiddleware, 'ajaxWidgetContextHolder', $this->mockAjaxWidgetContextHolder);

        $this->mockActionRequestFactory = $this->getMockBuilder(ActionRequestFactory::class)->disableOriginalConstructor()->onlyMethods(['prepareActionRequest'])->getMock();

        $this->inject($this->ajaxWidgetMiddleware, 'actionRequestFactory', $this->mockActionRequestFactory);

        $this->mockHashService = $this->createMock(HashService::class);
        $this->inject($this->ajaxWidgetMiddleware, 'hashService', $this->mockHashService);

        $this->mockDispatcher = $this->createMock(Dispatcher::class);
        $this->mockDispatcher->expects(self::any())->method('dispatch')->willReturn(new Response());
        $this->inject($this->ajaxWidgetMiddleware, 'dispatcher', $this->mockDispatcher);
        $this->inject($this->ajaxWidgetMiddleware, 'securityContext', $this->createStub(Context::class));
    }

    #[Test]
    public function handleSetsWidgetContextAndControllerObjectNameIfWidgetIdIsPresent()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->method('getParsedBody')->willReturn([
            '__widgetId' => $mockWidgetId,
        ]);

        $mockWidgetContext = $this->createMock(WidgetContext::class);
        $mockWidgetContext->expects($this->atLeastOnce())->method('getControllerObjectName')->willReturn($mockControllerObjectName);
        $this->mockAjaxWidgetContextHolder->expects($this->atLeastOnce())->method('get')->with($mockWidgetId)->willReturn($mockWidgetContext);
        $mockActionRequest = $this->createMock(ActionRequest::class);
        $this->mockActionRequestFactory->method('prepareActionRequest')->willReturn($mockActionRequest);

        $mockActionRequest->expects($this->once())->method('setArguments')->with(['__widgetContext' =>  $mockWidgetContext, '__widgetId' => 'SomeWidgetId']);
        $mockActionRequest->expects($this->once())->method('setControllerObjectName')->with($mockControllerObjectName);

        $this->ajaxWidgetMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    #[Test]
    public function handleDispatchesActionRequestIfWidgetContextIsPresent()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->method('getParsedBody')->willReturn([
            '__widgetId' => $mockWidgetId,
        ]);

        $mockWidgetContext = $this->createMock(WidgetContext::class);
        $mockWidgetContext->expects($this->atLeastOnce())->method('getControllerObjectName')->willReturn($mockControllerObjectName);
        $this->mockAjaxWidgetContextHolder->expects($this->atLeastOnce())->method('get')->with($mockWidgetId)->willReturn($mockWidgetContext);
        $mockActionRequest = $this->createStub(ActionRequest::class);
        $this->mockActionRequestFactory->method('prepareActionRequest')->willReturn($mockActionRequest);

        $this->mockDispatcher->expects($this->once())->method('dispatch')->willReturn(new Response());

        $this->ajaxWidgetMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    #[Test]
    public function handleCancelsComponentChainIfWidgetContextIsPresent()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->method('getParsedBody')->willReturn([
            '__widgetId' => $mockWidgetId,
        ]);

        $mockWidgetContext = $this->createMock(WidgetContext::class);
        $mockWidgetContext->expects($this->atLeastOnce())->method('getControllerObjectName')->willReturn($mockControllerObjectName);
        $this->mockAjaxWidgetContextHolder->expects($this->atLeastOnce())->method('get')->with($mockWidgetId)->willReturn($mockWidgetContext);
        $mockActionRequest = $this->createStub(ActionRequest::class);
        $this->mockActionRequestFactory->method('prepareActionRequest')->willReturn($mockActionRequest);

        $response = $this->ajaxWidgetMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
        self::assertNotSame($this->mockHttpResponse, $response);
    }

    #[Test]
    public function extractWidgetContextDecodesSerializedWidgetContextIfPresent()
    {
        $ajaxWidgetComponent = $this->getAccessibleMock(AjaxWidgetMiddleware::class, []);
        $this->inject($ajaxWidgetComponent, 'hashService', $this->mockHashService);

        $mockWidgetContext = new WidgetContext();
        $mockSerializedWidgetContext = base64_encode(serialize($mockWidgetContext));
        $mockSerializedWidgetContextWithHmac = $mockSerializedWidgetContext . 'HMAC';

        $this->mockHttpRequest->method('getParsedBody')->willReturn([
            '__widgetContext' => $mockSerializedWidgetContextWithHmac
        ]);

        $this->mockHashService->expects($this->atLeastOnce())->method('validateAndStripHmac')->with($mockSerializedWidgetContextWithHmac)->willReturn($mockSerializedWidgetContext);

        $actualResult = $ajaxWidgetComponent->_call('extractWidgetContext', $this->mockHttpRequest);
        self::assertEquals($mockWidgetContext, $actualResult);
    }
}
