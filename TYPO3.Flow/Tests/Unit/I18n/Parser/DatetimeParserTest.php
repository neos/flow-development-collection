<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n\Parser;

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
 * Testcase for the DatetimeParser
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DatetimeParserTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $dummyLocale;

	/**
	 * @var array
	 */
	protected $mockLocalizedLiterals;

	/**
	 * Template datetime attributes - expected results are merged with this
	 * array so code is less redundant.
	 *
	 * @var array
	 */
	protected $datetimeAttributesTemplate = array(
		'year' => 1970,
		'month' => 1,
		'day' => 1,
		'hour' => 0,
		'minute' => 0,
		'second' => 0,
		'timezone' => 'Europe/London',
	);

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->dummyLocale = new \F3\FLOW3\I18n\Locale('en_GB');
		$this->mockLocalizedLiterals = require(__DIR__ . '/../Fixtures/MockLocalizedLiteralsArray.php');
	}

	/**
	 * Data provider with valid date/time in strings, expected results, format
	 * type, and dummy parsedFormat arrays to use.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function parseStrings() {
		return array(
			array(
				'1988.11.19 AD',
				array_merge($this->datetimeAttributesTemplate, array('year' => 1988, 'month' => 11, 'day' => 19)),
				'date',
				array('yyyy', array('.'), 'MM', array('.'), 'dd', array(' '), 'G')
			),
			array(
				'10:00:59',
				array_merge($this->datetimeAttributesTemplate, array('hour' => 10, 'minute' => 0, 'second' => 59)),
				'time',
				array('HH', array(':'), 'mm', array(':'), 'ss')
			),
			array(
				'3 p.m. Europe/Berlin',
				array_merge($this->datetimeAttributesTemplate, array('hour' => 15, 'timezone' => 'Europe/Berlin')),
				'time',
				array('h', array(' '), 'a', array(' '),'zzzz')
			),
		);
	}

	/**
	 * @test
	 * @dataProvider parseStrings
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function strictParsingWorks($datetimeToParse, $expectedResult, $formatType, $parsedFormat) {
		$mockReader = $this->getMock('F3\FLOW3\I18n\Cldr\Reader\DatesReader');
		$mockReader->expects($this->at(0))->method('getParsedFormat')->with($this->dummyLocale, $formatType)->will($this->returnValue($parsedFormat));
		$mockReader->expects($this->at(1))->method('getLocalizedLiteralsForLocale')->with($this->dummyLocale)->will($this->returnValue($this->mockLocalizedLiterals));

		$expectedDateTime = new \DateTime();
		$expectedDateTime->setTimezone(new \DateTimeZone($expectedResult['timezone']));
		$expectedDateTime->setDate($expectedResult['year'], $expectedResult['month'], $expectedResult['day']);
		$expectedDateTime->setTime($expectedResult['hour'], $expectedResult['minute'], $expectedResult['second']);

		$parser = new \F3\FLOW3\I18n\Parser\DatetimeParser();
		$parser->injectDatesReader($mockReader);

		$result = $parser->parse($datetimeToParse, $this->dummyLocale, $formatType, 'default', 'strict');
		$this->assertEquals($expectedDateTime, $result);
	}
}

?>