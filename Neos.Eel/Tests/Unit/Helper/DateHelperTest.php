<?php
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
use Neos\Flow\I18n\Locale;

/**
 * Tests for DateHelper
 */
class DateHelperTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @return array
     */
    public function parseExamples()
    {
        $date = \DateTime::createFromFormat('Y-m-d', '2013-07-03');
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        return [
            'basic date' => ['2013-07-03', 'Y-m-d', $date],
            'date with time' => ['2013-07-03 12:34:56', 'Y-m-d H:i:s', $dateTime]
        ];
    }

    /**
     * @test
     * @dataProvider parseExamples
     */
    public function parseWorks($string, $format, $expected)
    {
        $helper = new DateHelper();
        $result = $helper->parse($string, $format);
        self::assertInstanceOf(\DateTime::class, $result);
        self::assertEqualsWithDelta((float)$expected->format('U'), (float)$result->format('U'), 60, 'Timestamps should match');
    }

    /**
     * @return array
     */
    public function formatExamples()
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        return [
            'DateTime object' => [$dateTime, 'Y-m-d H:i:s', '2013-07-03 12:34:56'],
            'timestamp as integer' => [1372856513, 'Y-m-d', '2013-07-03'],
            'now' => ['now', 'Y-m-d', date('Y-m-d')],
            'interval' => [new \DateInterval('P1D'), '%d days', '1 days']
        ];
    }

    /**
     * @test
     * @dataProvider formatExamples
     */
    public function formatWorks($dateOrString, $format, $expected)
    {
        $helper = new DateHelper();
        $result = $helper->format($dateOrString, $format);
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function formatCldrThrowsOnEmptyArguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $helper = new DateHelper();
        $helper->formatCldr(null, null);
    }

    /**
     * @test
     */
    public function formatCldrWorksWithEmptyLocale()
    {
        $locale = new Locale('en');
        $expected = 'whatever-value';

        $configurationMock = $this->createMock(\Neos\Flow\I18n\Configuration::class);
        $configurationMock->expects(self::atLeastOnce())->method('getCurrentLocale')->willReturn($locale);

        $localizationServiceMock = $this->createMock(\Neos\Flow\I18n\Service::class);
        $localizationServiceMock->expects(self::atLeastOnce())->method('getConfiguration')->willReturn($configurationMock);

        $formatMock = $this->createMock(\Neos\Flow\I18n\Formatter\DatetimeFormatter::class);
        $formatMock->expects(self::atLeastOnce())->method('formatDateTimeWithCustomPattern')->willReturn($expected);

        $helper = new DateHelper();
        $this->inject($helper, 'datetimeFormatter', $formatMock);
        $this->inject($helper, 'localizationService', $localizationServiceMock);

        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        $format = 'whatever-format';
        $helper->formatCldr($date, $format);
    }

    /**
     * @test
     */
    public function formatCldrCallsFormatService()
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        $format = 'whatever-format';
        $locale = 'en';
        $expected = '2013-07-03 12:34:56';

        $formatMock = $this->createMock(\Neos\Flow\I18n\Formatter\DatetimeFormatter::class);
        $formatMock->expects(self::atLeastOnce())->method('formatDateTimeWithCustomPattern');

        $helper = new DateHelper();
        $this->inject($helper, 'datetimeFormatter', $formatMock);

        $helper->formatCldr($date, $format, $locale);
    }

    /**
     * @test
     */
    public function nowWorks()
    {
        $helper = new DateHelper();
        $result = $helper->now();
        self::assertInstanceOf(\DateTime::class, $result);
        self::assertEqualsWithDelta(time(), (integer)$result->format('U'), 1, 'Now should be now');
    }

    /**
     * @test
     */
    public function createWorks()
    {
        $helper = new DateHelper();
        $result = $helper->create('yesterday noon');
        $expected = new \DateTime('yesterday noon');
        self::assertInstanceOf(\DateTime::class, $result);
        self::assertEqualsWithDelta($expected->getTimestamp(), $result->getTimestamp(), 1, 'Created DateTime object should match expected');
    }

    /**
     * @test
     */
    public function todayWorks()
    {
        $helper = new DateHelper();
        $result = $helper->today();
        self::assertInstanceOf(\DateTime::class, $result);
        $today = new \DateTime('today');
        self::assertEqualsWithDelta($today->getTimestamp(), $result->getTimestamp(), 1, 'Today should be today');
    }

    /**
     * @return array
     */
    public function calculationExamples()
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
        return [
            'add DateTime with DateInterval' => ['add', $dateTime, new \DateInterval('P1D'), '2013-07-04 12:34:56'],
            'add DateTime with string' => ['add', $dateTime, 'P1D', '2013-07-04 12:34:56'],
            'subtract DateTime with DateInterval' => ['subtract', $dateTime, new \DateInterval('P1D'), '2013-07-02 12:34:56'],
            'subtract DateTime with string' => ['subtract', $dateTime, 'P1D', '2013-07-02 12:34:56'],
        ];
    }

    /**
     * @test
     * @dataProvider calculationExamples
     */
    public function calculationWorks($method, $dateTime, $interval, $expected)
    {
        $timestamp = $dateTime->getTimestamp();

        $helper = new DateHelper();
        $result = $helper->$method($dateTime, $interval);

        self::assertEquals($timestamp, $dateTime->getTimeStamp(), 'DateTime should not be modified');
        self::assertEquals($expected, $result->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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
