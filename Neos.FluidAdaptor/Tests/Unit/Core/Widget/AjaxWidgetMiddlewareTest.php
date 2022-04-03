<?php
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
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\Core\Widget\AjaxWidgetMiddleware;
use Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder;
use Neos\FluidAdaptor\Core\Widget\WidgetContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Testcase for AjaxWidgetMiddleware
 *
 */
class AjaxWidgetMiddlewareTest extends UnitTestCase
{
    /**
     * @var AjaxWidgetMiddleware
     */
    protected $ajaxWidgetMiddleware;

    /**
     * @var ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockObjectManager;

    /**
     * @var RequestHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockRequestHandler;

    /**
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var ResponseInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpResponse;

    /**
     * @var AjaxWidgetContextHolder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockAjaxWidgetContextHolder;

    /**
     * @var HashService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHashService;

    /**
     * @var Dispatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockDispatcher;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSecurityContext;

    /**
     * @var \Neos\Flow\Property\PropertyMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPropertyMapper;

    /**
     * @var \Neos\Flow\Property\PropertyMappingConfiguration|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPropertyMappingConfiguration;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ActionRequestFactory
     */
    protected $mockActionRequestFactory;

    /**
     */
    protected function setUp(): void
    {
        $this->ajaxWidgetMiddleware = new AjaxWidgetMiddleware();

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpResponse = new Response();
        $this->mockHttpRequest->method('getQueryParams')->willreturn([]);
        $this->mockHttpRequest->method('getUploadedFiles')->willreturn([]);

        $this->mockRequestHandler = $this->getMockBuilder(RequestHandlerInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockRequestHandler->method('handle')->willReturn($this->mockHttpResponse);

        $this->mockAjaxWidgetContextHolder = $this->getMockBuilder(AjaxWidgetContextHolder::class)->getMock();
        $this->inject($this->ajaxWidgetMiddleware, 'ajaxWidgetContextHolder', $this->mockAjaxWidgetContextHolder);

        $this->mockActionRequestFactory = $this->getMockBuilder(ActionRequestFactory::class)->disableOriginalConstructor()->setMethods(['prepareActionRequest'])->getMock();

        $this->inject($this->ajaxWidgetMiddleware, 'actionRequestFactory', $this->mockActionRequestFactory);

        $this->mockHashService = $this->getMockBuilder(HashService::class)->getMock();
        $this->inject($this->ajaxWidgetMiddleware, 'hashService', $this->mockHashService);

        $this->mockDispatcher = $this->getMockBuilder(Dispatcher::class)->getMock();
        $this->inject($this->ajaxWidgetMiddleware, 'dispatcher', $this->mockDispatcher);

        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->getMock();
        $this->inject($this->ajaxWidgetMiddleware, 'securityContext', $this->mockSecurityContext);
    }

    /**
     * @test
     */
    public function handleDoesNotCreateActionRequestIfHttpRequestContainsNoWidgetContext()
    {
        $this->mockHttpRequest->method('getParsedBody')->willReturn([]);

        $this->mockObjectManager->expects(self::never())->method('get');

        $this->ajaxWidgetMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    /**
     * @test
     */
    public function handleSetsWidgetContextAndControllerObjectNameIfWidgetIdIsPresent()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->method('getParsedBody')->willReturn([
            '__widgetId' => $mockWidgetId,
        ]);

        $mockWidgetContext = $this->getMockBuilder(WidgetContext::class)->getMock();
        $mockWidgetContext->expects(self::atLeastOnce())->method('getControllerObjectName')->willReturn($mockControllerObjectName);
        $this->mockAjaxWidgetContextHolder->expects(self::atLeastOnce())->method('get')->with($mockWidgetId)->willReturn($mockWidgetContext);
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequestFactory->method('prepareActionRequest')->willReturn($mockActionRequest);

        $mockActionRequest->expects(self::once())->method('setArguments')->with(['__widgetContext' =>  $mockWidgetContext, '__widgetId' => 'SomeWidgetId']);
        $mockActionRequest->expects(self::once())->method('setControllerObjectName')->with($mockControllerObjectName);

        $this->ajaxWidgetMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    /**
     * @test
     */
    public function handleDispatchesActionRequestIfWidgetContextIsPresent()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->expects(self::any())->method('getParsedBody')->willReturn([
            '__widgetId' => $mockWidgetId,
        ]);

        $mockWidgetContext = $this->getMockBuilder(WidgetContext::class)->getMock();
        $mockWidgetContext->expects(self::atLeastOnce())->method('getControllerObjectName')->willReturn($mockControllerObjectName);
        $this->mockAjaxWidgetContextHolder->expects(self::atLeastOnce())->method('get')->with($mockWidgetId)->willReturn($mockWidgetContext);
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequestFactory->method('prepareActionRequest')->willReturn($mockActionRequest);

        $this->mockDispatcher->expects(self::once())->method('dispatch');

        $this->ajaxWidgetMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    /**
     * @test
     */
    public function handleCancelsComponentChainIfWidgetContextIsPresent()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->method('getParsedBody')->willReturn([
            '__widgetId' => $mockWidgetId,
        ]);

        $mockWidgetContext = $this->getMockBuilder(WidgetContext::class)->getMock();
        $mockWidgetContext->expects(self::atLeastOnce())->method('getControllerObjectName')->willReturn($mockControllerObjectName);
        $this->mockAjaxWidgetContextHolder->expects(self::atLeastOnce())->method('get')->with($mockWidgetId)->willReturn($mockWidgetContext);
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequestFactory->method('prepareActionRequest')->willReturn($mockActionRequest);

        $response = $this->ajaxWidgetMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
        self::assertNotSame($this->mockHttpResponse, $response);
    }

    /**
     * @test
     */
    public function extractWidgetContextDecodesSerializedWidgetContextIfPresent()
    {
        $ajaxWidgetComponent = $this->getAccessibleMock(AjaxWidgetMiddleware::class, ['dummy']);
        $this->inject($ajaxWidgetComponent, 'hashService', $this->mockHashService);

        $mockWidgetContext = new WidgetContext();
        $mockSerializedWidgetContext = base64_encode(serialize($mockWidgetContext));
        $mockSerializedWidgetContextWithHmac = $mockSerializedWidgetContext . 'HMAC';

        $this->mockHttpRequest->method('getParsedBody')->willReturn([
            '__widgetContext' => $mockSerializedWidgetContextWithHmac
        ]);

        $this->mockHashService->expects(self::atLeastOnce())->method('validateAndStripHmac')->with($mockSerializedWidgetContextWithHmac)->willReturn($mockSerializedWidgetContext);

        $actualResult = $ajaxWidgetComponent->_call('extractWidgetContext', $this->mockHttpRequest);
        self::assertEquals($mockWidgetContext, $actualResult);
    }
}
