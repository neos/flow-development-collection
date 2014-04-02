<?php
namespace TYPO3\Eel\Tests\Unit;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\Helper\DateHelper;

/**
 * Tests for DateHelper
 */
class DateHelperTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @return array
	 */
	public function parseExamples() {
		$date = \DateTime::createFromFormat('Y-m-d', '2013-07-03');
		$dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
		return array(
			'basic date' => array('2013-07-03', 'Y-m-d', $date),
			'date with time' => array('2013-07-03 12:34:56', 'Y-m-d H:i:s', $dateTime)
		);
	}

	/**
	 * @test
	 * @dataProvider parseExamples
	 */
	public function parseWorks($string, $format, $expected) {
		$helper = new DateHelper();
		$result = $helper->parse($string, $format);
		$this->assertInstanceOf('DateTime', $result);
		$this->assertEquals((float)$expected->format('U'), (float)$result->format('U'), 'Timestamps should match', 60);
	}

	/**
	 * @return array
	 */
	public function formatExamples() {
		$dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
		return array(
			'DateTime object' => array($dateTime, 'Y-m-d H:i:s', '2013-07-03 12:34:56'),
			'timestamp as integer' => array(1372856513, 'Y-m-d', '2013-07-03'),
			'timestamp as string' => array('1372856513', 'Y-m-d', '2013-07-03'),
			'now' => array('now', 'Y-m-d', date('Y-m-d')),
			'interval' => array(new \DateInterval('P1D'), '%d days', '1 days')
		);
	}

	/**
	 * @test
	 * @dataProvider formatExamples
	 */
	public function formatWorks($dateOrString, $format, $expected) {
		$helper = new DateHelper();
		$result = $helper->format($dateOrString, $format);
		$this->assertSame($expected, $result);
	}

	/**
	 * @test
	 */
	public function nowWorks() {
		$helper = new DateHelper();
		$result = $helper->now();
		$this->assertInstanceOf('DateTime', $result);
		$this->assertEquals(time(), (integer)$result->format('U'), 'Now should be now', 1);
	}

	/**
	 * @test
	 */
	public function todayWorks() {
		$helper = new DateHelper();
		$result = $helper->today();
		$this->assertInstanceOf('DateTime', $result);
		$today = new \DateTime('today');
		$this->assertEquals($today->getTimestamp(), $result->getTimestamp(), 'Today should be today', 1);
	}

	/**
	 * @return array
	 */
	public function calculationExamples() {
		$dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2013-07-03 12:34:56');
		return array(
			'add DateTime with DateInterval' => array('add', $dateTime, new \DateInterval('P1D'), '2013-07-04 12:34:56'),
			'add DateTime with string' => array('add', $dateTime, 'P1D', '2013-07-04 12:34:56'),
			'subtract DateTime with DateInterval' => array('subtract', $dateTime, new \DateInterval('P1D'), '2013-07-02 12:34:56'),
			'subtract DateTime with string' => array('subtract', $dateTime, 'P1D', '2013-07-02 12:34:56'),
		);
	}

	/**
	 * @test
	 * @dataProvider calculationExamples
	 */
	public function calculationWorks($method, $dateTime, $interval, $expected) {
		$timestamp = $dateTime->getTimestamp();

		$helper = new DateHelper();
		$result = $helper->$method($dateTime, $interval);

		$this->assertEquals($timestamp, $dateTime->getTimeStamp(), 'DateTime should not be modified');
		$this->assertEquals($expected, $result->format('Y-m-d H:i:s'));
	}

	/**
	 * @test
	 */
	public function diffWorks() {
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
	public function dateAccessorsWork() {
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
