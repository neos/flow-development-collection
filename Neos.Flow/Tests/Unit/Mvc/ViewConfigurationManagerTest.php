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

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Mvc\ViewConfigurationManager;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Eel\CompilingEvaluator;

/**
 * Testcase for the MVC ViewConfigurationManager
 *
 */
class ViewConfigurationManagerTest extends \Neos\Flow\Tests\UnitTestCase
{

    /**
     * @var ViewConfigurationManager
     */
    protected $viewConfigurationManager;

    /**
     * @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockActionRequest;

    /**
     * @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockConfigurationManager;

    /**
     * @var VariableFrontend|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockCache;


    public function setUp()
    {
        $this->viewConfigurationManager = new ViewConfigurationManager();

        // eel evaluator
        $eelEvaluator = new CompilingEvaluator();
        $this->inject($this->viewConfigurationManager, 'eelEvaluator', $eelEvaluator);

        // a dummy configuration manager is prepared
        $this->mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->viewConfigurationManager, 'configurationManager', $this->mockConfigurationManager);

        // caching is deactivated
        $this->mockCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $this->mockCache->expects($this->any())->method('get')->will($this->returnValue(false));
        $this->inject($this->viewConfigurationManager, 'cache', $this->mockCache);

        // a dummy request is prepared
        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('Neos.Flow'));
        $this->mockActionRequest->expects($this->any())->method('getControllerSubpackageKey')->will($this->returnValue(''));
        $this->mockActionRequest->expects($this->any())->method('getControllerName')->will($this->returnValue('Standard'));
        $this->mockActionRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue('index'));
        $this->mockActionRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->mockActionRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue(null));
    }

    /**
     * @test
     */
    public function getViewConfigurationFindsMatchingConfigurationForRequest()
    {
        $matchingConfiguration = [
            'requestFilter' => 'isPackage("Neos.Flow")',
            'options' => 'a value'
        ];

        $notMatchingConfiguration = [
            'requestFilter' => 'isPackage("Vendor.Package")',
            'options' => 'another value'
        ];

        $viewConfigurations = [$notMatchingConfiguration, $matchingConfiguration];

        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->with('Views')->will($this->returnValue($viewConfigurations));
        $calculatedConfiguration = $this->viewConfigurationManager->getViewConfiguration($this->mockActionRequest);

        $this->assertEquals($calculatedConfiguration, $matchingConfiguration);
    }

    /**
     * @test
     */
    public function getViewConfigurationUsedFilterConfigurationWithHigherWeight()
    {
        $matchingConfigurationOne = [
            'requestFilter' => 'isPackage("Neos.Flow")',
            'options' => 'a value'
        ];

        $matchingConfigurationTwo = [
            'requestFilter' => 'isPackage("Neos.Flow") && isFormat("html")',
            'options' => 'a value with higher weight'
        ];

        $notMatchingConfiguration = [
            'requestFilter' => 'isPackage("Vendor.Package")',
            'options' => 'another value'
        ];

        $viewConfigurations = [$notMatchingConfiguration, $matchingConfigurationOne, $matchingConfigurationTwo];

        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->with('Views')->will($this->returnValue($viewConfigurations));
        $calculatedConfiguration = $this->viewConfigurationManager->getViewConfiguration($this->mockActionRequest);

        $this->assertEquals($calculatedConfiguration, $matchingConfigurationTwo);
    }
}
