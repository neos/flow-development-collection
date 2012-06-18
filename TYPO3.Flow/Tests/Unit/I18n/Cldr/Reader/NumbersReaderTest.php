<?php
namespace TYPO3\FLOW3\Tests\Unit\I18n\Cldr\Reader;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the NumbersReader
 *
 */
class NumbersReaderTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * Dummy locale used in methods where locale is needed.
	 *
	 * @var \TYPO3\FLOW3\I18n\Locale
	 */
	protected $sampleLocale;

	/**
	 * A template array of parsed format. Used as a base in order to not repeat
	 * same fields everywhere.
	 *
	 * @var array
	 */
	protected $templateFormat = array(
		'positivePrefix' => '',
		'positiveSuffix' => '',
		'negativePrefix' => '-',
		'negativeSuffix' => '',

		'multiplier' => 1,

		'minDecimalDigits' => 0,
		'maxDecimalDigits' => 0,

		'minIntegerDigits' => 1,

		'primaryGroupingSize' => 0,
		'secondaryGroupingSize' => 0,

		'rounding' => 0,
	);

	/**
	 * @return void
	 */
	public function setUp() {
		$this->sampleLocale = new \TYPO3\FLOW3\I18n\Locale('en');
	}

	/**
	 * @test
	 */
	public function formatIsCorrectlyReadFromCldr() {
		$mockModel = $this->getMock('TYPO3\FLOW3\I18n\Cldr\CldrModel', array(), array(array()));
		$mockModel->expects($this->once())->method('getElement')->with('numbers/decimalFormats/decimalFormatLength/decimalFormat/pattern')->will($this->returnValue('mockFormatString'));

		$mockRepository = $this->getMock('TYPO3\FLOW3\I18n\Cldr\CldrRepository');
		$mockRepository->expects($this->once())->method('getModelForLocale')->with($this->sampleLocale)->will($this->returnValue($mockModel));

		$mockCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->at(0))->method('has')->with('parsedFormats')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(1))->method('has')->with('parsedFormatsIndices')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(2))->method('has')->with('localizedSymbols')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(3))->method('get')->with('parsedFormats')->will($this->returnValue(array()));
		$mockCache->expects($this->at(4))->method('get')->with('parsedFormatsIndices')->will($this->returnValue(array()));
		$mockCache->expects($this->at(5))->method('get')->with('localizedSymbols')->will($this->returnValue(array()));
		$mockCache->expects($this->at(6))->method('set')->with('parsedFormats');
		$mockCache->expects($this->at(7))->method('set')->with('parsedFormatsIndices');
		$mockCache->expects($this->at(8))->method('set')->with('localizedSymbols');

		$reader = $this->getAccessibleMock('TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader', array('parseFormat'));
		$reader->expects($this->once())->method('parseFormat')->with('mockFormatString')->will($this->returnValue('mockParsedFormat'));
		$reader->injectCldrRepository($mockRepository);
		$reader->injectCache($mockCache);
		$reader->initializeObject();

		$result = $reader->parseFormatFromCldr($this->sampleLocale, \TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL);
		$this->assertEquals('mockParsedFormat', $result);

		$reader->shutdownObject();
	}

	/**
	 * Data provider with valid format strings and expected results.
	 *
	 * @return array
	 */
	public function formatStringsAndParsedFormats() {
		return array(
			array('#,##0.###', array_merge($this->templateFormat, array('maxDecimalDigits' => 3, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3))),
			array('#,##,##0%', array_merge($this->templateFormat, array('positiveSuffix' => '%', 'negativeSuffix' => '%', 'multiplier' => 100, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 2))),
			array('¤ #,##0.00;¤ #,##0.00-', array_merge($this->templateFormat, array('positivePrefix' => '¤ ', 'negativePrefix' => '¤ ', 'negativeSuffix' => '-', 'minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3))),
			array('#,##0.05', array_merge($this->templateFormat, array('minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.05))),
		);
	}

	/**
	 * @test
	 * @dataProvider formatStringsAndParsedFormats
	 */
	public function formatStringsAreParsedCorrectly($format, array $expectedResult) {
		$reader = $this->getAccessibleMock('TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader', array('dummy'));

		$result = $reader->_call('parseFormat', $format);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with formats not supported by current implementation of
	 * NumbersReader.
	 *
	 * @return array
	 */
	public function unsupportedFormats() {
		return array(
			array('0.###E0'),
			array('@##'),
			array('* #0'),
			array('\'#\'##'),
		);
	}

	/**
	 * @test
	 * @dataProvider unsupportedFormats
	 * @expectedException \TYPO3\FLOW3\I18n\Cldr\Reader\Exception\UnsupportedNumberFormatException
	 */
	public function throwsExceptionWhenUnsupportedFormatsEncountered($format) {
		$reader = $this->getAccessibleMock('TYPO3\FLOW3\I18n\Cldr\Reader\NumbersReader', array('dummy'));

		$reader->_call('parseFormat', $format);
	}
}

?>