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


require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\CurrencyViewHelper
 */
class CurrencyViewHelperTest extends ViewHelperBaseTestcase
{
    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\CurrencyViewHelper::class)->setMethods(['renderChildren', 'registerRenderMethodArguments'])->getMock();
    }

    /**
     * @test
     */
    public function viewHelperRoundsFloatCorrectly()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('123,46', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersCurrencySign()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => 'foo']);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('123,00 foo', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsDecimalSeparator()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '', 'decimalSeparator' => '|']);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('12.345|00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsThousandsSeparator()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '', 'decimalSeparator' => ',', 'thousandsSeparator' => '|']);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('12|345,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersNullValues()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(null));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('0,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersNegativeAmounts()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(-123.456));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('-123,46', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesNumberFormatterOnGivenLocale()
    {
        $mockNumberFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\NumberFormatter::class)->setMethods(['formatCurrencyNumber'])->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatCurrencyNumber');
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => 'EUR', 'decimalSeparator' => '#', 'thousandsSeparator' => '*', 'forceLocale' => 'de_DE']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperFetchesCurrentLocaleViaI18nService()
    {
        $localizationConfiguration = new \Neos\Flow\I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(\Neos\Flow\I18n\Service::class)->setMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\NumberFormatter::class)->setMethods(['formatCurrencyNumber'])->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatCurrencyNumber');
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => 'EUR', 'forceLocale' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function viewHelperThrowsExceptionIfLocaleIsUsedWithoutExplicitCurrencySign()
    {
        $localizationConfiguration = new \Neos\Flow\I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(\Neos\Flow\I18n\Service::class)->setMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['forceLocale' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function viewHelperConvertsI18nExceptionsIntoViewHelperExceptions()
    {
        $localizationConfiguration = new \Neos\Flow\I18n\Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(\Neos\Flow\I18n\Service::class)->setMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->will($this->returnValue($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this->getMockBuilder(\Neos\Flow\I18n\Formatter\NumberFormatter::class)->setMethods(['formatCurrencyNumber'])->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatCurrencyNumber')->will($this->throwException(new \Neos\Flow\I18n\Exception()));
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '$', 'forceLocale' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperRespectsPrependCurrencyValue()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '€', 'decimalSeparator' => ',', 'thousandsSeparator' => '.', 'prependCurrency' => true]);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('€ 12.345,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsSeperateCurrencyValue()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '€', 'decimalSeparator' => ',', 'thousandsSeparator' => '.', 'prependCurrency' => false, 'separateCurrency' => false]);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('12.345,00€', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsCustomDecimalPlaces()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '€', 'decimalSeparator' => ',', 'thousandsSeparator' => '.', 'prependCurrency' => false, 'separateCurrency' => true, 'decimals' => 4]);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('12.345,0000 €', $actualResult);
    }

    /**
     * @test
     */
    public function doNotAppendEmptySpaceIfNoCurrencySignIsSet()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '', 'decimalSeparator' => ',', 'thousandsSeparator' => '.', 'prependCurrency' => false, 'separateCurrency' => true, 'decimals' => 2]);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('12.345,00', $actualResult);
    }
}
