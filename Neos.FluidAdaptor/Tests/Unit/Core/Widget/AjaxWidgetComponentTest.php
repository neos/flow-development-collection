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
use Neos\Flow\Http\Component\ComponentChain;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionRequestFactory;
use Neos\Flow\Mvc\DispatchComponent;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Mvc\Routing\RoutingComponent;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\Core\Widget\AjaxWidgetComponent;
use Neos\FluidAdaptor\Core\Widget\AjaxWidgetContextHolder;
use Neos\FluidAdaptor\Core\Widget\WidgetContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Testcase for AjaxWidgetComponent
 *
 */
class AjaxWidgetComponentTest extends UnitTestCase
{
    /**
     * @var AjaxWidgetComponent
     */
    protected $ajaxWidgetComponent;

    /**
     * @var ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockObjectManager;

    /**
     * @var ComponentContext|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockComponentContext;

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
        $this->ajaxWidgetComponent = new AjaxWidgetComponent();

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);

        $this->mockComponentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->getMock();

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpResponse = new Response();
        $this->mockHttpRequest->expects(self::any())->method('getQueryParams')->willreturn([]);
        $this->mockHttpRequest->expects(self::any())->method('getUploadedFiles')->willreturn([]);
        $this->mockComponentContext->expects(self::any())->method('getHttpRequest')->will(self::returnValue($this->mockHttpRequest));
        $this->mockComponentContext->expects(self::any())->method('getHttpResponse')->willReturn($this->mockHttpResponse);

        $this->mockAjaxWidgetContextHolder = $this->getMockBuilder(AjaxWidgetContextHolder::class)->getMock();
        $this->inject($this->ajaxWidgetComponent, 'ajaxWidgetContextHolder', $this->mockAjaxWidgetContextHolder);

        $this->mockActionRequestFactory = $this->getMockBuilder(ActionRequestFactory::class)->disableOriginalConstructor()->setMethods(['prepareActionRequest'])->getMock();

        $this->inject($this->ajaxWidgetComponent, 'actionRequestFactory', $this->mockActionRequestFactory);

        $this->mockHashService = $this->getMockBuilder(HashService::class)->getMock();
        $this->inject($this->ajaxWidgetComponent, 'hashService', $this->mockHashService);

        $this->mockDispatcher = $this->getMockBuilder(Dispatcher::class)->getMock();
        $this->inject($this->ajaxWidgetComponent, 'dispatcher', $this->mockDispatcher);

        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->getMock();
        $this->inject($this->ajaxWidgetComponent, 'securityContext', $this->mockSecurityContext);
    }

    /**
     * @test
     */
    public function handleDoesNotCreateActionRequestIfHttpRequestContainsNoWidgetContext()
    {
        $this->mockHttpRequest->expects(self::any())->method('getParsedBody')->willReturn([]);

        $this->mockObjectManager->expects(self::never())->method('get');

        $this->ajaxWidgetComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleSetsWidgetContextAndControllerObjectNameIfWidgetIdIsPresent()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->expects(self::any())->method('getParsedBody')->willReturn([
            '__widgetId' => $mockWidgetId,
        ]);

        $mockWidgetContext = $this->getMockBuilder(WidgetContext::class)->getMock();
        $mockWidgetContext->expects(self::atLeastOnce())->method('getControllerObjectName')->will(self::returnValue($mockControllerObjectName));
        $this->mockAjaxWidgetContextHolder->expects(self::atLeastOnce())->method('get')->with($mockWidgetId)->will(self::returnValue($mockWidgetContext));
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequestFactory->expects(self::any())->method('prepareActionRequest')->willReturn($mockActionRequest);
        $this->mockComponentContext->expects(self::any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', []],
            [DispatchComponent::class, 'actionRequest', $mockActionRequest]
        ]);

        $mockActionRequest->expects(self::once())->method('setArguments')->with(['__widgetContext' =>  $mockWidgetContext, '__widgetId' => 'SomeWidgetId']);
        $mockActionRequest->expects(self::once())->method('setControllerObjectName')->with($mockControllerObjectName);

