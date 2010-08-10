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
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->sampleLocale = new \F3\FLOW3\I18n\Locale('en');
		$this->sampleLocalizedLiterals = require(__DIR__ . '/../Fixtures/MockLocalizedLiteralsArray.php');
		$this->sampleDateTime = new \DateTime("@1276192176");
		$this->sampleDateTime->setTimezone(new \DateTimeZone('Europe/London'));
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatMethodsAreChoosenCorrectly() {
		$formatter = $this->getAccessibleMock('F3\FLOW3\I18n\Formatter\DatetimeFormatter', array('formatDate', 'formatTime', 'formatDateTime'));
		$formatter->expects($this->at(0))->method('formatDateTime')->with($this->sampleDateTime, $this->sampleLocale, 'default')->will($this->returnValue('bar1'));
		$formatter->expects($this->at(1))->method('formatDate')->with($this->sampleDateTime, $this->sampleLocale, 'default')->will($this->returnValue('bar2'));
		$formatter->expects($this->at(2))->method('formatTime')->with($this->sampleDateTime, $this->sampleLocale, 'full')->will($this->returnValue('bar3'));

		$result = $formatter->format($this->sampleDateTime, $this->sampleLocale);
		$this->assertEquals('bar1', $result);

		$result = $formatter->format($this->sampleDateTime, $this->sampleLocale, array('date'));
		$this->assertEquals('bar2', $result);

		$result = $formatter->format($this->sampleDateTime, $this->sampleLocale, array('time', 'full'));
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
	public function parsedFormatsAreUsedCorrectly(array $parsedFormat, $expectedResult) {
		$formatter = $this->getAccessibleMock('F3\FLOW3\I18n\Formatter\DatetimeFormatter', array('dummy'));

		$result = $formatter->_call('doFormattingWithParsedFormat', $this->sampleDateTime, $parsedFormat, $this->sampleLocalizedLiterals);
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
	public function formattingUsingCustomPatternWorks($format, array $parsedFormat, $expectedResult) {
		$mockDatesReader = $this->getMock('F3\FLOW3\I18n\Cldr\Reader\DatesReader');
		$mockDatesReader->expects($this->once())->method('parseCustomFormat')->with($format)->will($this->returnValue($parsedFormat));
		$mockDatesReader->expects($this->once())->method('getLocalizedLiteralsForLocale')->with($this->sampleLocale)->will($this->returnValue($this->sampleLocalizedLiterals));

		$formatter = new \F3\FLOW3\I18n\Formatter\DatetimeFormatter();
		$formatter->injectDatesReader($mockDatesReader);

		$result = $formatter->formatDateTimeWithCustomPattern($this->sampleDateTime, $format, $this->sampleLocale);
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
			array(
				array('EEEE', array(', '), 'y', array(' '), 'MMMM', array(' '), 'dd', array(' '), 'HH', array(':'), 'mm', array(':'), 'ss', array(' '), 'zzzz'),
				'Thursday, 2010 January 10 18:49:36 Europe/London',
				'dateTime',
			)
		);
	}

	/**
	 * @test
	 * @dataProvider sampleDataForSpecificFormattingMethods
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function specificFormattingMethodsWork(array $parsedFormat, $expectedResult, $formatType) {
		$formatLength = 'full';
		$mockDatesReader = $this->getMock('F3\FLOW3\I18n\Cldr\Reader\DatesReader');
		$mockDatesReader->expects($this->once())->method('parseFormatFromCldr')->with($this->sampleLocale, $formatType, $formatLength)->will($this->returnValue($parsedFormat));
		$mockDatesReader->expects($this->once())->method('getLocalizedLiteralsForLocale')->with($this->sampleLocale)->will($this->returnValue($this->sampleLocalizedLiterals));

		$formatter = new \F3\FLOW3\I18n\Formatter\DatetimeFormatter();
		$formatter->injectDatesReader($mockDatesReader);

		$methodName = 'format' . ucfirst($formatType);
		$result = $formatter->$methodName($this->sampleDateTime, $this->sampleLocale, $formatLength);
		$this->assertEquals($expectedResult, $result);
	}
}

?>