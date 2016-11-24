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
        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertEquals((float)$expected->format('U'), (float)$result->format('U'), 'Timestamps should match', 60);
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
            'timestamp as string' => ['1372856513', 'Y-m-d', '2013-07-03'],
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
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function nowWorks()
    {
        $helper = new DateHelper();
        $result = $helper->now();
        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertEquals(time(), (integer)$result->format('U'), 'Now should be now', 1);
    }

    /**
     * @test
     */
    public function todayWorks()
    {
        $helper = new DateHelper();
        $result = $helper->today();
        $this->assertInstanceOf(\DateTime::class, $result);
        $today = new \DateTime('today');
        $this->assertEquals($today->getTimestamp(), $result->getTimestamp(), 'Today should be today', 1);
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

        $this->assertEquals($timestamp, $dateTime->getTimeStamp(), 'DateTime should not be modified');
        $this->assertEquals($expected, $result->format('Y-m-d H:i:s'));
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
        $this->assertEquals(6, $result->d);
        $this->assertEquals(23, $result->h);
        $this->assertEquals(59, $result->i);
    }

    /**
     * @test
     */
    public function dateAccessorsWork()
    {
        $helper = new DateHelper();
        $date = new \DateTime('2013-10-16 14:59:27');

        $this->assertSame(2013, $helper->year($date));
        $this->assertSame(10, $helper->month($date));
        $this->assertSame(16, $helper->dayOfMonth($date));

        $this->assertSame(14, $helper->hour($date));
        $this->assertSame(59, $helper->minute($date));
        $this->assertSame(27, $helper->second($date));
    }
}
