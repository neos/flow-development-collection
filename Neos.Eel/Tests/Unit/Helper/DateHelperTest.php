<?php

declare(strict_types=1);

namespace Neos\Eel\Tests\Unit;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Eel\Helper\DateHelper;
use Neos\Flow\I18n\Configuration;
use Neos\Flow\I18n\Formatter\DatetimeFormatter;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Service;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for DateHelper
 */
final class DateHelperTest extends UnitTestCase
{
    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function parseExamples(): \Iterator
    {
        $date = \DateTime::createFromFormat('Y-m-d', '2013-07-03');
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        yield 'basic date' => ['2013-07-03', 'Y-m-d', $date];
        yield 'date with time' => ['2013-07-03 12:34:56', 'Y-m-d H:i:s', $dateTime];
    }

    #[DataProvider('parseExamples')]
    #[Test]
    public function parseWorks($string, $format, $expected)
    {
        $helper = new DateHelper();
        $result = $helper->parse($string, $format);
        self::assertInstanceOf(\DateTime::class, $result);
        self::assertEqualsWithDelta((float)$expected->format('U'), (float)$result->format('U'), 60, 'Timestamps should match');
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function formatExamples(): \Iterator
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        yield 'DateTime object' => [$dateTime, 'Y-m-d H:i:s', '2013-07-03 12:34:56'];
        yield 'timestamp as integer' => [1372856513, 'Y-m-d', '2013-07-03'];
        yield 'now' => ['now', 'Y-m-d', date('Y-m-d')];
        yield 'interval' => [new \DateInterval('P1D'), '%d days', '1 days'];
    }

    #[DataProvider('formatExamples')]
    #[Test]
    public function formatWorks($dateOrString, $format, $expected)
    {
        $helper = new DateHelper();
        $result = $helper->format($dateOrString, $format);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function formatCldrThrowsOnEmptyArguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $helper = new DateHelper();
        $helper->formatCldr(null, null);
    }

    #[Test]
    public function formatCldrWorksWithEmptyLocale()
    {
        $locale = new Locale('en');
        $expected = 'whatever-value';

        $configurationMock = $this->createMock(Configuration::class);
        $configurationMock->expects($this->atLeastOnce())->method('getCurrentLocale')->willReturn($locale);

        $localizationServiceMock = $this->createMock(Service::class);
        $localizationServiceMock->expects($this->atLeastOnce())->method('getConfiguration')->willReturn($configurationMock);

        $formatMock = $this->createMock(DatetimeFormatter::class);
        $formatMock->expects($this->atLeastOnce())->method('formatDateTimeWithCustomPattern')->willReturn($expected);

        $helper = new DateHelper();
        $this->inject($helper, 'datetimeFormatter', $formatMock);
        $this->inject($helper, 'localizationService', $localizationServiceMock);

        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        $format = 'whatever-format';
        $helper->formatCldr($date, $format);
    }

    #[Test]
    public function formatCldrCallsFormatService()
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        $format = 'whatever-format';
        $locale = 'en';
        $expected = '2013-07-03 12:34:56';

        $formatMock = $this->createMock(DatetimeFormatter::class);
        $formatMock->expects($this->atLeastOnce())->method('formatDateTimeWithCustomPattern');

        $helper = new DateHelper();
        $this->inject($helper, 'datetimeFormatter', $formatMock);

