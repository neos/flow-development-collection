<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale\Cldr\Reader;

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
 * Testcase for the DatesReader
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DatesReaderTest extends \F3\Testing\BaseTestCase {

	/**
	 * Dummy locale used in methods where locale is needed.
	 *
	 * @var \F3\FLOW3\Locale\Locale
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
		$this->dummyLocale = new \F3\FLOW3\Locale\Locale('en');
		$this->mockLocalizedLiterals = require(__DIR__ . '/../../Fixtures/MockLocalizedLiteralsArray.php');
		$this->sampleDateTime = new \DateTime("@1276192176");
		$this->sampleDateTime->setTimezone(new \DateTimeZone('Europe/London'));
	}

	/**
	 * Setting cache expectations is partially same for many tests, so it's been
	 * extracted to this method.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function createCacheExpectations($mockCache, $useEmptyMockLiterals = FALSE) {
		$mockCache->expects($this->at(0))->method('has')->with('parsedFormats')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(1))->method('get')->with('parsedFormats')->will($this->returnValue(array()));
		$mockCache->expects($this->at(2))->method('has')->with('parsedFormatsIndices')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(3))->method('get')->with('parsedFormatsIndices')->will($this->returnValue(array()));
		$mockCache->expects($this->at(4))->method('has')->with('localizedLiterals')->will($this->returnValue(TRUE));
		
		if ($useEmptyMockLiterals) {
			$mockCache->expects($this->at(5))->method('get')->with('localizedLiterals')->will($this->returnValue(array()));
		} else {
			$mockCache->expects($this->at(5))->method('get')->with('localizedLiterals')->will($this->returnValue(array((string)$this->dummyLocale => $this->mockLocalizedLiterals)));
		}

	}

	/**
	 * Data provider with valid format strings and expected results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatStrings() {
		return array(
			array('yyyy.MM.dd G', array('yyyy', array('.'), 'MM', array('.'), 'dd', array(' '), 'G')),
			array('HH:mm:ss zzz', array('HH', array(':'), 'mm', array(':'), 'ss', array(' '), 'zzz')),
			array('EEE, MMM d, \'\'yy', array('EEE', array(','), array(' '), 'MMM', array(' '), 'd', array(','), array(' '), array('\''), 'yy')),
			array('hh \'o\'\'clock\' a, zzzz', array('hh', array(' '), array('o'), array('\''), array('clock'), array(' '), 'a', array(','), array(' '), 'zzzz')),
			array('QQyyLLLLDFEEEE', array('QQ', 'yy', 'LLLL', 'D', 'F', 'EEEE')),
			array('QQQMMMMMEEEEEwk', array('QQQ', 'MMMMM', 'EEEEE', 'w', 'k')),
			array('GGGGGKSWqqqqGGGGV', array('GGGGG', 'K', 'S', 'W', 'qqqq', 'GGGG', 'V')),
		);
	}

	/**
	 * @test
	 * @dataProvider formatStrings
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatStringsAreParsedCorrectly($format, $expectedResult) {
		$reader = $this->getAccessibleMock('F3\FLOW3\Locale\Cldr\Reader\DatesReader', array('dummy'));

		$result = $reader->_call('parseFormat', $format);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with example parsed formats, and expected results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function sampleDateTimesAndParsedFormats() {
		$parsedFormats = $this->formatStrings();

		return array(
			array($parsedFormats[0][1], '2010.06.10 AD'),
			array($parsedFormats[1][1], '18:49:36 BST'),
			array($parsedFormats[2][1], 'Thu, Jun 10, \'10'),
			array($parsedFormats[3][1], '06 o\'clock p.m., Europe/London'),
			array($parsedFormats[4][1], '0210January1612Thursday'),
			array($parsedFormats[5][1], 'Q26T2318'),
			array($parsedFormats[6][1], 'A6032nd quarterAnno Domini'),
		);
	}

	/**
	 * @test
	 * @dataProvider sampleDateTimesAndParsedFormats
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function parsedFormatsAreUsedCorrectly($parsedFormat, $expectedResult) {
		$reader = $this->getAccessibleMock('F3\FLOW3\Locale\Cldr\Reader\DatesReader', array('dummy'));

		$result = $reader->_call('doFormattingWithParsedFormat', $this->sampleDateTime, $parsedFormat, $this->mockLocalizedLiterals);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with example formats, and expected results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function dataForFormatDateTimeWithCustomPatternMethod() {
		return array(
			array('yyyy.MM.dd G', '2010.06.10 AD'),
		);
	}

	/**
	 * @test
	 * @dataProvider dataForFormatDateTimeWithCustomPatternMethod
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatDateTimeWithCustomPatternWorks($format, $expectedResult) {
		$getRawArrayCallback = function() {
			$args = func_get_args();
			$mockDatesCldrData = require(__DIR__ . '/../../Fixtures/MockDatesParsedCLDRData.php');

			$lastPartOfPath = substr($args[0], strrpos($args[0], '/') + 1);
			if ($lastPartOfPath === 'eras') {
				return $mockDatesCldrData['eras'];
			} else {
				return $mockDatesCldrData[str_replace('Context', '', $lastPartOfPath) . 's'];
			}
		};

		$getValueOfAttributeCallback = function() {
			$args = func_get_args();
			$attribute = $args[0];
			$attributeNumber = $args[1];

			$attributes = explode('" ', $attribute);
			if (count($attributes) < $attributeNumber) {
				return FALSE;
			} else if (count($attributes) === $attributeNumber) {
				return substr($attributes[$attributeNumber - 1], strpos($attributes[$attributeNumber - 1], '"') + 1, -1);
			} else {
				return substr($attributes[$attributeNumber - 1], strpos($attributes[$attributeNumber - 1], '"') + 1);
			}
		};

		$mockModel = $this->getMock('F3\FLOW3\Locale\Cldr\HierarchicalCldrModel');
		$mockModel->expects($this->exactly(5))->method('getRawArray')->will($this->returnCallback($getRawArrayCallback));
		$mockModel->expects($this->any())->method('getValueOfAttribute')->will($this->returnCallback($getValueOfAttributeCallback));
		
		$mockRepository = $this->getMock('F3\FLOW3\Locale\Cldr\CldrRepository');
		$mockRepository->expects($this->once())->method('getHierarchicalModel')->with('main', $this->dummyLocale)->will($this->returnValue($mockModel));

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$this->createCacheExpectations($mockCache, TRUE);

		$reader = $this->getAccessibleMock('F3\FLOW3\Locale\Cldr\Reader\DatesReader', array('dummy'));
		$reader->injectCldrRepository($mockRepository);
		$reader->injectCache($mockCache);
		$reader->initializeObject();

		$result = $reader->formatDateTimeWithCustomPattern($this->sampleDateTime, $format, $this->dummyLocale);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with date formats, time formats, dateTime formats, and
	 * expected results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function dataForFormatDateTimeWorksMethod() {
		return array(
			array('EEEE, y MMMM dd', 'HH:mm:ss zzzz', '{1} {0}', 'Thursday, 2010 January 10 18:49:36 Europe/London'),
		);
	}

	/**
	 * @test
	 * @dataProvider dataForFormatDateTimeWorksMethod
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatDateTimeWorks($dateFormat, $timeFormat, $dateTimeFormat, $expectedResult) {
		$mockModel = $this->getMock('F3\FLOW3\Locale\Cldr\HierarchicalCldrModel');
		$mockModel->expects($this->at(0))->method('getOneElement')->with('dates/calendars/calendar/type="gregorian"/dateFormats/dateFormatLength/type="full"/dateFormat/pattern')->will($this->returnValue($dateFormat));
		$mockModel->expects($this->at(1))->method('getOneElement')->with('dates/calendars/calendar/type="gregorian"/timeFormats/timeFormatLength/type="full"/timeFormat/pattern')->will($this->returnValue($timeFormat));
		$mockModel->expects($this->at(2))->method('getOneElement')->with('dates/calendars/calendar/type="gregorian"/dateTimeFormats/dateTimeFormatLength/type="full"/dateTimeFormat/pattern')->will($this->returnValue($dateTimeFormat));

		$mockRepository = $this->getMock('F3\FLOW3\Locale\Cldr\CldrRepository');
		$mockRepository->expects($this->exactly(3))->method('getHierarchicalModel')->with('main', $this->dummyLocale)->will($this->returnValue($mockModel));

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$this->createCacheExpectations($mockCache);

		$reader = $this->getAccessibleMock('F3\FLOW3\Locale\Cldr\Reader\DatesReader', array('dummy'));
		$reader->injectCldrRepository($mockRepository);
		$reader->injectCache($mockCache);
		$reader->initializeObject();

		$result = $reader->formatDateTime($this->sampleDateTime, $this->dummyLocale, 'full');
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with example formats, expected results, and format types.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function dataForSpecificFormattingMethods() {
		return array(
			array('EEEE, y MMMM dd', 'Thursday, 2010 January 10', 'date'),
			array('HH:mm:ss zzzz', '18:49:36 Europe/London', 'time'),
		);
	}

	/**
	 * @test
	 * @dataProvider dataForSpecificFormattingMethods
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function specificFormattingMethodsWork($formatString, $expectedResult, $formattingType) {
		$mockModel = $this->getMock('F3\FLOW3\Locale\Cldr\HierarchicalCldrModel');
		$mockModel->expects($this->once())->method('getOneElement')->with('dates/calendars/calendar/type="gregorian"/' . $formattingType . 'Formats/' . $formattingType . 'FormatLength/type="full"/' . $formattingType . 'Format/pattern')->will($this->returnValue($formatString));

		$mockRepository = $this->getMock('F3\FLOW3\Locale\Cldr\CldrRepository');
		$mockRepository->expects($this->once())->method('getHierarchicalModel')->with('main', $this->dummyLocale)->will($this->returnValue($mockModel));

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$this->createCacheExpectations($mockCache);
		$mockCache->expects($this->at(6))->method('set')->with('parsedFormats');
		$mockCache->expects($this->at(7))->method('set')->with('parsedFormatsIndices');
		$mockCache->expects($this->at(8))->method('set')->with('localizedLiterals');

		$reader = $this->getAccessibleMock('F3\FLOW3\Locale\Cldr\Reader\DatesReader', array('dummy'));
		$reader->injectCldrRepository($mockRepository);
		$reader->injectCache($mockCache);
		$reader->initializeObject();

		$name = 'format' . ucfirst($formattingType);
		$result = $reader->$name($this->sampleDateTime, $this->dummyLocale, 'full');
		$this->assertEquals($expectedResult, $result);

		$reader->shutdownObject();
	}
}

?>
