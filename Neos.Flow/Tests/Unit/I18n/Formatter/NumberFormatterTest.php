<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\I18n\Formatter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\I18n;
use Neos\Flow\I18n\Cldr\Reader\CurrencyReader;
use Neos\Flow\I18n\Cldr\Reader\NumbersReader;
use Neos\Flow\I18n\Formatter\NumberFormatter;
use Neos\Flow\I18n\Locale;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the NumberFormatter
 */
final class NumberFormatterTest extends UnitTestCase
{
    /**
     * @var I18n\Locale
     */
    protected $sampleLocale;

    /**
     * Localized symbols array used during formatting.
     *
     * @var array
     */
    protected $sampleLocalizedSymbols = [
        'decimal' => ',',
        'group' => ' ',
        'percentSign' => '%',
        'minusSign' => '-',
        'perMille' => '‰',
        'infinity' => '∞',
        'nan' => 'NaN',
    ];

    /**
     * Currency fractions array used during formatting.
     *
     * @var array
     */
    protected $sampleCurrencyFractions = [
        'JPY' => ['digits' => 0, 'rounding' => 0],
        'DEFAULT' => ['digits' => 2, 'rounding' => 0],
    ];

    /**
     * A template array of parsed format. Used as a base in order to not repeat
     * same fields everywhere.
     *
     * @var array
     */
    protected static $templateFormat = [
        'positivePrefix' => '',
        'positiveSuffix' => '',
        'negativePrefix' => '-',
        'negativeSuffix' => '',

        'multiplier' => 1,

        'minDecimalDigits' => 0,
        'maxDecimalDigits' => 0,

        'minIntegerDigits' => 1,

        'primaryGroupingSize' => 0,
        'secondaryGroupingSize' => 0,

        'rounding' => 0,
    ];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->sampleLocale = new Locale('en');
    }

    #[Test]
    public function formatMethodsAreChoosenCorrectly()
    {
        $sampleNumber = 123.456;

        $formatter = $this->getAccessibleMock(NumberFormatter::class, ['formatDecimalNumber', 'formatPercentNumber']);
        $formatter->expects($this->once())->method('formatDecimalNumber')->with($sampleNumber, $this->sampleLocale, NumbersReader::FORMAT_LENGTH_DEFAULT)->willReturn('bar1');
        $formatter->expects($this->once())->method('formatPercentNumber')->with($sampleNumber, $this->sampleLocale, NumbersReader::FORMAT_LENGTH_DEFAULT)->willReturn('bar2');

        $result = $formatter->format($sampleNumber, $this->sampleLocale);
        self::assertEquals('bar1', $result);

        $result = $formatter->format($sampleNumber, $this->sampleLocale, ['percent']);
        self::assertEquals('bar2', $result);
    }

    /**
     * Data provider with example numbers, parsed formats, and expected results.
     *
     * Note: order of elements in returned array is actually different (sample
     * number, expected result, and parsed format to use), in order to make it
     * more readable.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function sampleNumbersAndParsedFormats(): \Iterator
    {
        yield [1234.567, '01234,5670', array_merge(self::$templateFormat, ['minDecimalDigits' => 4, 'maxDecimalDigits' => 5, 'minIntegerDigits' => 5])];
        yield [0.10004, '0,1', array_merge(self::$templateFormat, ['minDecimalDigits' => 1, 'maxDecimalDigits' => 4])];
        yield [1000.23, '1 000,25', array_merge(self::$templateFormat, ['maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.05])];
    }

    #[DataProvider('sampleNumbersAndParsedFormats')]
    #[Test]
    public function parsedFormatsAreUsedCorrectly($number, $expectedResult, array $parsedFormat)
    {
        $formatter = $this->getAccessibleMock(NumberFormatter::class, []);
        $result = $formatter->_call('doFormattingWithParsedFormat', $number, $parsedFormat, $this->sampleLocalizedSymbols);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Data provider with example numbers, parsed formats, and expected results.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function customFormatsAndFormatterNumbers(): \Iterator
    {
        yield [
            1234.567, '00000.0000',
            array_merge(self::$templateFormat, ['minDecimalDigits' => 4, 'maxDecimalDigits' => 4, 'minIntegerDigits' => 5]),
            '01234,5670',
        ];
        yield [
            0.10004, '0.0###',
            array_merge(self::$templateFormat, ['minDecimalDigits' => 1, 'maxDecimalDigits' => 4]),
            '0,1',
        ];
        yield [
            -1099.99, '#,##0.0;(#)',
            array_merge(self::$templateFormat, ['minDecimalDigits' => 1, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'negativePrefix' => '(', 'negativeSuffix' => ')']),
            '(1 100,0)'
        ];
    }

    #[DataProvider('customFormatsAndFormatterNumbers')]
    #[Test]
    public function formattingUsingCustomPatternWorks($number, $format, array $parsedFormat, $expectedResult)
    {
        $mockNumbersReader = $this->createMock(NumbersReader::class);
        $mockNumbersReader->expects($this->once())->method('parseCustomFormat')->with($format)->willReturn(($parsedFormat));
        $mockNumbersReader->expects($this->once())->method('getLocalizedSymbolsForLocale')->with($this->sampleLocale)->willReturn(($this->sampleLocalizedSymbols));

        $formatter = new NumberFormatter();
        $formatter->injectNumbersReader($mockNumbersReader);

        $result = $formatter->formatNumberWithCustomPattern($number, $format, $this->sampleLocale);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Data provider with numbers, parsed formats, expected results, format types
     * (decimal, percent or currency) and currency sign if applicable.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function sampleDataForSpecificFormattingMethods(): \Iterator
    {
        yield [
            9999.9,
            array_merge(self::$templateFormat, ['maxDecimalDigits' => 3, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3]),
            '9 999,9', NumbersReader::FORMAT_TYPE_DECIMAL
        ];
        yield [
            0.85,
            array_merge(self::$templateFormat, ['multiplier' => 100, 'positiveSuffix' => '%', 'negativeSuffix' => '%']),
            '85%', NumbersReader::FORMAT_TYPE_PERCENT];
        yield [
            5.5,
            array_merge(self::$templateFormat, ['minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'positiveSuffix' => ' ¤', 'negativeSuffix' => ' ¤']),
            '5,50 zł', NumbersReader::FORMAT_TYPE_CURRENCY, 'zł'
        ];
        yield [
            100.00,
            array_merge(self::$templateFormat, ['minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'positiveSuffix' => ' ¤', 'negativeSuffix' => ' ¤']),
            '100 Yen', NumbersReader::FORMAT_TYPE_CURRENCY, 'Yen', 'JPY'
        ];
        yield [
            100.567,
            array_merge(self::$templateFormat, ['minDecimalDigits' => 3, 'maxDecimalDigits' => 3, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'positiveSuffix' => ' ¤', 'negativeSuffix' => ' ¤']),
            '100,57 €', NumbersReader::FORMAT_TYPE_CURRENCY, '€'
        ];
        yield [
            acos(8),
            array_merge(self::$templateFormat, ['minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3]),
            'NaN', NumbersReader::FORMAT_TYPE_DECIMAL
        ];
        yield [
            log(0),
            array_merge(self::$templateFormat, ['minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3]),
            '-∞', NumbersReader::FORMAT_TYPE_PERCENT
        ];
        yield [
            -log(0),
            array_merge(self::$templateFormat, ['minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3]),
            '∞', NumbersReader::FORMAT_TYPE_CURRENCY
        ];
    }

    #[DataProvider('sampleDataForSpecificFormattingMethods')]
    #[Test]
    public function specificFormattingMethodsWork($number, array $parsedFormat, $expectedResult, $formatType, $currencySign = null, $currencyCode = 'DEFAULT')
    {
        $mockNumbersReader = $this->createMock(NumbersReader::class);
        $mockNumbersReader->expects($this->once())->method('parseFormatFromCldr')->with($this->sampleLocale, $formatType, 'default')->willReturn(($parsedFormat));
        $mockNumbersReader->expects($this->once())->method('getLocalizedSymbolsForLocale')->with($this->sampleLocale)->willReturn(($this->sampleLocalizedSymbols));

        $mockCurrencyReader = $this->createMock(CurrencyReader::class);
        $mockCurrencyReader->method('getFraction')->with($currencyCode)->willReturn(($this->sampleCurrencyFractions[$currencyCode]));

        $formatter = new NumberFormatter();
        $formatter->injectNumbersReader($mockNumbersReader);
        $formatter->injectCurrencyReader($mockCurrencyReader);

        if ($formatType === 'currency') {
            $result = $formatter->formatCurrencyNumber($number, $this->sampleLocale, $currencySign, NumbersReader::FORMAT_LENGTH_DEFAULT, $currencyCode);
        } else {
            $methodName = 'format' . ucfirst($formatType) . 'Number';
            $result = $formatter->$methodName($number, $this->sampleLocale);
        }
        self::assertEquals($expectedResult, $result);
    }
}