        $helper->formatCldr($date, $format, $locale);
    }

    #[Test]
    public function formatCldrDate()
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        $formatLength = 'long';
        $localeString = 'en';

        $locale = new Locale('en');
        $expectedString = '2013-07-03 12:34:56';

        $formatMock = $this->createMock(DatetimeFormatter::class);
        $formatMock->expects(self::atLeastOnce())->method('formatDate')->with($date, $locale, $formatLength)->willReturn($expectedString);

        $helper = new DateHelper();
        $this->inject($helper, 'datetimeFormatter', $formatMock);

        $result = $helper->formatCldrDate($date, $formatLength, $localeString);
        $this->assertSame($expectedString, $result);
    }

    #[Test]
    public function formatCldrTime()
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        $formatLength = 'long';
        $localeString = 'en';

        $locale = new Locale('en');
        $expectedString = '2013-07-03 12:34:56';

        $formatMock = $this->createMock(DatetimeFormatter::class);
        $formatMock->expects(self::atLeastOnce())->method('formatTime')->with($date, $locale, $formatLength)->willReturn($expectedString);

        $helper = new DateHelper();
        $this->inject($helper, 'datetimeFormatter', $formatMock);

        $result = $helper->formatCldrTime($date, $formatLength, $localeString);
        $this->assertSame($expectedString, $result);
    }

    #[Test]
    public function formatCldrDateTime()
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        $formatLength = 'long';
        $localeString = 'en';

        $locale = new Locale('en');
        $expectedString = '2013-07-03 12:34:56';

        $formatMock = $this->createMock(DatetimeFormatter::class);
        $formatMock->expects(self::atLeastOnce())->method('formatDateTime')->with($date, $locale, $formatLength)->willReturn($expectedString);

        $helper = new DateHelper();
        $this->inject($helper, 'datetimeFormatter', $formatMock);

        $result = $helper->formatCldrDateTime($date, $formatLength, $localeString);
        $this->assertSame($expectedString, $result);
    }

    #[Test]
    public function nowWorks()
    {
        $helper = new DateHelper();
        $result = $helper->now();
        self::assertInstanceOf(\DateTime::class, $result);
        self::assertEqualsWithDelta(time(), (int)$result->format('U'), 1, 'Now should be now');
    }

    #[Test]
    public function createWorks()
    {
        $helper = new DateHelper();
        $result = $helper->create('yesterday noon');
        $expected = new \DateTime('yesterday noon');
        self::assertInstanceOf(\DateTime::class, $result);
        self::assertEqualsWithDelta($expected->getTimestamp(), $result->getTimestamp(), 1, 'Created DateTime object should match expected');
    }

    #[Test]
    public function todayWorks()
    {
        $helper = new DateHelper();
        $result = $helper->today();
        self::assertInstanceOf(\DateTime::class, $result);
        $today = new \DateTime('today');
        self::assertEqualsWithDelta($today->getTimestamp(), $result->getTimestamp(), 1, 'Today should be today');
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function calculationExamples(): \Iterator
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        yield 'add DateTime with DateInterval' => ['add', $dateTime, new \DateInterval('P1D'), '2013-07-04 12:34:56'];
        yield 'add DateTime with string' => ['add', $dateTime, 'P1D', '2013-07-04 12:34:56'];
        yield 'subtract DateTime with DateInterval' => ['subtract', $dateTime, new \DateInterval('P1D'), '2013-07-02 12:34:56'];
        yield 'subtract DateTime with string' => ['subtract', $dateTime, 'P1D', '2013-07-02 12:34:56'];
    }

    #[DataProvider('calculationExamples')]
    #[Test]
    public function calculationWorks($method, $dateTime, $interval, $expected)
    {
        $timestamp = $dateTime->getTimestamp();

        $helper = new DateHelper();
        $result = $helper->$method($dateTime, $interval);

        self::assertEquals($timestamp, $dateTime->getTimeStamp(), 'DateTime should not be modified');
        self::assertEquals($expected, $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function diffWorks()
    {
        $earlierTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        $futureTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-10 12:33:56');

        $helper = new DateHelper();
        $result = $helper->diff($earlierTime, $futureTime);
        self::assertEquals(6, $result->d);
        self::assertEquals(23, $result->h);
        self::assertEquals(59, $result->i);
    }

    #[Test]
    public function dateAccessorsWork()
    {
        $helper = new DateHelper();
        $date = new \DateTime('2013-10-16 14:59:27');

        self::assertSame(2013, $helper->year($date));
        self::assertSame(10, $helper->month($date));
        self::assertSame(16, $helper->dayOfMonth($date));

        self::assertSame(14, $helper->hour($date));
        self::assertSame(59, $helper->minute($date));
        self::assertSame(27, $helper->second($date));
    }
}
