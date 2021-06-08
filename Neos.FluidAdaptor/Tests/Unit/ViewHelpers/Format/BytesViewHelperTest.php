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

use Neos\Flow\I18n\Configuration;
use Neos\Flow\I18n\Exception as I18nException;
use Neos\Flow\I18n\Formatter\NumberFormatter;
use Neos\Flow\I18n\Service;
use Neos\FluidAdaptor\Core\ViewHelper\Exception as ViewHelperException;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\BytesViewHelper
 */
class BytesViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\NumberViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\BytesViewHelper::class)->setMethods(['renderChildren'])->getMock();

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @return array
     */
    public function valueDataProvider()
    {
        return [

            // invalid values
            [
                'value' => 'invalid',
                'decimals' => null,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '0 B'
            ],
            [
                'value' => '',
                'decimals' => 2,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '0.00 B'
            ],
            [
                'value' => [],
                'decimals' => 2,
                'decimalSeparator' => ',',
                'thousandsSeparator' => null,
                'expected' => '0,00 B'
            ],

            // valid values
            [
                'value' => 123,
                'decimals' => null,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '123 B'
            ],
            [
                'value' => '43008',
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '42.0 KB'
            ],
            [
                'value' => 1024,
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1.0 KB'
            ],
            [
                'value' => 1023,
                'decimals' => 2,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1,023.00 B'
            ],
            [
                'value' => 1073741823,
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => '.',
                'expected' => '1.024.0 MB'
            ],
            [
                'value' => pow(1024, 5),
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1.0 PB'
            ],
            [
                'value' => pow(1024, 8),
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1.0 YB'
            ]
        ];
    }

    /**
     * @param $value
     * @param $decimals
     * @param $decimalSeparator
     * @param $thousandsSeparator
     * @param $expected
     * @test
     * @dataProvider valueDataProvider
     */
    public function renderCorrectlyConvertsAValue($value, $decimals, $decimalSeparator, $thousandsSeparator, $expected)
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $value, 'decimals' => $decimals, 'decimalSeparator' => $decimalSeparator, 'thousandsSeparator' => $thousandsSeparator]);
        $actualResult = $this->viewHelper->render();
        static::assertEquals($expected, $actualResult);
    }

    /**
     * @test
     */
    public function renderUsesChildNodesIfValueArgumentIsOmitted()
    {
        $this->viewHelper->expects(static::once())->method('renderChildren')->willReturn(12345);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);

        $actualResult = $this->viewHelper->render();

        static::assertEquals('12 KB', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesNumberFormatterOnGivenLocale()
    {
        $mockNumberFormatter = $this
            ->getMockBuilder(NumberFormatter::class)
            ->setMethods(['formatDecimalNumber'])
            ->getMock()
        ;
        $mockNumberFormatter->expects(static::once())->method('formatDecimalNumber');

        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['forceLocale' => 'de_DE']);

        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperAppendsUnitToLocalizedNumber()
    {
        $mockNumberFormatter = $this
            ->getMockBuilder(NumberFormatter::class)
            ->setMethods(['formatDecimalNumber'])
            ->getMock()
        ;
        $mockNumberFormatter
            ->expects(static::once())
            ->method('formatDecimalNumber')
            ->willReturn('123,45')
        ;

        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['forceLocale' => 'de_DE', 'value' => 123456]);

        $actualResult = $this->viewHelper->render();

        static::assertEquals('123,45 KB', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperFetchesCurrentLocaleViaI18nService()
    {
        $localizationConfiguration = new Configuration('de_DE');

        $mockLocalizationService = $this
            ->getMockBuilder(Service::class)
            ->setMethods(['getConfiguration'])
            ->getMock()
        ;
        $mockLocalizationService
            ->expects(static::once())
            ->method('getConfiguration')
            ->willReturn($localizationConfiguration)
        ;
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this
            ->getMockBuilder(NumberFormatter::class)
            ->setMethods(['formatDecimalNumber'])
            ->getMock()
        ;
        $mockNumberFormatter->expects(static::once())->method('formatDecimalNumber');
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->expects(static::once())->method('renderChildren')->willReturn(123.456);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['forceLocale' => true]);

        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperConvertsI18nExceptionsIntoViewHelperExceptions()
    {
        $localizationConfiguration = new Configuration('de_DE');

        $mockLocalizationService = $this
            ->getMockBuilder(Service::class)
            ->setMethods(['getConfiguration'])
            ->getMock()
        ;
        $mockLocalizationService
            ->expects(static::once())
            ->method('getConfiguration')
            ->willReturn($localizationConfiguration)
        ;
        $this->inject($this->viewHelper, 'localizationService', $mockLocalizationService);

        $mockNumberFormatter = $this
            ->getMockBuilder(NumberFormatter::class)
            ->setMethods(['formatDecimalNumber'])
            ->getMock()
        ;
        $mockNumberFormatter
            ->expects(static::once())
            ->method('formatDecimalNumber')
            ->will(static::throwException(new I18nException()))
        ;
        $this->inject($this->viewHelper, 'numberFormatter', $mockNumberFormatter);

        $this->viewHelper->expects(static::once())->method('renderChildren')->willReturn(123.456);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['forceLocale' => true]);

        $this->expectException(ViewHelperException::class);

        $this->viewHelper->render();
    }
}
