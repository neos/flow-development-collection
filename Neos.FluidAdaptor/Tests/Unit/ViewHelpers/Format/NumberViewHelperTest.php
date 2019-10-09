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

use Neos\FluidAdaptor\Core\ViewHelper\Exception;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\NumberViewHelper
 */
class NumberViewHelperTest extends ViewHelperBaseTestcase
{

    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\NumberViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\NumberViewHelper::class)->setMethods(['renderChildren'])->getMock();
    }

    /**
     * @test
     */
    public function formatNumberDefaultsToEnglishNotationWithTwoDecimals()
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue(10000.0 / 3.0));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('3,333.33', $actualResult);
    }

    /**
     * @test
     */
    public function formatNumberWithDecimalsDecimalPointAndSeparator()
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue(10000.0 / 3.0));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['decimals' => 3, 'decimalSeparator' => ',', 'thousandsSeparator' => '.']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('3.333,333', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesNumberFormatterOnGivenLocale()
    {
        $mockNumberFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\NumberFormatter::class)->setMethods(['formatDecimalNumber'])->getMock();
        $mockNumberFormatter->expects(self::once())->method('formatDecimalNumber');

        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);
        $this->viewHelper->setArguments([]);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['decimals' => 2, 'decimalSeparator' => '#', 'thousandsSeparator' => '*', 'forceLocale' => 'de_DE']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperFetchesCurrentLocaleViaI18nService()
    {
        $localizationConfiguration = new \Neos\Flow\I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(\Neos\Flow\I18n\Service::class)->setMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects(self::once())->method('getConfiguration')->will(self::returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\NumberFormatter::class)->setMethods(['formatDecimalNumber'])->getMock();
        $mockNumberFormatter->expects(self::once())->method('formatDecimalNumber');
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue(123.456));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['forceLocale' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperConvertsI18nExceptionsIntoViewHelperExceptions()
    {
        $this->expectException(Exception::class);
        $localizationConfiguration = new \Neos\Flow\I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(\Neos\Flow\I18n\Service::class)->setMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects(self::once())->method('getConfiguration')->will(self::returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\NumberFormatter::class)->setMethods(['formatDecimalNumber'])->getMock();
        $mockNumberFormatter->expects(self::once())->method('formatDecimalNumber')->will(self::throwException(new \Neos\Flow\I18n\Exception()));
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue(123.456));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['forceLocale' => true]);
        $this->viewHelper->render();
    }
}
