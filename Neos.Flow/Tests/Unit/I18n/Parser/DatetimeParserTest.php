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
 * Testcase for the DatetimeParser
 */
class DatetimeParserTest extends UnitTestCase
{
    /**
     * @var I18n\Locale
     */
    protected $sampleLocale;

    /**
     * @var array
     */
    protected $sampleLocalizedLiterals;

    /**
     * Template datetime attributes - expected results are merged with this
     * array so code is less redundant.
     *
     * @var array
     */
    protected $datetimeAttributesTemplate = [
        'year' => null,
        'month' => null,
        'day' => null,
        'hour' => null,
        'minute' => null,
        'second' => null,
        'timezone' => null,
    ];

    /**
     * @return void
     */
    public function setUp()
    {
        $this->sampleLocale = new I18n\Locale('en_GB');
        $this->sampleLocalizedLiterals = require(__DIR__ . '/../Fixtures/MockLocalizedLiteralsArray.php');
    }

    /**
     * Sample data for all test methods, with format type, string datetime to
     * parse, string format, expected parsed datetime, and parsed format.
     *
     * Note that this data provider has everything needed by any test method, so
     * not every element is used by every method.
     *
     * @return array
     */
    public function sampleDatetimesEasyToParse()
    {
        return [
            [I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE, '1988.11.19 AD', 'yyyy.MM.dd G', array_merge($this->datetimeAttributesTemplate, ['year' => 1988, 'month' => 11, 'day' => 19]), ['yyyy', ['.'], 'MM', ['.'], 'dd', [' '], 'G']],
            [I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME, '10:00:59', 'HH:mm:ss', array_merge($this->datetimeAttributesTemplate, ['hour' => 10, 'minute' => 0, 'second' => 59]), ['HH', [':'], 'mm', [':'], 'ss']],
            [I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME, '3 p.m. Europe/Berlin', 'h a zzzz', array_merge($this->datetimeAttributesTemplate, ['hour' => 15, 'timezone' => 'Europe/Berlin']), ['h', [' '], 'a', [' '],'zzzz']],
        ];
    }

    /**
     * Sample data with structure like in sampleDatetimesEasyToParse(), but with
     * examples harder to parse - only lenient parsing mode should be able to
     * parse them.
     *
     * @return array
     */
    public function sampleDatetimesHardToParse()
    {
        return [
            [I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE, 'foo 2010/07 /30th', 'y.M.d', array_merge($this->datetimeAttributesTemplate, ['year' => 2010, 'month' => 7, 'day' => 30]), ['y', ['.'], 'M', ['.'], 'd']],
            [I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE, 'Jun foo 99 Europe/Berlin', 'MMMyyz', array_merge($this->datetimeAttributesTemplate, ['year' => 99, 'month' => 6, 'timezone' => 'Europe/Berlin']), ['MMM', 'yy', 'z']],
            [I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME, '24:11 CEST', 'K:m zzzz', array_merge($this->datetimeAttributesTemplate, ['hour' => 0, 'minute' => 11, 'timezone' => 'CEST']), ['K', [':'], 'm', [' '], 'zzzz']],
        ];
    }

    /**
     * @test
     * @dataProvider sampleDatetimesEasyToParse
     */
    public function strictParsingWorksCorrectlyForEasyDatetimes($formatType, $datetimeToParse, $stringFormat, $expectedParsedDatetime, array $parsedFormat)
    {
        $parser = $this->getAccessibleMock(I18n\Parser\DatetimeParser::class, ['dummy']);
        $result = $parser->_call('doParsingInStrictMode', $datetimeToParse, $parsedFormat, $this->sampleLocalizedLiterals);
        $this->assertEquals($expectedParsedDatetime, $result);
    }

    /**
     * @test
     * @dataProvider sampleDatetimesHardToParse
     */
    public function strictParsingReturnsFalseForHardDatetimes($formatType, $datetimeToParse, $stringFormat, $expectedParsedDatetime, array $parsedFormat)
    {
        $parser = $this->getAccessibleMock(I18n\Parser\DatetimeParser::class, ['dummy']);
        $result = $parser->_call('doParsingInStrictMode', $datetimeToParse, $parsedFormat, $this->sampleLocalizedLiterals);
        $this->assertEquals(false, $result);
    }

    /**
     * @test
     * @dataProvider sampleDatetimesEasyToParse
     */
    public function lenientParsingWorksCorrectlyForEasyDatetimes($formatType, $datetimeToParse, $stringFormat, $expectedParsedDatetime, array $parsedFormat)
    {
        $parser = $this->getAccessibleMock(I18n\Parser\DatetimeParser::class, ['dummy']);
        $result = $parser->_call('doParsingInLenientMode', $datetimeToParse, $parsedFormat, $this->sampleLocalizedLiterals);
        $this->assertEquals($expectedParsedDatetime, $result);
    }

    /**
     * @test
     * @dataProvider sampleDatetimesHardToParse
     */
    public function lenientParsingWorksCorrectlyForHardDatetimes($formatType, $datetimeToParse, $stringFormat, $expectedParsedDatetime, array $parsedFormat)
    {
        $parser = $this->getAccessibleMock(I18n\Parser\DatetimeParser::class, ['dummy']);
        $result = $parser->_call('doParsingInLenientMode', $datetimeToParse, $parsedFormat, $this->sampleLocalizedLiterals);
        $this->assertEquals($expectedParsedDatetime, $result);
    }
}
