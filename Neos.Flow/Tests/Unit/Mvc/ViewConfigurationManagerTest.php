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

use Neos\Cache\Frontend\StringFrontend;
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
     * @var ActionRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockActionRequest;

    /**
     * @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockConfigurationManager;

    /**
     * @var VariableFrontend|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockCache;


    protected function setUp(): void
    {
        $this->viewConfigurationManager = new ViewConfigurationManager();

        // eel evaluator
        $eelEvaluator = $this->createEvaluator();
        $this->inject($this->viewConfigurationManager, 'eelEvaluator', $eelEvaluator);

        // a dummy configuration manager is prepared
        $this->mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->viewConfigurationManager, 'configurationManager', $this->mockConfigurationManager);

        // caching is deactivated
        $this->mockCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $this->mockCache->expects(self::any())->method('get')->will(self::returnValue(false));
        $this->inject($this->viewConfigurationManager, 'cache', $this->mockCache);

        // a dummy request is prepared
        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects(self::any())->method('getControllerPackageKey')->will(self::returnValue('Neos.Flow'));
        $this->mockActionRequest->expects(self::any())->method('getControllerSubpackageKey')->will(self::returnValue(''));
        $this->mockActionRequest->expects(self::any())->method('getControllerName')->will(self::returnValue('Standard'));
        $this->mockActionRequest->expects(self::any())->method('getControllerActionName')->will(self::returnValue('index'));
        $this->mockActionRequest->expects(self::any())->method('getFormat')->will(self::returnValue('html'));
        $this->mockActionRequest->expects(self::any())->method('getParentRequest')->will(self::returnValue(null));
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

        $this->mockConfigurationManager->expects(self::any())->method('getConfiguration')->with('Views')->will(self::returnValue($viewConfigurations));
        $calculatedConfiguration = $this->viewConfigurationManager->getViewConfiguration($this->mockActionRequest);

        self::assertEquals($calculatedConfiguration, $matchingConfiguration);
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

        $this->mockConfigurationManager->expects(self::any())->method('getConfiguration')->with('Views')->will(self::returnValue($viewConfigurations));
        $calculatedConfiguration = $this->viewConfigurationManager->getViewConfiguration($this->mockActionRequest);

        self::assertEquals($calculatedConfiguration, $matchingConfigurationTwo);
    }

    /**
     * @return CompilingEvaluator
     */
    protected function createEvaluator()
    {
        $stringFrontendMock = $this->getMockBuilder(StringFrontend::class)->setMethods([])->disableOriginalConstructor()->getMock();
        $stringFrontendMock->expects(self::any())->method('get')->willReturn(false);

        $evaluator = new CompilingEvaluator();
        $evaluator->injectExpressionCache($stringFrontendMock);
        return $evaluator;
    }
}
