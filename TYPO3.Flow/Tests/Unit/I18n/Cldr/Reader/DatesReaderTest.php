<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n\Cldr\Reader;

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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DatesReaderTest extends \F3\Testing\BaseTestCase {

	/**
	 * Dummy locale used in methods where locale is needed.
	 *
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $sampleLocale;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->sampleLocale = new \F3\FLOW3\I18n\Locale('en');
	}

	/**
	 * Setting cache expectations is partially same for many tests, so it's been
	 * extracted to this method.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function createCacheExpectations($mockCache) {
		$mockCache->expects($this->at(0))->method('has')->with('parsedFormats')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(1))->method('has')->with('parsedFormatsIndices')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(2))->method('has')->with('localizedLiterals')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(3))->method('get')->with('parsedFormats')->will($this->returnValue(array()));
		$mockCache->expects($this->at(4))->method('get')->with('parsedFormatsIndices')->will($this->returnValue(array()));
		$mockCache->expects($this->at(5))->method('get')->with('localizedLiterals')->will($this->returnValue(array()));
		$mockCache->expects($this->at(6))->method('set')->with('parsedFormats');
		$mockCache->expects($this->at(7))->method('set')->with('parsedFormatsIndices');
		$mockCache->expects($this->at(8))->method('set')->with('localizedLiterals');
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatIsCorrectlyReadFromCldr() {
		$mockModel = $this->getMock('F3\FLOW3\I18n\Cldr\CldrModelCollection');
		$mockModel->expects($this->once())->method('getRawArray')->with('dates/calendars/calendar/type="gregorian"/dateFormats/default')->will($this->returnValue(array('choice="medium"' => '')));
		$mockModel->expects($this->once())->method('getElement')->with('dates/calendars/calendar/type="gregorian"/dateFormats/dateFormatLength/type="medium"/dateFormat/pattern')->will($this->returnValue('mockFormatString'));

		$mockRepository = $this->getMock('F3\FLOW3\I18n\Cldr\CldrRepository');
		$mockRepository->expects($this->once())->method('getModelCollection')->with('main', $this->sampleLocale)->will($this->returnValue($mockModel));

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$this->createCacheExpectations($mockCache);

		$reader = $this->getAccessibleMock('F3\FLOW3\I18n\Cldr\Reader\DatesReader', array('parseFormat'));
		$reader->expects($this->once())->method('parseFormat')->with('mockFormatString')->will($this->returnValue('mockParsedFormat'));
		$reader->injectCldrRepository($mockRepository);
		$reader->injectCache($mockCache);
		$reader->initializeObject();

		$result = $reader->parseFormatFromCldr($this->sampleLocale, \F3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE, \F3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT);
		$this->assertEquals('mockParsedFormat', $result);

		$reader->shutdownObject();
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function dateTimeFormatIsParsedCorrectly() {
		$mockModel = $this->getMock('F3\FLOW3\I18n\Cldr\CldrModelCollection');
		$mockModel->expects($this->at(0))->method('getElement', 'dates/calendars/calendar/type="gregorian"/dateTimeFormats/dateTimeFormatLength/type="full"/dateTimeFormat/pattern')->will($this->returnValue('foo {0} {1} bar'));
		$mockModel->expects($this->at(1))->method('getElement', 'dates/calendars/calendar/type="gregorian"/dateFormats/dateFormatLength/type="full"/dateFormat/pattern')->will($this->returnValue('dMy'));
		$mockModel->expects($this->at(2))->method('getElement', 'dates/calendars/calendar/type="gregorian"/timeFormats/timeFormatLength/type="full"/timeFormat/pattern')->will($this->returnValue('hms'));

		$mockRepository = $this->getMock('F3\FLOW3\I18n\Cldr\CldrRepository');
		$mockRepository->expects($this->exactly(3))->method('getModelCollection')->with('main', $this->sampleLocale)->will($this->returnValue($mockModel));

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$this->createCacheExpectations($mockCache);

		$reader = new \F3\FLOW3\I18n\Cldr\Reader\DatesReader();
		$reader->injectCldrRepository($mockRepository);
		$reader->injectCache($mockCache);
		$reader->initializeObject();

		$result = $reader->parseFormatFromCldr($this->sampleLocale, \F3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATETIME, \F3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_FULL);
		$this->assertEquals(array(array('foo '), 'h', 'm', 's', array(' '), 'd', 'M', 'y', array(' bar')), $result);
		$reader->shutdownObject();
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function localizedLiteralsAreCorrectlyReadFromCldr() {
		$getRawArrayCallback = function() {
			$args = func_get_args();
			$mockDatesCldrData = require(__DIR__ . '/../../Fixtures/MockDatesParsedCldrData.php');

			$lastPartOfPath = substr($args[0], strrpos($args[0], '/') + 1);
			if ($lastPartOfPath === 'eras') {
				return $mockDatesCldrData['eras'];
			} else {
				return $mockDatesCldrData[str_replace('Context', '', $lastPartOfPath) . 's'];
			}
		};

		$mockModel = $this->getMock('F3\FLOW3\I18n\Cldr\CldrModelCollection');
		$mockModel->expects($this->exactly(5))->method('getRawArray')->will($this->returnCallback($getRawArrayCallback));

		$mockRepository = $this->getMock('F3\FLOW3\I18n\Cldr\CldrRepository');
		$mockRepository->expects($this->once())->method('getModelCollection')->with('main', $this->sampleLocale)->will($this->returnValue($mockModel));

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$this->createCacheExpectations($mockCache);

		$reader = new \F3\FLOW3\I18n\Cldr\Reader\DatesReader();
		$reader->injectCldrRepository($mockRepository);
		$reader->injectCache($mockCache);
		$reader->initializeObject();

		$result = $reader->getLocalizedLiteralsForLocale($this->sampleLocale);
		$this->assertEquals('January', $result['months']['format']['wide'][1]);
		$this->assertEquals('Sat', $result['days']['format']['abbreviated']['sat']);
		$this->assertEquals('1', $result['quarters']['format']['narrow'][1]);
		$this->assertEquals('a.m.', $result['dayPeriods']['stand-alone']['wide']['am']);
		$this->assertEquals('Anno Domini', $result['eras']['eraNames'][1]);

		$reader->shutdownObject();
	}

	/**
	 * Data provider with valid format strings and expected results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatStringsAndParsedFormats() {
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
	 * @dataProvider formatStringsAndParsedFormats
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatStringsAreParsedCorrectly($format, $expectedResult) {
		$reader = $this->getAccessibleMock('F3\FLOW3\I18n\Cldr\Reader\DatesReader', array('dummy'));

		$result = $reader->_call('parseFormat', $format);
		$this->assertEquals($expectedResult, $result);
	}
}

?>