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
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var ComponentContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockComponentContext;

    /**
     * @var ServerRequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpResponse;

    /**
     * @var AjaxWidgetContextHolder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockAjaxWidgetContextHolder;

    /**
     * @var HashService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHashService;

    /**
     * @var Dispatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockDispatcher;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSecurityContext;

    /**
     * @var \Neos\Flow\Property\PropertyMapper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPropertyMapper;

    /**
     * @var \Neos\Flow\Property\PropertyMappingConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPropertyMappingConfiguration;

    /**
     */
    public function setUp()
    {
        $this->ajaxWidgetComponent = new AjaxWidgetComponent();

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);

        $this->mockComponentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->getMock();

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpResponse = new Response();
        $this->mockHttpRequest->expects($this->any())->method('getQueryParams')->willreturn([]);
        $this->mockHttpRequest->expects($this->any())->method('getUploadedFiles')->willreturn([]);
        $this->mockComponentContext->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
        $this->mockComponentContext->expects($this->any())->method('getHttpResponse')->willReturn($this->mockHttpResponse);

        $this->mockAjaxWidgetContextHolder = $this->getMockBuilder(AjaxWidgetContextHolder::class)->getMock();
        $this->inject($this->ajaxWidgetComponent, 'ajaxWidgetContextHolder', $this->mockAjaxWidgetContextHolder);

        $this->mockHashService = $this->getMockBuilder(HashService::class)->getMock();
        $this->inject($this->ajaxWidgetComponent, 'hashService', $this->mockHashService);

        $this->mockDispatcher = $this->getMockBuilder(Dispatcher::class)->getMock();
        $this->inject($this->ajaxWidgetComponent, 'dispatcher', $this->mockDispatcher);

        $this->inject($this->ajaxWidgetComponent, 'objectManager', $this->mockObjectManager);

        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->getMock();
        $this->inject($this->ajaxWidgetComponent, 'securityContext', $this->mockSecurityContext);
    }

    /**
     * @test
     */
    public function handleDoesNotCreateActionRequestIfHttpRequestContainsNoWidgetContext()
    {
        $this->mockHttpRequest->expects($this->any())->method('getParsedBody')->willReturn([]);

        $this->mockObjectManager->expects($this->never())->method('get');

        $this->ajaxWidgetComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleSetsWidgetContextAndControllerObjectNameIfWidgetIdIsPresent()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->expects($this->any())->method('getParsedBody')->willReturn([
            '__widgetId' => $mockWidgetId,
        ]);

        $mockWidgetContext = $this->getMockBuilder(WidgetContext::class)->getMock();
        $mockWidgetContext->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($mockControllerObjectName));
        $this->mockAjaxWidgetContextHolder->expects($this->atLeastOnce())->method('get')->with($mockWidgetId)->will($this->returnValue($mockWidgetContext));
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockObjectManager->expects($this->atLeastOnce())->method('get')->with(ActionRequest::class)->will($this->returnValue($mockActionRequest));
        $this->mockComponentContext->expects($this->any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', []],
            [DispatchComponent::class, 'actionRequest', $mockActionRequest]
        ]);

        $mockActionRequest->expects($this->once())->method('setArguments')->with(['__widgetContext' =>  $mockWidgetContext, '__widgetId' => 'SomeWidgetId']);
        $mockActionRequest->expects($this->once())->method('setControllerObjectName')->with($mockControllerObjectName);

        $this->ajaxWidgetComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleDispatchesActionRequestIfWidgetContextIsPresent()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->expects($this->any())->method('getParsedBody')->willReturn([
            '__widgetId' => $mockWidgetId,
        ]);

        $mockWidgetContext = $this->getMockBuilder(WidgetContext::class)->getMock();
        $mockWidgetContext->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($mockControllerObjectName));
        $this->mockAjaxWidgetContextHolder->expects($this->atLeastOnce())->method('get')->with($mockWidgetId)->will($this->returnValue($mockWidgetContext));
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockObjectManager->expects($this->atLeastOnce())->method('get')->with(ActionRequest::class)->will($this->returnValue($mockActionRequest));
        $this->mockComponentContext->expects($this->any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', []],
            [DispatchComponent::class, 'actionRequest', $mockActionRequest]
        ]);
        $this->mockDispatcher->expects($this->once())->method('dispatch');

        $this->ajaxWidgetComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleCancelsComponentChainIfWidgetContextIsPresent()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->expects($this->any())->method('getParsedBody')->willReturn([
            '__widgetId' => $mockWidgetId,
        ]);

        $mockWidgetContext = $this->getMockBuilder(WidgetContext::class)->getMock();
        $mockWidgetContext->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($mockControllerObjectName));
        $this->mockAjaxWidgetContextHolder->expects($this->atLeastOnce())->method('get')->with($mockWidgetId)->will($this->returnValue($mockWidgetContext));
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockObjectManager->expects($this->atLeastOnce())->method('get')->with(ActionRequest::class)->will($this->returnValue($mockActionRequest));
        $this->mockComponentContext->expects($this->any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', []],
            [DispatchComponent::class, 'actionRequest', $mockActionRequest]
        ]);
        $this->mockComponentContext->expects($this->any())->method('setParameter')->with(ComponentChain::class, 'cancel', true);

        $this->ajaxWidgetComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleInjectsActionRequestToSecurityContext()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->expects($this->any())->method('getParsedBody')->willReturn([
            '__widgetId' => $mockWidgetId,
        ]);

        $mockWidgetContext = $this->getMockBuilder(WidgetContext::class)->getMock();
        $mockWidgetContext->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($mockControllerObjectName));
        $this->mockAjaxWidgetContextHolder->expects($this->atLeastOnce())->method('get')->with($mockWidgetId)->will($this->returnValue($mockWidgetContext));
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockObjectManager->expects($this->atLeastOnce())->method('get')->with(ActionRequest::class)->will($this->returnValue($mockActionRequest));
        $this->mockComponentContext->expects($this->any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', []],
            [DispatchComponent::class, 'actionRequest', $mockActionRequest]
        ]);

        $this->mockSecurityContext->expects($this->once())->method('setRequest')->with($mockActionRequest);

        $this->ajaxWidgetComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function extractWidgetContextDecodesSerializedWidgetContextIfPresent()
    {
        $ajaxWidgetComponent = $this->getAccessibleMock(\Neos\FluidAdaptor\Core\Widget\AjaxWidgetComponent::class, ['dummy']);
        $this->inject($ajaxWidgetComponent, 'hashService', $this->mockHashService);

        $mockWidgetContext = 'SomeWidgetContext';
        $mockSerializedWidgetContext = base64_encode(serialize($mockWidgetContext));
        $mockSerializedWidgetContextWithHmac = $mockSerializedWidgetContext . 'HMAC';

        $this->mockHttpRequest->expects($this->any())->method('getParsedBody')->willReturn([
            '__widgetContext' => $mockSerializedWidgetContextWithHmac
        ]);


        $this->mockHashService->expects($this->atLeastOnce())->method('validateAndStripHmac')->with($mockSerializedWidgetContextWithHmac)->will($this->returnValue($mockSerializedWidgetContext));

        $actualResult = $ajaxWidgetComponent->_call('extractWidgetContext', $this->mockHttpRequest);
        $this->assertEquals($mockWidgetContext, $actualResult);
    }
}
