<?php
namespace TYPO3\Fluid\Tests\Unit\Core\Widget;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Http;
use TYPO3\Flow\Mvc\Dispatcher;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Cryptography\HashService;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Fluid\Core\Widget\AjaxWidgetComponent;
use TYPO3\Fluid\Core\Widget\AjaxWidgetContextHolder;

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
     * @var Http\Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var Http\Response|\PHPUnit_Framework_MockObject_MockObject
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
     * @var \TYPO3\Flow\Property\PropertyMapper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPropertyMapper;

    /**
     * @var \TYPO3\Flow\Property\PropertyMappingConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPropertyMappingConfiguration;

    /**
     */
    public function setUp()
    {
        $this->ajaxWidgetComponent = new AjaxWidgetComponent();

        $this->mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $this->inject($this->ajaxWidgetComponent, 'objectManager', $this->mockObjectManager);

        $this->mockComponentContext = $this->getMockBuilder(\TYPO3\Flow\Http\Component\ComponentContext::class)->disableOriginalConstructor()->getMock();

        $this->mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
        $this->mockComponentContext->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));

        $this->mockHttpResponse = $this->getMockBuilder(\TYPO3\Flow\Http\Response::class)->disableOriginalConstructor()->getMock();
        $this->mockComponentContext->expects($this->any())->method('getHttpResponse')->will($this->returnValue($this->mockHttpResponse));

        $this->mockAjaxWidgetContextHolder = $this->getMockBuilder(\TYPO3\Fluid\Core\Widget\AjaxWidgetContextHolder::class)->getMock();
        $this->inject($this->ajaxWidgetComponent, 'ajaxWidgetContextHolder', $this->mockAjaxWidgetContextHolder);

        $this->mockHashService = $this->getMockBuilder(\TYPO3\Flow\Security\Cryptography\HashService::class)->getMock();
        $this->inject($this->ajaxWidgetComponent, 'hashService', $this->mockHashService);

        $this->mockDispatcher = $this->getMockBuilder(\TYPO3\Flow\Mvc\Dispatcher::class)->getMock();
        $this->inject($this->ajaxWidgetComponent, 'dispatcher', $this->mockDispatcher);

        $this->mockSecurityContext = $this->getMockBuilder(\TYPO3\Flow\Security\Context::class)->getMock();
        $this->inject($this->ajaxWidgetComponent, 'securityContext', $this->mockSecurityContext);

        $this->mockPropertyMapper = $this->getMockBuilder(\TYPO3\Flow\Property\PropertyMapper::class)->disableOriginalConstructor()->getMock();
        $this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue(array()));
        $this->inject($this->ajaxWidgetComponent, 'propertyMapper', $this->mockPropertyMapper);
    }

    /**
     * @test
     */
    public function handleDoesNotCreateActionRequestIfHttpRequestContainsNoWidgetContext()
    {
        $this->mockHttpRequest->expects($this->at(0))->method('hasArgument')->with('__widgetId')->will($this->returnValue(false));
        $this->mockHttpRequest->expects($this->at(1))->method('hasArgument')->with('__widgetContext')->will($this->returnValue(false));

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
        $this->mockHttpRequest->expects($this->at(0))->method('hasArgument')->with('__widgetId')->will($this->returnValue(true));
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getArgument')->with('__widgetId')->will($this->returnValue($mockWidgetId));
        $mockWidgetContext = $this->getMockBuilder(\TYPO3\Fluid\Core\Widget\WidgetContext::class)->getMock();
        $mockWidgetContext->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($mockControllerObjectName));
        $this->mockAjaxWidgetContextHolder->expects($this->atLeastOnce())->method('get')->with($mockWidgetId)->will($this->returnValue($mockWidgetContext));
        $mockActionRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockObjectManager->expects($this->atLeastOnce())->method('get')->with(\TYPO3\Flow\Mvc\ActionRequest::class)->will($this->returnValue($mockActionRequest));

        $mockActionRequest->expects($this->once())->method('setArgument')->with('__widgetContext', $mockWidgetContext);
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
        $this->mockHttpRequest->expects($this->at(0))->method('hasArgument')->with('__widgetId')->will($this->returnValue(true));
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getArgument')->with('__widgetId')->will($this->returnValue($mockWidgetId));
        $mockWidgetContext = $this->getMockBuilder(\TYPO3\Fluid\Core\Widget\WidgetContext::class)->getMock();
        $mockWidgetContext->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($mockControllerObjectName));
        $this->mockAjaxWidgetContextHolder->expects($this->atLeastOnce())->method('get')->with($mockWidgetId)->will($this->returnValue($mockWidgetContext));
        $mockActionRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockObjectManager->expects($this->atLeastOnce())->method('get')->with(\TYPO3\Flow\Mvc\ActionRequest::class)->will($this->returnValue($mockActionRequest));

        $this->mockDispatcher->expects($this->once())->method('dispatch')->with($mockActionRequest, $this->mockHttpResponse);

        $this->ajaxWidgetComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleCancelsComponentChainIfWidgetContextIsPresent()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->expects($this->at(0))->method('hasArgument')->with('__widgetId')->will($this->returnValue(true));
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getArgument')->with('__widgetId')->will($this->returnValue($mockWidgetId));
        $mockWidgetContext = $this->getMockBuilder(\TYPO3\Fluid\Core\Widget\WidgetContext::class)->getMock();
        $mockWidgetContext->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($mockControllerObjectName));
        $this->mockAjaxWidgetContextHolder->expects($this->atLeastOnce())->method('get')->with($mockWidgetId)->will($this->returnValue($mockWidgetContext));
        $mockActionRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockObjectManager->expects($this->atLeastOnce())->method('get')->with(\TYPO3\Flow\Mvc\ActionRequest::class)->will($this->returnValue($mockActionRequest));

        $this->mockComponentContext->expects($this->once())->method('setParameter')->with(\TYPO3\Flow\Http\Component\ComponentChain::class, 'cancel', true);

        $this->ajaxWidgetComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleInjectsActionRequestToSecurityContext()
    {
        $mockWidgetId = 'SomeWidgetId';
        $mockControllerObjectName = 'SomeControllerObjectName';
        $this->mockHttpRequest->expects($this->at(0))->method('hasArgument')->with('__widgetId')->will($this->returnValue(true));
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getArgument')->with('__widgetId')->will($this->returnValue($mockWidgetId));
        $mockWidgetContext = $this->getMockBuilder(\TYPO3\Fluid\Core\Widget\WidgetContext::class)->getMock();
        $mockWidgetContext->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($mockControllerObjectName));
        $this->mockAjaxWidgetContextHolder->expects($this->atLeastOnce())->method('get')->with($mockWidgetId)->will($this->returnValue($mockWidgetContext));
        $mockActionRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockObjectManager->expects($this->atLeastOnce())->method('get')->with(\TYPO3\Flow\Mvc\ActionRequest::class)->will($this->returnValue($mockActionRequest));


        $this->mockSecurityContext->expects($this->once())->method('setRequest')->with($mockActionRequest);

        $this->ajaxWidgetComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function extractWidgetContextDecodesSerializedWidgetContextIfPresent()
    {
        $ajaxWidgetComponent = $this->getAccessibleMock(\TYPO3\Fluid\Core\Widget\AjaxWidgetComponent::class, array('dummy'));
        $this->inject($ajaxWidgetComponent, 'hashService', $this->mockHashService);

        $mockWidgetContext = 'SomeWidgetContext';
        $mockSerializedWidgetContext = base64_encode(serialize($mockWidgetContext));
        $mockSerializedWidgetContextWithHmac = $mockSerializedWidgetContext . 'HMAC';
        $this->mockHttpRequest->expects($this->at(0))->method('hasArgument')->with('__widgetId')->will($this->returnValue(false));
        $this->mockHttpRequest->expects($this->at(1))->method('hasArgument')->with('__widgetContext')->will($this->returnValue(true));
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getArgument')->with('__widgetContext')->will($this->returnValue($mockSerializedWidgetContextWithHmac));
        $this->mockHashService->expects($this->atLeastOnce())->method('validateAndStripHmac')->with($mockSerializedWidgetContextWithHmac)->will($this->returnValue($mockSerializedWidgetContext));

        $actualResult = $ajaxWidgetComponent->_call('extractWidgetContext', $this->mockHttpRequest);
        $this->assertEquals($mockWidgetContext, $actualResult);
    }
}
