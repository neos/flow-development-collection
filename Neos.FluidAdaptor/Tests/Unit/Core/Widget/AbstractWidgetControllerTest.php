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
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\Core\Widget\WidgetContext;

/**
 * Test case for AbstractWidgetController
 */
class AbstractWidgetControllerTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\Widget\Exception\WidgetContextNotFoundException
     */
    public function processRequestShouldThrowExceptionIfWidgetContextNotFound()
    {
        /** @var \Neos\Flow\Mvc\ActionRequest $mockActionRequest */
        $mockActionRequest = $this->createMock(\Neos\Flow\Mvc\ActionRequest::class);
        $mockActionRequest->expects($this->atLeastOnce())->method('getInternalArgument')->with('__widgetContext')->will($this->returnValue(null));
        $response = new Response();

        /** @var \Neos\FluidAdaptor\Core\Widget\AbstractWidgetController $abstractWidgetController */
        $abstractWidgetController = $this->getMockForAbstractClass(\Neos\FluidAdaptor\Core\Widget\AbstractWidgetController::class);
        $abstractWidgetController->processRequest($mockActionRequest, $response);
    }

    /**
     * @test
     */
    public function processRequestShouldSetWidgetConfiguration()
    {
        /** @var \Neos\Flow\Mvc\ActionRequest $mockActionRequest */
        $mockActionRequest = $this->createMock(\Neos\Flow\Mvc\ActionRequest::class);
        $mockResponse = $this->createMock(\Neos\Flow\Http\Response::class);

        $httpRequest = Request::create(new Uri('http://localhost'));
        $mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $expectedWidgetConfiguration = array('foo' => uniqid());

        $widgetContext = new WidgetContext();
        $widgetContext->setAjaxWidgetConfiguration($expectedWidgetConfiguration);

        $mockActionRequest->expects($this->atLeastOnce())->method('getInternalArgument')->with('__widgetContext')->will($this->returnValue($widgetContext));

        $abstractWidgetController = $this->getAccessibleMock(\Neos\FluidAdaptor\Core\Widget\AbstractWidgetController::class, array('resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'mapRequestArgumentsToControllerArguments', 'detectFormat', 'resolveView', 'callActionMethod'));
        $abstractWidgetController->_set('mvcPropertyMappingConfigurationService', $this->createMock(\Neos\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService::class));

        $abstractWidgetController->processRequest($mockActionRequest, $mockResponse);

        $actualWidgetConfiguration = $abstractWidgetController->_get('widgetConfiguration');
        $this->assertEquals($expectedWidgetConfiguration, $actualWidgetConfiguration);
    }
}