        $this->ajaxWidgetComponent->handle($this->mockComponentContext);
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
        $mockWidgetContext->expects(self::atLeastOnce())->method('getControllerObjectName')->will(self::returnValue($mockControllerObjectName));
        $this->mockAjaxWidgetContextHolder->expects(self::atLeastOnce())->method('get')->with($mockWidgetId)->will(self::returnValue($mockWidgetContext));
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequestFactory->expects(self::any())->method('prepareActionRequest')->willReturn($mockActionRequest);
        $this->mockComponentContext->expects(self::any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', []],
            [DispatchComponent::class, 'actionRequest', $mockActionRequest]
        ]);
        $this->mockDispatcher->expects(self::once())->method('dispatch');

        $this->ajaxWidgetComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleCancelsComponentChainIfWidgetContextIsPresent()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->expects(self::any())->method('getParsedBody')->willReturn([
            '__widgetId' => $mockWidgetId,
        ]);

        $mockWidgetContext = $this->getMockBuilder(WidgetContext::class)->getMock();
        $mockWidgetContext->expects(self::atLeastOnce())->method('getControllerObjectName')->will(self::returnValue($mockControllerObjectName));
        $this->mockAjaxWidgetContextHolder->expects(self::atLeastOnce())->method('get')->with($mockWidgetId)->will(self::returnValue($mockWidgetContext));
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequestFactory->expects(self::any())->method('prepareActionRequest')->willReturn($mockActionRequest);
        $this->mockComponentContext->expects(self::any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', []],
            [DispatchComponent::class, 'actionRequest', $mockActionRequest]
        ]);
        $this->mockComponentContext->expects(self::any())->method('setParameter')->with(ComponentChain::class, 'cancel', true);

        $this->ajaxWidgetComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleInjectsActionRequestToSecurityContext()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->expects(self::any())->method('getParsedBody')->willReturn([
            '__widgetId' => $mockWidgetId,
        ]);

        $mockWidgetContext = $this->getMockBuilder(WidgetContext::class)->getMock();
        $mockWidgetContext->expects(self::atLeastOnce())->method('getControllerObjectName')->will(self::returnValue($mockControllerObjectName));
        $this->mockAjaxWidgetContextHolder->expects(self::atLeastOnce())->method('get')->with($mockWidgetId)->will(self::returnValue($mockWidgetContext));
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequestFactory->expects(self::any())->method('prepareActionRequest')->willReturn($mockActionRequest);
        $this->mockComponentContext->expects(self::any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', []],
            [DispatchComponent::class, 'actionRequest', $mockActionRequest]
        ]);

        $this->mockSecurityContext->expects(self::once())->method('setRequest')->with($mockActionRequest);

        $this->ajaxWidgetComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function extractWidgetContextDecodesSerializedWidgetContextIfPresent()
    {
        $ajaxWidgetComponent = $this->getAccessibleMock(AjaxWidgetComponent::class, ['dummy']);
        $this->inject($ajaxWidgetComponent, 'hashService', $this->mockHashService);

        $mockWidgetContext = new WidgetContext();
        $mockSerializedWidgetContext = base64_encode(serialize($mockWidgetContext));
        $mockSerializedWidgetContextWithHmac = $mockSerializedWidgetContext . 'HMAC';

        $this->mockHttpRequest->expects(self::any())->method('getParsedBody')->willReturn([
            '__widgetContext' => $mockSerializedWidgetContextWithHmac
        ]);

        $this->mockHashService->expects(self::atLeastOnce())->method('validateAndStripHmac')->with($mockSerializedWidgetContextWithHmac)->will(self::returnValue($mockSerializedWidgetContext));

        $actualResult = $ajaxWidgetComponent->_call('extractWidgetContext', $this->mockHttpRequest);
        self::assertEquals($mockWidgetContext, $actualResult);
    }
}
