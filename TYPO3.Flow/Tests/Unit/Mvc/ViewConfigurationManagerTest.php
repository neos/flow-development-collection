<?php
namespace TYPO3\Flow\Tests\Unit\Mvc;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Mvc\ViewConfigurationManager;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Eel\CompilingEvaluator;

/**
 * Testcase for the MVC ViewConfigurationManager
 *
 */
class ViewConfigurationManagerTest extends \TYPO3\Flow\Tests\UnitTestCase
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
        $this->mockActionRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('TYPO3.Flow'));
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
            'requestFilter' => 'isPackage("TYPO3.Flow")',
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
    public function getViewConfigurationOverridesValuesOfHigherWeightedMatchingFilters()
    {
        $matchingConfigurationOne = [
            'requestFilter' => 'isPackage("TYPO3.Flow")',
            'options' => 'a value'
        ];

        $matchingConfigurationTwo = [
            'requestFilter' => 'isPackage("TYPO3.Flow") && isFormat("html")',
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

    /**
     * @test
     */
    public function getViewConfigurationOverridesPropertiesWithHigherWeightedMatchingFilters()
    {
        $matchingConfigurationOne = [
            'requestFilter' => 'isPackage("TYPO3.Flow")',
            'options' => [
                'foo' => 'foo',
                'bar' => 'bar'
            ]
        ];

        $matchingConfigurationTwo = [
            'requestFilter' => 'isPackage("TYPO3.Flow") && isFormat("html")',
            'options' => [
                'bar' => 'overridden_by_higher_weight',
                'baz' => 'added_with_higher_weight'
            ]
        ];

        $notMatchingConfiguration = [
            'requestFilter' => 'isPackage("Vendor.Package")',
            'options' => 'another value'
        ];

        $expectedMergedConfiguration = [
            'requestFilter' => 'isPackage("TYPO3.Flow") && isFormat("html")',
            'options' => [
                'bar' => 'overridden_by_higher_weight',
                'baz' => 'added_with_higher_weight',
                'foo' => 'foo'
            ]
        ];

        $viewConfigurations = [$notMatchingConfiguration, $matchingConfigurationOne, $matchingConfigurationTwo];

        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->with('Views')->will($this->returnValue($viewConfigurations));
        $calculatedConfiguration = $this->viewConfigurationManager->getViewConfiguration($this->mockActionRequest);

        // contrary to asserEquals assetSame insits on identical key order in arrays
        $this->assertSame($calculatedConfiguration, $expectedMergedConfiguration);
    }

    /**
     * @test
     */
    public function getViewConfigurationPlacesPropertiesOfHeigherWeightedFiltersBeforeOtherProperties()
    {
        $matchingConfigurationOne = [
            'requestFilter' => 'isPackage("TYPO3.Flow")',
            'options' => [
                'templateRootPaths' => [
                    'a' => 'a/path',
                    'b' => 'b/path'
                ]
            ]
        ];

        $matchingConfigurationTwo = [
            'requestFilter' => 'isPackage("TYPO3.Flow") && isFormat("html")',
            'options' => [
                'templateRootPaths' => [
                    'a' => 'a/path/overwritten'
                ]
            ]
        ];

        $matchingConfigurationThree = [
            'requestFilter' => 'isPackage("TYPO3.Flow") && isController("Standard") && isFormat("html")',
            'options' => [
                'templateRootPaths' => [
                    'c' => 'another/path/added'
                ]
            ]
        ];

        $expectedMergedConfiguration = [
            'requestFilter' => 'isPackage("TYPO3.Flow") && isController("Standard") && isFormat("html")',
            'options' => [
                'templateRootPaths' => [
                    'c' => 'another/path/added',
                    'a' => 'a/path/overwritten',
                    'b' => 'b/path'
                ]
            ]
        ];

        $viewConfigurations = [$matchingConfigurationOne, $matchingConfigurationTwo, $matchingConfigurationThree];

        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->with('Views')->will($this->returnValue($viewConfigurations));
        $calculatedConfiguration = $this->viewConfigurationManager->getViewConfiguration($this->mockActionRequest);

        // contrary to asserEquals assetSame insits on identical key order in arrays
        $this->assertSame($calculatedConfiguration, $expectedMergedConfiguration);
    }
}
