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
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Fluid\Core\Widget\WidgetContext;

/**
 * Test case for AbstractWidgetController
 */
class AbstractWidgetControllerTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\Widget\Exception\WidgetContextNotFoundException
     */
    public function processRequestShouldThrowExceptionIfWidgetContextNotFound()
    {
        /** @var \TYPO3\Flow\Mvc\ActionRequest $mockActionRequest */
        $mockActionRequest = $this->createMock('TYPO3\Flow\Mvc\ActionRequest');
        $mockActionRequest->expects($this->atLeastOnce())->method('getInternalArgument')->with('__widgetContext')->will($this->returnValue(null));
        $response = new Response();

        /** @var \TYPO3\Fluid\Core\Widget\AbstractWidgetController $abstractWidgetController */
        $abstractWidgetController = $this->getMockForAbstractClass('TYPO3\Fluid\Core\Widget\AbstractWidgetController');
        $abstractWidgetController->processRequest($mockActionRequest, $response);
    }

    /**
     * @test
     */
    public function processRequestShouldSetWidgetConfiguration()
    {
        /** @var \TYPO3\Flow\Mvc\ActionRequest $mockActionRequest */
        $mockActionRequest = $this->createMock('TYPO3\Flow\Mvc\ActionRequest');
        $mockResponse = $this->createMock('TYPO3\Flow\Http\Response');

        $httpRequest = Request::create(new Uri('http://localhost'));
        $mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $expectedWidgetConfiguration = array('foo' => uniqid());

        $widgetContext = new WidgetContext();
        $widgetContext->setAjaxWidgetConfiguration($expectedWidgetConfiguration);

        $mockActionRequest->expects($this->atLeastOnce())->method('getInternalArgument')->with('__widgetContext')->will($this->returnValue($widgetContext));

        $abstractWidgetController = $this->getAccessibleMock('TYPO3\Fluid\Core\Widget\AbstractWidgetController', array('resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'mapRequestArgumentsToControllerArguments', 'detectFormat', 'resolveView', 'callActionMethod'));
        $abstractWidgetController->_set('mvcPropertyMappingConfigurationService', $this->createMock('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService'));

        $abstractWidgetController->processRequest($mockActionRequest, $mockResponse);

        $actualWidgetConfiguration = $abstractWidgetController->_get('widgetConfiguration');
        $this->assertEquals($expectedWidgetConfiguration, $actualWidgetConfiguration);
    }
}
