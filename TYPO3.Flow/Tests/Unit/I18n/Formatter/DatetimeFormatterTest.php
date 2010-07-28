<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n\Formatter;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the DatetimeFormatter
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DatetimeFormatterTest extends \F3\Testing\BaseTestCase {

	/**
	 * Dummy locale used in methods where locale is needed.
	 *
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $dummyLocale;

	/**
	 * @var array
	 */
	protected $mockLocalizedLiterals;

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
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->dummyLocale = new \F3\FLOW3\I18n\Locale('en');
		$this->mockLocalizedLiterals = require(__DIR__ . '/../Fixtures/MockLocalizedLiteralsArray.php');
		$this->sampleDateTime = new \DateTime("@1276192176");
		$this->sampleDateTime->setTimezone(new \DateTimeZone('Europe/London'));
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatWorks() {
		$formatter = $this->getAccessibleMock('F3\FLOW3\I18n\Formatter\DatetimeFormatter', array('formatDate', 'formatTime', 'formatDateTime'));
		$formatter->expects($this->at(0))->method('formatDateTime')->with($this->sampleDateTime, $this->dummyLocale, 'default')->will($this->returnValue('bar1'));
		$formatter->expects($this->at(1))->method('formatDate')->with($this->sampleDateTime, $this->dummyLocale, 'default')->will($this->returnValue('bar2'));
		$formatter->expects($this->at(2))->method('formatTime')->with($this->sampleDateTime, $this->dummyLocale, 'full')->will($this->returnValue('bar3'));

		$result = $formatter->format($this->sampleDateTime, $this->dummyLocale);
		$this->assertEquals('bar1', $result);

		$result = $formatter->format($this->sampleDateTime, $this->dummyLocale, array('date'));
		$this->assertEquals('bar2', $result);

		$result = $formatter->format($this->sampleDateTime, $this->dummyLocale, array('time', 'full'));
		$this->assertEquals('bar3', $result);
	}

	/**
	 * Data provider with example parsed formats, and expected results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
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
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function parsedFormatsAreUsedCorrectly($parsedFormat, $expectedResult) {
		$formatter = $this->getAccessibleMock('F3\FLOW3\I18n\Formatter\DatetimeFormatter', array('dummy'));

		$result = $formatter->_call('doFormattingWithParsedFormat', $this->sampleDateTime, $parsedFormat, $this->mockLocalizedLiterals);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with custom formats, theirs parsed versions, and expected
	 * results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function customFormatsAndFormattedDatetimes() {
		return array(
			array('yyyy.MM.dd G', array('yyyy', array('.'), 'MM', array('.'), 'dd', array(' '), 'G'), '2010.06.10 AD'),
		);
	}

	/**
	 * @test
	 * @dataProvider customFormatsAndFormattedDatetimes
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formattingUsingCustomPatternWorks($format, $parsedFormat, $expectedResult) {
		$mockDatesReader = $this->getMock('F3\FLOW3\I18n\Cldr\Reader\DatesReader');
		$mockDatesReader->expects($this->once())->method('parseCustomFormat')->with($format)->will($this->returnValue($parsedFormat));
		$mockDatesReader->expects($this->once())->method('getLocalizedLiteralsForLocale')->with($this->dummyLocale)->will($this->returnValue($this->mockLocalizedLiterals));

		$formatter = new \F3\FLOW3\I18n\Formatter\DatetimeFormatter();
		$formatter->injectDatesReader($mockDatesReader);

		$result = $formatter->formatDateTimeWithCustomPattern($this->sampleDateTime, $format, $this->dummyLocale);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with parsed date formats, time formats, dateTime formats,
	 * and expected results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function sampleDataForDateAndTimeFormatting() {
		return array(
			array(
				array('EEEE', array(', '), 'y', array(' '), 'MMMM', array(' '), 'dd'),
				array('HH', array(':'), 'mm', array(':'), 'ss', array(' '), 'zzzz'),
				'{1} {0}',
				'Thursday, 2010 January 10 18:49:36 Europe/London',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider sampleDataForDateAndTimeFormatting
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatDateTimeWorks($parsedDateFormat, $parsedTimeFormat, $dateTimeFormat, $expectedResult) {
		$formatLength = 'full';
		$mockDatesReader = $this->getMock('F3\FLOW3\I18n\Cldr\Reader\DatesReader');
		$mockDatesReader->expects($this->at(0))->method('parseFormatFromCldr')->with($this->dummyLocale, 'date', $formatLength)->will($this->returnValue($parsedDateFormat));
		$mockDatesReader->expects($this->at(1))->method('getLocalizedLiteralsForLocale')->with($this->dummyLocale)->will($this->returnValue($this->mockLocalizedLiterals));
		$mockDatesReader->expects($this->at(2))->method('parseFormatFromCldr')->with($this->dummyLocale, 'time', $formatLength)->will($this->returnValue($parsedTimeFormat));
		$mockDatesReader->expects($this->at(3))->method('getLocalizedLiteralsForLocale')->with($this->dummyLocale)->will($this->returnValue($this->mockLocalizedLiterals));
		$mockDatesReader->expects($this->at(4))->method('parseFormatFromCldr')->with($this->dummyLocale, 'dateTime', $formatLength)->will($this->returnValue($dateTimeFormat));

		$formatter = new \F3\FLOW3\I18n\Formatter\DatetimeFormatter();
		$formatter->injectDatesReader($mockDatesReader);

		$result = $formatter->formatDateTime($this->sampleDateTime, $this->dummyLocale, $formatLength);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with parsed formats, expected results, and format types.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function sampleDataForSpecificFormattingMethods() {
		return array(
			array(
				array('EEEE', array(', '), 'y', array(' '), 'MMMM', array(' '), 'dd'),
				'Thursday, 2010 January 10',
				'date'
			),
			array(
				array('HH', array(':'), 'mm', array(':'), 'ss', array(' '), 'zzzz'),
				'18:49:36 Europe/London',
				'time'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider sampleDataForSpecificFormattingMethods
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function specificFormattingMethodsWork($parsedFormat, $expectedResult, $formatType) {
		$formatLength = 'full';
		$mockDatesReader = $this->getMock('F3\FLOW3\I18n\Cldr\Reader\DatesReader');
		$mockDatesReader->expects($this->once())->method('parseFormatFromCldr')->with($this->dummyLocale, $formatType, $formatLength)->will($this->returnValue($parsedFormat));
		$mockDatesReader->expects($this->once())->method('getLocalizedLiteralsForLocale')->with($this->dummyLocale)->will($this->returnValue($this->mockLocalizedLiterals));

		$formatter = new \F3\FLOW3\I18n\Formatter\DatetimeFormatter();
		$formatter->injectDatesReader($mockDatesReader);

		$name = 'format' . ucfirst($formatType);
		$result = $formatter->$name($this->sampleDateTime, $this->dummyLocale, $formatLength);
		$this->assertEquals($expectedResult, $result);
	}
}

?>
