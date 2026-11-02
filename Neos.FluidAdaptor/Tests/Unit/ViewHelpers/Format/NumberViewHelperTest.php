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
use Neos\Flow\I18n\Configuration;
use Neos\Flow\I18n\Formatter\NumberFormatter;
use Neos\Flow\I18n\Service;
use Neos\FluidAdaptor\Core\ViewHelper\Exception;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Format\NumberViewHelper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\NumberViewHelper
 */
final class NumberViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\NumberViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        $this->viewHelper = $this->getMockBuilder(NumberViewHelper::class)->onlyMethods(['renderChildren'])->getMock();
    }

    #[Test]
    public function formatNumberDefaultsToEnglishNotationWithTwoDecimals()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((10000.0 / 3.0));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('3,333.33', $actualResult);
    }

    #[Test]
    public function formatNumberWithDecimalsDecimalPointAndSeparator()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((10000.0 / 3.0));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['decimals' => 3, 'decimalSeparator' => ',', 'thousandsSeparator' => '.']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('3.333,333', $actualResult);
    }

    #[Test]
    public function viewHelperUsesNumberFormatterOnGivenLocale()
    {
        $mockNumberFormatter = $this->getMockBuilder(NumberFormatter::class)->onlyMethods(['formatDecimalNumber'])->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatDecimalNumber');

        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);
        $this->viewHelper->setArguments([]);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['decimals' => 2, 'decimalSeparator' => '#', 'thousandsSeparator' => '*', 'forceLocale' => 'de_DE']);
        $this->viewHelper->render();
    }

    #[Test]
    public function viewHelperFetchesCurrentLocaleViaI18nService()
    {
        $localizationConfiguration = new Configuration('de_DE');

        $mockLocalizationService = $this->getMockBuilder(Service::class)->onlyMethods(['getConfiguration'])->getMock();
        $mockLocalizationService->expects($this->once())->method('getConfiguration')->willReturn(($localizationConfiguration));
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this->getMockBuilder(NumberFormatter::class)->onlyMethods(['formatDecimalNumber'])->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatDecimalNumber');
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

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

        $mockNumberFormatter = $this->getMockBuilder(NumberFormatter::class)->onlyMethods(['formatDecimalNumber'])->getMock();
        $mockNumberFormatter->expects($this->once())->method('formatDecimalNumber')->willThrowException(new \Neos\Flow\I18n\Exception());
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn((123.456));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['forceLocale' => true]);
        $this->viewHelper->render();
    }
}
