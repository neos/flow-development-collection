<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Format;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\NumberViewHelper
 */
class NumberViewHelperTest extends \Neos\Flow\Tests\UnitTestCase
{

    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\NumberViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\NumberViewHelper::class)->setMethods(array('renderChildren'))->getMock();
    }

    /**
     * @test
     */
    public function formatNumberDefaultsToEnglishNotationWithTwoDecimals()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(10000.0 / 3.0));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('3,333.33', $actualResult);
    }

    /**
     * @test
     */
    public function formatNumberWithDecimalsDecimalPointAndSeparator()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(10000.0 / 3.0));
        $actualResult = $this->viewHelper->render(3, ',', '.');
        $this->assertEquals('3.333,333', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesNumberFormatterOnGivenLocale()
    {
        $mockNumberFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\NumberFormatter::class)->setMethods(array('formatDecimalNumber'))->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatDecimalNumber');

        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);
        $this->viewHelper->setArguments(array('forceLocale' => 'de_DE'));
        $this->viewHelper->render(2, '#', '*');
    }

    /**
     * @test
     */
    public function viewHelperFetchesCurrentLocaleViaI18nService()
    {
        $localizationConfiguration = new \Neos\Flow\I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(\Neos\Flow\I18n\Service::class)->setMethods(array('getConfiguration'))->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\NumberFormatter::class)->setMethods(array('formatDecimalNumber'))->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatDecimalNumber');
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));
        $this->viewHelper->setArguments(array('forceLocale' => true));
        $this->viewHelper->render();
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function viewHelperConvertsI18nExceptionsIntoViewHelperExceptions()
    {
        $localizationConfiguration = new \Neos\Flow\I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(\Neos\Flow\I18n\Service::class)->setMethods(array('getConfiguration'))->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\NumberFormatter::class)->setMethods(array('formatDecimalNumber'))->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatDecimalNumber')->will($this->throwException(new \Neos\Flow\I18n\Exception()));
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));
        $this->viewHelper->setArguments(array('forceLocale' => true));
        $this->viewHelper->render();
    }
}
