<?php

declare(strict_types=1);

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

use Neos\Flow\I18n\Configuration;
use Neos\Flow\I18n\Formatter\NumberFormatter;
use Neos\Flow\I18n\Service;
use Neos\FluidAdaptor\Core\ViewHelper\Exception;
use Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Format\CurrencyViewHelper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\CurrencyViewHelper
 */
final class CurrencyViewHelperTest extends ViewHelperBaseTestcase
{
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(CurrencyViewHelper::class)->onlyMethods(['renderChildren'])->getMock();
    }

    #[Test]
    public function viewHelperRoundsFloatCorrectly()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((123.456));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('123,46', $actualResult);
    }

    #[Test]
    public function viewHelperRendersCurrencySign()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((123));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => 'foo']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('123,00 foo', $actualResult);
    }

    #[Test]
    public function viewHelperRespectsDecimalSeparator()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((12345));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '', 'decimalSeparator' => '|']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('12.345|00', $actualResult);
    }

    #[Test]
    public function viewHelperRespectsThousandsSeparator()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((12345));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '', 'decimalSeparator' => ',', 'thousandsSeparator' => '|']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('12|345,00', $actualResult);
    }

    #[Test]
    public function viewHelperRendersNullValues()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((null));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('0,00', $actualResult);
    }

    #[Test]
    public function viewHelperRendersNegativeAmounts()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((-123.456));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('-123,46', $actualResult);
    }

    #[Test]
    public function viewHelperUsesNumberFormatterOnGivenLocale()
    {
        $mockNumberFormatter = $this->getMockBuilder(NumberFormatter::class)->onlyMethods(['formatCurrencyNumber'])->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatCurrencyNumber');
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => 'EUR', 'decimalSeparator' => '#', 'thousandsSeparator' => '*', 'forceLocale' => 'de_DE']);
        $this->viewHelper->render();
    }

    #[Test]
    public function viewHelperFetchesCurrentLocaleViaI18nService()
    {
        $localizationConfiguration = new Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(Service::class)->onlyMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->willReturn(($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this->getMockBuilder(NumberFormatter::class)->onlyMethods(['formatCurrencyNumber'])->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatCurrencyNumber');
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((123.456));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => 'EUR', 'forceLocale' => true]);
        $this->viewHelper->render();
    }

    #[Test]
    public function viewHelperThrowsExceptionIfLocaleIsUsedWithoutExplicitCurrencySign()
    {
        $this->expectException(InvalidVariableException::class);
        $localizationConfiguration = new Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(Service::class)->onlyMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->willReturn(($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((123.456));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['forceLocale' => true]);
        $this->viewHelper->render();
    }

    #[Test]
    public function viewHelperConvertsI18nExceptionsIntoViewHelperExceptions()
    {
        $this->expectException(Exception::class);
        $localizationConfiguration = new Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(Service::class)->onlyMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->willReturn(($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this->getMockBuilder(NumberFormatter::class)->onlyMethods(['formatCurrencyNumber'])->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatCurrencyNumber')->willThrowException(new \Neos\Flow\I18n\Exception());
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((123.456));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '$', 'forceLocale' => true]);
        $this->viewHelper->render();
    }

    #[Test]
    public function viewHelperRespectsPrependCurrencyValue()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((12345));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '€', 'decimalSeparator' => ',', 'thousandsSeparator' => '.', 'prependCurrency' => true]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('€ 12.345,00', $actualResult);
    }

    #[Test]
    public function viewHelperRespectsSeperateCurrencyValue()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((12345));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '€', 'decimalSeparator' => ',', 'thousandsSeparator' => '.', 'prependCurrency' => false, 'separateCurrency' => false]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('12.345,00€', $actualResult);
    }

    #[Test]
    public function viewHelperRespectsCustomDecimalPlaces()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((12345));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '€', 'decimalSeparator' => ',', 'thousandsSeparator' => '.', 'prependCurrency' => false, 'separateCurrency' => true, 'decimals' => 4]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('12.345,0000 €', $actualResult);
    }

    #[Test]
    public function doNotAppendEmptySpaceIfNoCurrencySignIsSet()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((12345));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['currencySign' => '', 'decimalSeparator' => ',', 'thousandsSeparator' => '.', 'prependCurrency' => false, 'separateCurrency' => true, 'decimals' => 2]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('12.345,00', $actualResult);
    }
}
