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
use Neos\Flow\I18n\Cldr\Reader\DatesReader;
use Neos\Flow\I18n\Formatter\DatetimeFormatter;
use Neos\Flow\I18n\Locale;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the DatetimeFormatter
 */
final class DatetimeFormatterTest extends UnitTestCase
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
    protected function setUp(): void
    {
        $this->sampleLocale = new Locale('en');
        $this->sampleLocalizedLiterals = require(__DIR__ . '/../Fixtures/MockLocalizedLiteralsArray.php');
        $this->sampleDateTime = new \DateTime('@1276192176');
        $this->sampleDateTime->setTimezone(new \DateTimeZone('Europe/London'));
    }

    #[Test]
    public function formatMethodsAreChoosenCorrectly()
    {
        $formatter = $this->getAccessibleMock(DatetimeFormatter::class, ['formatDate', 'formatTime', 'formatDateTime']);
        $formatter->expects($this->once())->method('formatDateTime')->with($this->sampleDateTime, $this->sampleLocale, DatesReader::FORMAT_LENGTH_DEFAULT)->willReturn('bar1');
        $formatter->expects($this->once())->method('formatDate')->with($this->sampleDateTime, $this->sampleLocale, DatesReader::FORMAT_LENGTH_DEFAULT)->willReturn('bar2');
        $formatter->expects($this->once())->method('formatTime')->with($this->sampleDateTime, $this->sampleLocale, DatesReader::FORMAT_LENGTH_FULL)->willReturn('bar3');

        $result = $formatter->format($this->sampleDateTime, $this->sampleLocale);
        self::assertEquals('bar1', $result);

        $result = $formatter->format($this->sampleDateTime, $this->sampleLocale, [DatesReader::FORMAT_TYPE_DATE]);
        self::assertEquals('bar2', $result);

        $result = $formatter->format($this->sampleDateTime, $this->sampleLocale, [DatesReader::FORMAT_TYPE_TIME, DatesReader::FORMAT_LENGTH_FULL]);
        self::assertEquals('bar3', $result);
    }

    /**
     * Data provider with example parsed formats, and expected results.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function parsedFormatsAndFormattedDatetimes(): \Iterator
    {
        yield [['yyyy', ['.'], 'MM', ['.'], 'dd', [' '], 'G'], '2010.06.10 AD'];
        yield [['HH', [':'], 'mm', [':'], 'ss', [' '], 'zzz'], '18:49:36 BST'];
        yield [['EEE', [','], [' '], 'MMM', [' '], 'd', [','], [' '], ['\''], 'yy'], 'Thu, Jun 10, \'10'];
        yield [['hh', [' '], ['o'], ['\''], ['clock'], [' '], 'a', [','], [' '], 'zzzz'], '06 o\'clock p.m., Europe/London'];
        yield [['QQ', 'yy', 'LLLL', 'D', 'F', 'EEEE'], '0210January1612Thursday'];
        yield [['QQQ', 'MMMMM', 'EEEEE', 'w', 'k'], 'Q26T2318'];
        yield [['GGGGG', 'K', 'S', 'W', 'qqqq', 'GGGG', 'V'], 'A6032nd quarterAnno Domini'];
        yield [['QQ', 'yy', 'LLLL', 'D', 'F', 'ccc'], '0210January1612Thu'];
    }

    #[DataProvider('parsedFormatsAndFormattedDatetimes')]
    #[Test]
    public function parsedFormatsAreUsedCorrectly(array $parsedFormat, $expectedResult)
    {
        $formatter = $this->getAccessibleMock(DatetimeFormatter::class, []);

        $result = $formatter->_call('doFormattingWithParsedFormat', $this->sampleDateTime, $parsedFormat, $this->sampleLocalizedLiterals);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Data provider with custom formats, theirs parsed versions, and expected
     * results.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function customFormatsAndFormattedDatetimes(): \Iterator
    {
        yield ['yyyy.MM.dd G', ['yyyy', ['.'], 'MM', ['.'], 'dd', [' '], 'G'], '2010.06.10 AD'];
    }

    #[DataProvider('customFormatsAndFormattedDatetimes')]
    #[Test]
    public function formattingUsingCustomPatternWorks($format, array $parsedFormat, $expectedResult)
    {
        $mockDatesReader = $this->createMock(DatesReader::class);
        $mockDatesReader->expects($this->once())->method('parseCustomFormat')->with($format)->willReturn(($parsedFormat));
        $mockDatesReader->expects($this->once())->method('getLocalizedLiteralsForLocale')->with($this->sampleLocale)->willReturn(($this->sampleLocalizedLiterals));

        $formatter = new DatetimeFormatter();
        $formatter->injectDatesReader($mockDatesReader);

        $result = $formatter->formatDateTimeWithCustomPattern($this->sampleDateTime, $format, $this->sampleLocale);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Data provider with parsed formats, expected results, and format types.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function sampleDataForSpecificFormattingMethods(): \Iterator
    {
        yield [
            ['EEEE', [', '], 'y', [' '], 'MMMM', [' '], 'dd'],
            'Thursday, 2010 January 10',
            DatesReader::FORMAT_TYPE_DATE
        ];
        yield [
            ['HH', [':'], 'mm', [':'], 'ss', [' '], 'zzzz'],
            '18:49:36 Europe/London',
            DatesReader::FORMAT_TYPE_TIME
        ];
        yield [
            ['EEEE', [', '], 'y', [' '], 'MMMM', [' '], 'dd', [' '], 'HH', [':'], 'mm', [':'], 'ss', [' '], 'zzzz'],
            'Thursday, 2010 January 10 18:49:36 Europe/London',
            DatesReader::FORMAT_TYPE_DATETIME
        ];
    }

    #[DataProvider('sampleDataForSpecificFormattingMethods')]
    #[Test]
    public function specificFormattingMethodsWork(array $parsedFormat, $expectedResult, $formatType)
    {
        $formatLength = DatesReader::FORMAT_LENGTH_FULL;
        $mockDatesReader = $this->createMock(DatesReader::class);
        $mockDatesReader->expects($this->once())->method('parseFormatFromCldr')->with($this->sampleLocale, $formatType, $formatLength)->willReturn(($parsedFormat));
        $mockDatesReader->expects($this->once())->method('getLocalizedLiteralsForLocale')->with($this->sampleLocale)->willReturn(($this->sampleLocalizedLiterals));

        $formatter = new DatetimeFormatter();
        $formatter->injectDatesReader($mockDatesReader);

        $methodName = 'format' . ucfirst($formatType);
        $result = $formatter->$methodName($this->sampleDateTime, $this->sampleLocale, $formatLength);
        self::assertEquals($expectedResult, $result);
    }
}
