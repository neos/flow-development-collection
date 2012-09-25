<?php
namespace TYPO3\Flow\Tests\Unit\I18n\Parser;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the DatetimeParser
 *
 */
class DatetimeParserTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\I18n\Locale
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
	protected $datetimeAttributesTemplate = array(
		'year' => NULL,
		'month' => NULL,
		'day' => NULL,
		'hour' => NULL,
		'minute' => NULL,
		'second' => NULL,
		'timezone' => NULL,
	);

	/**
	 * @return void
	 */
	public function setUp() {
		$this->sampleLocale = new \TYPO3\Flow\I18n\Locale('en_GB');
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
	public function sampleDatetimesEasyToParse() {
		return array(
			array(\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE, '1988.11.19 AD', 'yyyy.MM.dd G', array_merge($this->datetimeAttributesTemplate, array('year' => 1988, 'month' => 11, 'day' => 19)), array('yyyy', array('.'), 'MM', array('.'), 'dd', array(' '), 'G')),
			array(\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME, '10:00:59', 'HH:mm:ss', array_merge($this->datetimeAttributesTemplate, array('hour' => 10, 'minute' => 0, 'second' => 59)), array('HH', array(':'), 'mm', array(':'), 'ss')),
			array(\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME, '3 p.m. Europe/Berlin', 'h a zzzz', array_merge($this->datetimeAttributesTemplate, array('hour' => 15, 'timezone' => 'Europe/Berlin')), array('h', array(' '), 'a', array(' '),'zzzz')),
		);
	}

	/**
	 * Sample data with structure like in sampleDatetimesEasyToParse(), but with
	 * examples harder to parse - only lenient parsing mode should be able to
	 * parse them.
	 *
	 * @return array
	 */
	public function sampleDatetimesHardToParse() {
		return array(
			array(\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE, 'foo 2010/07 /30th', 'y.M.d', array_merge($this->datetimeAttributesTemplate, array('year' => 2010, 'month' => 7, 'day' => 30)), array('y', array('.'), 'M', array('.'), 'd')),
			array(\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE, 'Jun foo 99 Europe/Berlin', 'MMMyyz', array_merge($this->datetimeAttributesTemplate, array('year' => 99, 'month' => 6, 'timezone' => 'Europe/Berlin')), array('MMM', 'yy', 'z')),
			array(\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME, '24:11 CEST', 'K:m zzzz', array_merge($this->datetimeAttributesTemplate, array('hour' => 0, 'minute' => 11, 'timezone' => 'CEST')), array('K', array(':'), 'm', array(' '), 'zzzz')),
		);
	}

	/**
	 * @test
	 * @dataProvider sampleDatetimesEasyToParse
	 */
	public function strictParsingWorksCorrectlyForEasyDatetimes($formatType, $datetimeToParse, $stringFormat, $expectedParsedDatetime, array $parsedFormat) {
		$parser = $this->getAccessibleMock('TYPO3\Flow\I18n\Parser\DatetimeParser', array('dummy'));
		$result = $parser->_call('doParsingInStrictMode', $datetimeToParse, $parsedFormat, $this->sampleLocalizedLiterals);
		$this->assertEquals($expectedParsedDatetime, $result);
	}

	/**
	 * @test
	 * @dataProvider sampleDatetimesHardToParse
	 */
	public function strictParsingReturnsFalseForHardDatetimes($formatType, $datetimeToParse, $stringFormat, $expectedParsedDatetime, array $parsedFormat) {
		$parser = $this->getAccessibleMock('TYPO3\Flow\I18n\Parser\DatetimeParser', array('dummy'));
		$result = $parser->_call('doParsingInStrictMode', $datetimeToParse, $parsedFormat, $this->sampleLocalizedLiterals);
		$this->assertEquals(FALSE, $result);
	}

	/**
	 * @test
	 * @dataProvider sampleDatetimesEasyToParse
	 */
	public function lenientParsingWorksCorrectlyForEasyDatetimes($formatType, $datetimeToParse, $stringFormat, $expectedParsedDatetime, array $parsedFormat) {
		$parser = $this->getAccessibleMock('TYPO3\Flow\I18n\Parser\DatetimeParser', array('dummy'));
		$result = $parser->_call('doParsingInLenientMode', $datetimeToParse, $parsedFormat, $this->sampleLocalizedLiterals);
		$this->assertEquals($expectedParsedDatetime, $result);
	}

	/**
	 * @test
	 * @dataProvider sampleDatetimesHardToParse
	 */
	public function lenientParsingWorksCorrectlyForHardDatetimes($formatType, $datetimeToParse, $stringFormat, $expectedParsedDatetime, array $parsedFormat) {
		$parser = $this->getAccessibleMock('TYPO3\Flow\I18n\Parser\DatetimeParser', array('dummy'));
		$result = $parser->_call('doParsingInLenientMode', $datetimeToParse, $parsedFormat, $this->sampleLocalizedLiterals);
		$this->assertEquals($expectedParsedDatetime, $result);
	}
}

?>