<?php
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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the NumberParser
 */
class NumberParserTest extends UnitTestCase
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
    protected $templateFormat = [
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
    public function setUp()
    {
        $this->sampleLocale = new I18n\Locale('en_GB');
    }

    /**
     * Sample data for all test methods, with format type, string number to parse,
     * expected parsed number, string format, and parsed format.
     *
     * Note that this data provider has everything needed by any test method, so
     * not every element is used by every method.
     *
     * @return array
     */
    public function sampleNumbersEasyToParse()
    {
        return [
            [I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL, '01234,5670', 1234.567, '0000.0000#', array_merge($this->templateFormat, ['minDecimalDigits' => 4, 'maxDecimalDigits' => 5, 'minIntegerDigits' => 5])],
            [I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL, '0,1', 0.1, '0.0###', array_merge($this->templateFormat, ['minDecimalDigits' => 1, 'maxDecimalDigits' => 4])],
            [I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL, '1 000,25', 1000.25, '#,##0.05', array_merge($this->templateFormat, ['maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.05])],
            [I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL, '9 999,9', 9999.9, '#,##0.0', array_merge($this->templateFormat, ['maxDecimalDigits' => 3, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3])],
            [I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL, '(1 100,0)', -1100.0, '#,##0.0;(#)', array_merge($this->templateFormat, ['minDecimalDigits' => 1, 'maxDecimalDigits' => 1, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'negativePrefix' => '(', 'negativeSuffix' => ')'])],
            [I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL, '-1,0', -1.0, '0.0;-#', array_merge($this->templateFormat, ['minDecimalDigits' => 1, 'maxDecimalDigits' => 1, 'negativePrefix' => '-'])],
            [I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL, 'd1,0b', 1.0, 'd0.0b', array_merge($this->templateFormat, ['minDecimalDigits' => 1, 'maxDecimalDigits' => 1, 'positivePrefix' => 'd', 'positiveSuffix' => 'b'])],
            [I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_PERCENT, '85%', 0.85, '#0%', array_merge($this->templateFormat, ['multiplier' => 100, 'positiveSuffix' => '%', 'negativeSuffix' => '%'])],
        ];
    }

    /**
     * Sample data with structure like in sampleNumbersEasyToParse(), but with
     * number harder to parse - only lenient parsing mode should be able to
     * parse them.
     *
     * @return array
     */
    public function sampleNumbersHardToParse()
    {
        return [
            [I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL, 'foo01234,56780bar', 1234.5678, '0000.0000#', array_merge($this->templateFormat, ['minDecimalDigits' => 4, 'maxDecimalDigits' => 5, 'minIntegerDigits' => 5])],
            [I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL, 'foo+2 10 00,33baz', 21000.33, '#,##0.05', array_merge($this->templateFormat, ['maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.05])],
            [I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL, '1foo10-', -110, '0.0;#-', array_merge($this->templateFormat, ['minDecimalDigits' => 1, 'maxDecimalDigits' => 1, 'negativePrefix' => '', 'negativeSuffix' => '-'])],
            [I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_PERCENT, '%5,3%%', 0.053, '#00.00%', array_merge($this->templateFormat, ['multiplier' => 100, 'positiveSuffix' => '%', 'negativeSuffix' => '%', 'minIntegerDigits' => 2, 'minDecimalDigits' => 2])],
        ];
    }

    /**
     * @test
     * @dataProvider sampleNumbersEasyToParse
     */
    public function strictParsingWorksCorrectlyForEasyNumbers($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat)
    {
        $parser = $this->getAccessibleMock(I18n\Parser\NumberParser::class, ['dummy']);
        $result = $parser->_call('doParsingInStrictMode', $numberToParse, $parsedFormat, $this->sampleLocalizedSymbols);
        $this->assertEquals($expectedParsedNumber, $result);
    }

    /**
     * @test
     * @dataProvider sampleNumbersHardToParse
     */
    public function strictParsingReturnsFalseForHardNumbers($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat)
    {
        $parser = $this->getAccessibleMock(I18n\Parser\NumberParser::class, ['dummy']);
        $result = $parser->_call('doParsingInStrictMode', $numberToParse, $parsedFormat, $this->sampleLocalizedSymbols);
        $this->assertEquals(false, $result);
    }

    /**
     * @test
     * @dataProvider sampleNumbersEasyToParse
     */
    public function lenientParsingWorksCorrectlyForEasyNumbers($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat)
    {
        $parser = $this->getAccessibleMock(I18n\Parser\NumberParser::class, ['dummy']);
        $result = $parser->_call('doParsingInLenientMode', $numberToParse, $parsedFormat, $this->sampleLocalizedSymbols);
        $this->assertEquals($expectedParsedNumber, $result);
    }

    /**
     * @test
     * @dataProvider sampleNumbersHardToParse
     */
    public function lenientParsingWorksCorrectlyForHardNumbers($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat)
    {
        $parser = $this->getAccessibleMock(I18n\Parser\NumberParser::class, ['dummy']);
        $result = $parser->_call('doParsingInLenientMode', $numberToParse, $parsedFormat, $this->sampleLocalizedSymbols);
        $this->assertEquals($expectedParsedNumber, $result);
    }

    /**
     * @test
     * @dataProvider sampleNumbersEasyToParse
     */
    public function parsingUsingCustomPatternWorks($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat)
    {
        $mockNumbersReader = $this->createMock(I18n\Cldr\Reader\NumbersReader::class);
        $mockNumbersReader->expects($this->once())->method('parseCustomFormat')->with($stringFormat)->will($this->returnValue($parsedFormat));
        $mockNumbersReader->expects($this->once())->method('getLocalizedSymbolsForLocale')->with($this->sampleLocale)->will($this->returnValue($this->sampleLocalizedSymbols));

        $parser = new I18n\Parser\NumberParser();
        $parser->injectNumbersReader($mockNumbersReader);

        $result = $parser->parseNumberWithCustomPattern($numberToParse, $stringFormat, $this->sampleLocale, true);
        $this->assertEquals($expectedParsedNumber, $result);
    }

    /**
     * @test
     * @dataProvider sampleNumbersEasyToParse
     */
    public function specificFormattingMethodsWork($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat)
    {
        $mockNumbersReader = $this->createMock(I18n\Cldr\Reader\NumbersReader::class);
        $mockNumbersReader->expects($this->once())->method('parseFormatFromCldr')->with($this->sampleLocale, $formatType, I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_DEFAULT)->will($this->returnValue($parsedFormat));
        $mockNumbersReader->expects($this->once())->method('getLocalizedSymbolsForLocale')->with($this->sampleLocale)->will($this->returnValue($this->sampleLocalizedSymbols));

        $formatter = new I18n\Parser\NumberParser();
        $formatter->injectNumbersReader($mockNumbersReader);

        $methodName = 'parse' . ucfirst($formatType) . 'Number';
        $result = $formatter->$methodName($numberToParse, $this->sampleLocale);

        $this->assertEquals($expectedParsedNumber, $result);
    }
}
