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

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\Core\Widget\Exception\WidgetContextNotFoundException;
use Neos\FluidAdaptor\Core\Widget\WidgetContext;

/**
 * Test case for AbstractWidgetController
 */
class AbstractWidgetControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function processRequestShouldThrowExceptionIfWidgetContextNotFound()
    {
        $this->expectException(WidgetContextNotFoundException::class);
        /** @var \Neos\Flow\Mvc\ActionRequest $mockActionRequest */
        $mockActionRequest = $this->createMock(\Neos\Flow\Mvc\ActionRequest::class);
        $mockActionRequest->expects(self::atLeastOnce())->method('getInternalArgument')->with('__widgetContext')->will(self::returnValue(null));
        $response = new ActionResponse();

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
        $mockResponse = new ActionResponse();

        $httpRequest = new ServerRequest('GET', new Uri('http://localhost'));
        $mockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($httpRequest));

        $expectedWidgetConfiguration = ['foo' => uniqid()];

        $widgetContext = new WidgetContext();
        $widgetContext->setAjaxWidgetConfiguration($expectedWidgetConfiguration);

        $mockActionRequest->expects(self::atLeastOnce())->method('getInternalArgument')->with('__widgetContext')->will(self::returnValue($widgetContext));

        $abstractWidgetController = $this->getAccessibleMock(\Neos\FluidAdaptor\Core\Widget\AbstractWidgetController::class, ['resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'mapRequestArgumentsToControllerArguments', 'detectFormat', 'resolveView', 'callActionMethod']);
        $abstractWidgetController->_set('mvcPropertyMappingConfigurationService', $this->createMock(\Neos\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService::class));

        $abstractWidgetController->processRequest($mockActionRequest, $mockResponse);

        $actualWidgetConfiguration = $abstractWidgetController->_get('widgetConfiguration');
        self::assertEquals($expectedWidgetConfiguration, $actualWidgetConfiguration);
    }
}
