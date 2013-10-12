<?php
namespace TYPO3\Flow\Tests\Unit\I18n\Formatter;

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
 * Testcase for the DatetimeFormatter
 *
 */
class DatetimeFormatterTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Dummy locale used in methods where locale is needed.
	 *
	 * @var \TYPO3\Flow\I18n\Locale
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
	public function setUp() {
		$this->sampleLocale = new \TYPO3\Flow\I18n\Locale('en');
		$this->sampleLocalizedLiterals = require(__DIR__ . '/../Fixtures/MockLocalizedLiteralsArray.php');
		$this->sampleDateTime = new \DateTime("@1276192176");
		$this->sampleDateTime->setTimezone(new \DateTimeZone('Europe/London'));
	}

	/**
	 * @test
	 */
	public function formatMethodsAreChoosenCorrectly() {
		$formatter = $this->getAccessibleMock('TYPO3\Flow\I18n\Formatter\DatetimeFormatter', array('formatDate', 'formatTime', 'formatDateTime'));
		$formatter->expects($this->at(0))->method('formatDateTime')->with($this->sampleDateTime, $this->sampleLocale, \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT)->will($this->returnValue('bar1'));
		$formatter->expects($this->at(1))->method('formatDate')->with($this->sampleDateTime, $this->sampleLocale, \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT)->will($this->returnValue('bar2'));
		$formatter->expects($this->at(2))->method('formatTime')->with($this->sampleDateTime, $this->sampleLocale, \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_FULL)->will($this->returnValue('bar3'));

		$result = $formatter->format($this->sampleDateTime, $this->sampleLocale);
		$this->assertEquals('bar1', $result);

		$result = $formatter->format($this->sampleDateTime, $this->sampleLocale, array(\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE));
		$this->assertEquals('bar2', $result);

		$result = $formatter->format($this->sampleDateTime, $this->sampleLocale, array(\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME, \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_FULL));
		$this->assertEquals('bar3', $result);
	}

	/**
	 * Data provider with example parsed formats, and expected results.
	 *
	 * @return array
	 */
	public function parsedFormatsAndFormattedDatetimes() {
		return array(
			array(array('yyyy', array('.'), 'MM', array('.'), 'dd', array(' '), 'G'), '2010.06.10 AD'),
			array(array('HH', array(':'), 'mm', array(':'), 'ss', array(' '), 'zzz'), '18:49:36 BST'),
			array(array('EEE', array(','), array(' '), 'MMM', array(' '), 'd', array(','), array(' '), array('\''), 'yy'), 'Thu, Jun 10, \'10'),
			array(array('hh', array(' '), array('o'), array('\''), array('clock'), array(' '), 'a', array(','), array(' '), 'zzzz'), '06 o\'clock p.m., Europe/London'),
			array(array('QQ', 'yy', 'LLLL', 'D', 'F', 'EEEE'), '0210January1612Thursday'),
			array(array('QQQ', 'MMMMM', 'EEEEE', 'w', 'k'), 'Q26T2318'),
			array(array('GGGGG', 'K', 'S', 'W', 'qqqq', 'GGGG', 'V'), 'A6032nd quarterAnno Domini'),
		);
	}

	/**
	 * @test
	 * @dataProvider parsedFormatsAndFormattedDatetimes
	 */
	public function parsedFormatsAreUsedCorrectly(array $parsedFormat, $expectedResult) {
		$formatter = $this->getAccessibleMock('TYPO3\Flow\I18n\Formatter\DatetimeFormatter', array('dummy'));

		$result = $formatter->_call('doFormattingWithParsedFormat', $this->sampleDateTime, $parsedFormat, $this->sampleLocalizedLiterals);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with custom formats, theirs parsed versions, and expected
	 * results.
	 *
	 * @return array
	 */
	public function customFormatsAndFormattedDatetimes() {
		return array(
			array('yyyy.MM.dd G', array('yyyy', array('.'), 'MM', array('.'), 'dd', array(' '), 'G'), '2010.06.10 AD'),
		);
	}

	/**
	 * @test
	 * @dataProvider customFormatsAndFormattedDatetimes
	 */
	public function formattingUsingCustomPatternWorks($format, array $parsedFormat, $expectedResult) {
		$mockDatesReader = $this->getMock('TYPO3\Flow\I18n\Cldr\Reader\DatesReader');
		$mockDatesReader->expects($this->once())->method('parseCustomFormat')->with($format)->will($this->returnValue($parsedFormat));
		$mockDatesReader->expects($this->once())->method('getLocalizedLiteralsForLocale')->with($this->sampleLocale)->will($this->returnValue($this->sampleLocalizedLiterals));

		$formatter = new \TYPO3\Flow\I18n\Formatter\DatetimeFormatter();
		$formatter->injectDatesReader($mockDatesReader);

		$result = $formatter->formatDateTimeWithCustomPattern($this->sampleDateTime, $format, $this->sampleLocale);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with parsed formats, expected results, and format types.
	 *
	 * @return array
	 */
	public function sampleDataForSpecificFormattingMethods() {
		return array(
			array(
				array('EEEE', array(', '), 'y', array(' '), 'MMMM', array(' '), 'dd'),
				'Thursday, 2010 January 10',
				\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE
			),
			array(
				array('HH', array(':'), 'mm', array(':'), 'ss', array(' '), 'zzzz'),
				'18:49:36 Europe/London',
				\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME
			),
			array(
				array('EEEE', array(', '), 'y', array(' '), 'MMMM', array(' '), 'dd', array(' '), 'HH', array(':'), 'mm', array(':'), 'ss', array(' '), 'zzzz'),
				'Thursday, 2010 January 10 18:49:36 Europe/London',
				\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATETIME
			)
		);
	}

	/**
	 * @test
	 * @dataProvider sampleDataForSpecificFormattingMethods
	 */
	public function specificFormattingMethodsWork(array $parsedFormat, $expectedResult, $formatType) {
		$formatLength = \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_FULL;
		$mockDatesReader = $this->getMock('TYPO3\Flow\I18n\Cldr\Reader\DatesReader');
		$mockDatesReader->expects($this->once())->method('parseFormatFromCldr')->with($this->sampleLocale, $formatType, $formatLength)->will($this->returnValue($parsedFormat));
		$mockDatesReader->expects($this->once())->method('getLocalizedLiteralsForLocale')->with($this->sampleLocale)->will($this->returnValue($this->sampleLocalizedLiterals));

		$formatter = new \TYPO3\Flow\I18n\Formatter\DatetimeFormatter();
		$formatter->injectDatesReader($mockDatesReader);

		$methodName = 'format' . ucfirst($formatType);
		$result = $formatter->$methodName($this->sampleDateTime, $this->sampleLocale, $formatLength);
		$this->assertEquals($expectedResult, $result);
	}
}
