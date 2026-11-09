<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\I18n\Parser;

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
use Neos\Flow\I18n\Cldr\Reader\NumbersReader;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Parser\NumberParser;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the NumberParser
 */
final class NumberParserTest extends UnitTestCase
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

        'rounding' => 0.0,
    ];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->sampleLocale = new Locale('en_GB');
    }

    /**
     * Sample data for all test methods, with format type, string number to parse,
     * expected parsed number, string format, and parsed format.
     *
     * Note that this data provider has everything needed by any test method, so
     * not every element is used by every method.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function sampleNumbersEasyToParse(): \Iterator
    {
        yield [NumbersReader::FORMAT_TYPE_DECIMAL, '01234,5670', 1234.567, '0000.0000#', array_merge(self::$templateFormat, ['minDecimalDigits' => 4, 'maxDecimalDigits' => 5, 'minIntegerDigits' => 5])];
        yield [NumbersReader::FORMAT_TYPE_DECIMAL, '0,1', 0.1, '0.0###', array_merge(self::$templateFormat, ['minDecimalDigits' => 1, 'maxDecimalDigits' => 4])];
        yield [NumbersReader::FORMAT_TYPE_DECIMAL, '1 000,25', 1000.25, '#,##0.05', array_merge(self::$templateFormat, ['maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.05])];
        yield [NumbersReader::FORMAT_TYPE_DECIMAL, '9 999,9', 9999.9, '#,##0.0', array_merge(self::$templateFormat, ['maxDecimalDigits' => 3, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3])];
        yield [NumbersReader::FORMAT_TYPE_DECIMAL, '(1 100,0)', -1100.0, '#,##0.0;(#)', array_merge(self::$templateFormat, ['minDecimalDigits' => 1, 'maxDecimalDigits' => 1, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'negativePrefix' => '(', 'negativeSuffix' => ')'])];
        yield [NumbersReader::FORMAT_TYPE_DECIMAL, '-1,0', -1.0, '0.0;-#', array_merge(self::$templateFormat, ['minDecimalDigits' => 1, 'maxDecimalDigits' => 1, 'negativePrefix' => '-'])];
        yield [NumbersReader::FORMAT_TYPE_DECIMAL, 'd1,0b', 1.0, 'd0.0b', array_merge(self::$templateFormat, ['minDecimalDigits' => 1, 'maxDecimalDigits' => 1, 'positivePrefix' => 'd', 'positiveSuffix' => 'b'])];
        yield [NumbersReader::FORMAT_TYPE_PERCENT, '85%', 0.85, '#0%', array_merge(self::$templateFormat, ['multiplier' => 100, 'positiveSuffix' => '%', 'negativeSuffix' => '%'])];
    }

    /**
     * Sample data with structure like in sampleNumbersEasyToParse(), but with
     * number harder to parse - only lenient parsing mode should be able to
     * parse them.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function sampleNumbersHardToParse(): \Iterator
    {
        yield [NumbersReader::FORMAT_TYPE_DECIMAL, 'foo01234,56780bar', 1234.5678, '0000.0000#', array_merge(self::$templateFormat, ['minDecimalDigits' => 4, 'maxDecimalDigits' => 5, 'minIntegerDigits' => 5])];
        yield [NumbersReader::FORMAT_TYPE_DECIMAL, 'foo+2 10 00,33baz', 21000.33, '#,##0.05', array_merge(self::$templateFormat, ['maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.05])];
        yield [NumbersReader::FORMAT_TYPE_DECIMAL, '1foo10-', -110, '0.0;#-', array_merge(self::$templateFormat, ['minDecimalDigits' => 1, 'maxDecimalDigits' => 1, 'negativePrefix' => '', 'negativeSuffix' => '-'])];
        yield [NumbersReader::FORMAT_TYPE_PERCENT, '%5,3%%', 0.053, '#00.00%', array_merge(self::$templateFormat, ['multiplier' => 100, 'positiveSuffix' => '%', 'negativeSuffix' => '%', 'minIntegerDigits' => 2, 'minDecimalDigits' => 2])];
    }

    #[DataProvider('sampleNumbersEasyToParse')]
    #[Test]
    public function strictParsingWorksCorrectlyForEasyNumbers($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat)
    {
        $parser = $this->getAccessibleMock(NumberParser::class, []);
        $result = $parser->_call('doParsingInStrictMode', $numberToParse, $parsedFormat, $this->sampleLocalizedSymbols);
        self::assertEquals($expectedParsedNumber, $result);
    }

    #[DataProvider('sampleNumbersHardToParse')]
    #[Test]
    public function strictParsingReturnsFalseForHardNumbers($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat)
    {
        $parser = $this->getAccessibleMock(NumberParser::class, []);
        $result = $parser->_call('doParsingInStrictMode', $numberToParse, $parsedFormat, $this->sampleLocalizedSymbols);
        self::assertEquals(false, $result);
    }

    #[DataProvider('sampleNumbersEasyToParse')]
    #[Test]
    public function lenientParsingWorksCorrectlyForEasyNumbers($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat)
    {
        $parser = $this->getAccessibleMock(NumberParser::class, []);
        $result = $parser->_call('doParsingInLenientMode', $numberToParse, $parsedFormat, $this->sampleLocalizedSymbols);
        self::assertEquals($expectedParsedNumber, $result);
    }

    #[DataProvider('sampleNumbersHardToParse')]
    #[Test]
    public function lenientParsingWorksCorrectlyForHardNumbers($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat)
    {
        $parser = $this->getAccessibleMock(NumberParser::class, []);
        $result = $parser->_call('doParsingInLenientMode', $numberToParse, $parsedFormat, $this->sampleLocalizedSymbols);
        self::assertEquals($expectedParsedNumber, $result);
    }

    #[DataProvider('sampleNumbersEasyToParse')]
    #[Test]
    public function parsingUsingCustomPatternWorks($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat)
    {
        $mockNumbersReader = $this->createMock(NumbersReader::class);
        $mockNumbersReader->expects($this->once())->method('parseCustomFormat')->with($stringFormat)->willReturn(($parsedFormat));
        $mockNumbersReader->expects($this->once())->method('getLocalizedSymbolsForLocale')->with($this->sampleLocale)->willReturn(($this->sampleLocalizedSymbols));

        $parser = new NumberParser();
        $parser->injectNumbersReader($mockNumbersReader);

        $result = $parser->parseNumberWithCustomPattern($numberToParse, $stringFormat, $this->sampleLocale, true);
        self::assertEquals($expectedParsedNumber, $result);
    }

    #[DataProvider('sampleNumbersEasyToParse')]
    #[Test]
    public function specificFormattingMethodsWork($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat)
    {
        $mockNumbersReader = $this->createMock(NumbersReader::class);
        $mockNumbersReader->expects($this->once())->method('parseFormatFromCldr')->with($this->sampleLocale, $formatType, NumbersReader::FORMAT_LENGTH_DEFAULT)->willReturn(($parsedFormat));
        $mockNumbersReader->expects($this->once())->method('getLocalizedSymbolsForLocale')->with($this->sampleLocale)->willReturn(($this->sampleLocalizedSymbols));

        $formatter = new NumberParser();
        $formatter->injectNumbersReader($mockNumbersReader);

        $methodName = 'parse' . ucfirst($formatType) . 'Number';
        $result = $formatter->$methodName($numberToParse, $this->sampleLocale);

        self::assertEquals($expectedParsedNumber, $result);
    }
}
