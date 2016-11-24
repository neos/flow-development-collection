<?php
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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the DatetimeFormatter
 */
class DatetimeFormatterTest extends UnitTestCase
{
    /**
     * Dummy locale used in methods where locale is needed.
     *
     * @var I18n\Locale
     */
    protected $sampleLocale;

    /**
     * @var array
     */
    protected $sampleLocalizedLiterals;

    /**
     * DateTime object used in tests
     *
     * Timestamp for: 2010-06-10T17:49:36+00:00
     *
     * Please note that timezone for this object is changed, so it actually
     * represents date one hour later.
     *
     * @var \DateTime
     */
    protected $sampleDateTime;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->sampleLocale = new I18n\Locale('en');
        $this->sampleLocalizedLiterals = require(__DIR__ . '/../Fixtures/MockLocalizedLiteralsArray.php');
        $this->sampleDateTime = new \DateTime('@1276192176');
        $this->sampleDateTime->setTimezone(new \DateTimeZone('Europe/London'));
    }

    /**
     * @test
     */
    public function formatMethodsAreChoosenCorrectly()
    {
        $formatter = $this->getAccessibleMock(I18n\Formatter\DatetimeFormatter::class, ['formatDate', 'formatTime', 'formatDateTime']);
        $formatter->expects($this->at(0))->method('formatDateTime')->with($this->sampleDateTime, $this->sampleLocale, I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT)->will($this->returnValue('bar1'));
        $formatter->expects($this->at(1))->method('formatDate')->with($this->sampleDateTime, $this->sampleLocale, I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT)->will($this->returnValue('bar2'));
        $formatter->expects($this->at(2))->method('formatTime')->with($this->sampleDateTime, $this->sampleLocale, I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_FULL)->will($this->returnValue('bar3'));

        $result = $formatter->format($this->sampleDateTime, $this->sampleLocale);
        $this->assertEquals('bar1', $result);

        $result = $formatter->format($this->sampleDateTime, $this->sampleLocale, [I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE]);
        $this->assertEquals('bar2', $result);

        $result = $formatter->format($this->sampleDateTime, $this->sampleLocale, [I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME, I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_FULL]);
        $this->assertEquals('bar3', $result);
    }

    /**
     * Data provider with example parsed formats, and expected results.
     *
     * @return array
     */
    public function parsedFormatsAndFormattedDatetimes()
    {
        return [
            [['yyyy', ['.'], 'MM', ['.'], 'dd', [' '], 'G'], '2010.06.10 AD'],
            [['HH', [':'], 'mm', [':'], 'ss', [' '], 'zzz'], '18:49:36 BST'],
            [['EEE', [','], [' '], 'MMM', [' '], 'd', [','], [' '], ['\''], 'yy'], 'Thu, Jun 10, \'10'],
            [['hh', [' '], ['o'], ['\''], ['clock'], [' '], 'a', [','], [' '], 'zzzz'], '06 o\'clock p.m., Europe/London'],
            [['QQ', 'yy', 'LLLL', 'D', 'F', 'EEEE'], '0210January1612Thursday'],
            [['QQQ', 'MMMMM', 'EEEEE', 'w', 'k'], 'Q26T2318'],
            [['GGGGG', 'K', 'S', 'W', 'qqqq', 'GGGG', 'V'], 'A6032nd quarterAnno Domini'],
        ];
    }

    /**
     * @test
     * @dataProvider parsedFormatsAndFormattedDatetimes
     */
    public function parsedFormatsAreUsedCorrectly(array $parsedFormat, $expectedResult)
    {
        $formatter = $this->getAccessibleMock(I18n\Formatter\DatetimeFormatter::class, ['dummy']);

        $result = $formatter->_call('doFormattingWithParsedFormat', $this->sampleDateTime, $parsedFormat, $this->sampleLocalizedLiterals);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Data provider with custom formats, theirs parsed versions, and expected
     * results.
     *
     * @return array
     */
    public function customFormatsAndFormattedDatetimes()
    {
        return [
            ['yyyy.MM.dd G', ['yyyy', ['.'], 'MM', ['.'], 'dd', [' '], 'G'], '2010.06.10 AD'],
        ];
    }

    /**
     * @test
     * @dataProvider customFormatsAndFormattedDatetimes
     */
    public function formattingUsingCustomPatternWorks($format, array $parsedFormat, $expectedResult)
    {
        $mockDatesReader = $this->createMock(I18n\Cldr\Reader\DatesReader::class);
        $mockDatesReader->expects($this->once())->method('parseCustomFormat')->with($format)->will($this->returnValue($parsedFormat));
        $mockDatesReader->expects($this->once())->method('getLocalizedLiteralsForLocale')->with($this->sampleLocale)->will($this->returnValue($this->sampleLocalizedLiterals));

        $formatter = new I18n\Formatter\DatetimeFormatter();
        $formatter->injectDatesReader($mockDatesReader);

        $result = $formatter->formatDateTimeWithCustomPattern($this->sampleDateTime, $format, $this->sampleLocale);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Data provider with parsed formats, expected results, and format types.
     *
     * @return array
     */
    public function sampleDataForSpecificFormattingMethods()
    {
        return [
            [
                ['EEEE', [', '], 'y', [' '], 'MMMM', [' '], 'dd'],
                'Thursday, 2010 January 10',
                I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE
            ],
            [
                ['HH', [':'], 'mm', [':'], 'ss', [' '], 'zzzz'],
                '18:49:36 Europe/London',
                I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME
            ],
            [
                ['EEEE', [', '], 'y', [' '], 'MMMM', [' '], 'dd', [' '], 'HH', [':'], 'mm', [':'], 'ss', [' '], 'zzzz'],
                'Thursday, 2010 January 10 18:49:36 Europe/London',
                I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATETIME
            ]
        ];
    }

    /**
     * @test
     * @dataProvider sampleDataForSpecificFormattingMethods
     */
    public function specificFormattingMethodsWork(array $parsedFormat, $expectedResult, $formatType)
    {
        $formatLength = I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_FULL;
        $mockDatesReader = $this->createMock(I18n\Cldr\Reader\DatesReader::class);
        $mockDatesReader->expects($this->once())->method('parseFormatFromCldr')->with($this->sampleLocale, $formatType, $formatLength)->will($this->returnValue($parsedFormat));
        $mockDatesReader->expects($this->once())->method('getLocalizedLiteralsForLocale')->with($this->sampleLocale)->will($this->returnValue($this->sampleLocalizedLiterals));

        $formatter = new I18n\Formatter\DatetimeFormatter();
        $formatter->injectDatesReader($mockDatesReader);

        $methodName = 'format' . ucfirst($formatType);
        $result = $formatter->$methodName($this->sampleDateTime, $this->sampleLocale, $formatLength);
        $this->assertEquals($expectedResult, $result);
    }
}
