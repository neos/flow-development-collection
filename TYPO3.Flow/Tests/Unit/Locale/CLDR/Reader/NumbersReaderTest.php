<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale\CLDR\Reader;

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
 * Testcase for the NumbersReader
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NumbersReaderTest extends \F3\Testing\BaseTestCase {

	/**
	 * Localized symbols array used during formatting.
	 *
	 * @var array
	 */
	protected $mockLocalizedSymbols = array(
		'decimal' => ',',
		'group' => ' ',
		'percentSign' => '%',
		'minusSign' => '-',
		'perMille' => '‰',
		'infinity' => '∞',
		'nan' => 'NaN',
	);

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
	 * Dummy locale used in methods where locale is needed.
	 *
	 * @var \F3\FLOW3\Locale\Locale
	 */
	protected $dummyLocale;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->dummyLocale = new \F3\FLOW3\Locale\Locale('en');
	}

	/**
	 * Data provider with valid format strings and expected results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatStrings() {
		return array(
			array('#,##0.###', array_merge($this->templateFormat, array('maxDecimalDigits' => 3, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3))),
			array('#,##,##0%', array_merge($this->templateFormat, array('positiveSuffix' => '%', 'negativeSuffix' => '%', 'multiplier' => 100, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 2))),
			array('¤ #,##0.00;¤ #,##0.00-', array_merge($this->templateFormat, array('positivePrefix' => '¤ ', 'negativePrefix' => '¤ ', 'negativeSuffix' => '-', 'minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3))),
			array('#,##0.05', array_merge($this->templateFormat, array('minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.05))),
		);
	}

	/**
	 * @test
	 * @dataProvider formatStrings
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatStringsAreParsedCorrectly($format, $expectedResult) {
		$reader = $this->getAccessibleMock('F3\FLOW3\Locale\CLDR\Reader\NumbersReader', array('dummy'));

		$result = $reader->_call('parseFormat', $format);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with example numbers, parsed formats, and expected results.
	 *
	 * Note: order of elements in returned array is actually different (sample
	 * number, expected result, and parsed format to use), in order to make it
	 * more readable.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function sampleNumbersAndParsedFormats() {
		return array(
			array(1234.567, '01234,5670', array_merge($this->templateFormat, array('minDecimalDigits' => 4, 'maxDecimalDigits' => 5, 'minIntegerDigits' => 5))),
			array(0.10004, '0,1', array_merge($this->templateFormat, array('minDecimalDigits' => 1, 'maxDecimalDigits' => 4))),
			array(1000.23, '1 000,25', array_merge($this->templateFormat, array('maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.05)))
		);
	}

	/**
	 * @test
	 * @dataProvider sampleNumbersAndParsedFormats
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function parsedFormatsAreUsedCorrectly($number, $expectedResult, $parsedFormat) {
		$reader = $this->getAccessibleMock('F3\FLOW3\Locale\CLDR\Reader\NumbersReader', array('dummy'));
		$result = $reader->_call('doFormattingWithParsedFormat', $number, $parsedFormat, $this->mockLocalizedSymbols);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with example numbers, formats, and expected results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function dataForFormatNumberWithCustomPatternMethod() {
		return array(
			array(1234.567, '00000.0000', '01234,5670'),
			array(0.10004, '0.0###', '0,1'),
			array(-1099.99, '#,##0.0;(#)', '(1 100,0)')
		);
	}

	/**
	 * @test
	 * @dataProvider dataForFormatNumberWithCustomPatternMethod
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatNumberWithCustomPatternWorks($number, $format, $expectedResult) {
		$mockModel = $this->getMock('F3\FLOW3\Locale\CLDR\HierarchicalCLDRModel');
		$mockModel->expects($this->once())->method('getRawArray')->with('numbers/symbols')->will($this->returnValue($this->mockLocalizedSymbols));

		$mockRepository = $this->getMock('F3\FLOW3\Locale\CLDR\CLDRRepository');
		$mockRepository->expects($this->once())->method('getHierarchicalModel')->with('main', $this->dummyLocale)->will($this->returnValue($mockModel));

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->at(0))->method('has')->with('parsedFormats')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(1))->method('get')->with('parsedFormats')->will($this->returnValue(array()));
		$mockCache->expects($this->at(2))->method('has')->with('parsedFormatsIndices')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(3))->method('get')->with('parsedFormatsIndices')->will($this->returnValue(array()));
		$mockCache->expects($this->at(4))->method('has')->with('localizedSymbols')->will($this->returnValue(TRUE));
		$mockCache->expects($this->at(5))->method('get')->with('localizedSymbols')->will($this->returnValue(array()));
		$mockCache->expects($this->at(6))->method('set')->with('parsedFormats');
		$mockCache->expects($this->at(7))->method('set')->with('parsedFormatsIndices');
		$mockCache->expects($this->at(8))->method('set')->with('localizedSymbols');

		$reader = $this->getAccessibleMock('F3\FLOW3\Locale\CLDR\Reader\NumbersReader', array('dummy'));
		$reader->injectCLDRRepository($mockRepository);
		$reader->injectCache($mockCache);
		$reader->initializeObject();

		$result = $reader->formatNumberWithCustomPattern($number, $format, $this->dummyLocale);
		$this->assertEquals($expectedResult, $result);

		$reader->shutdownObject();
	}

	/**
	 * Data provider with numbers, formats, expected results, format types
	 * (decimal, percent or currency) and currency sign if applicable.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function dataForSpecificFormattingMethods() {
		return array(
			array(9999.9, '#,##0.###', '9 999,9', 'decimal'),
			array(0.85, '##0%', '85%', 'percent'),
			array(5.5, '#,##0.00 ¤', '5,50 zł', 'currency', 'zł'),
			array(acos(8), '#,##0.00', 'NaN', 'decimal'),
			array(log(0), '#,##0.00', '-∞', 'percent'),
			array(-log(0), '#,##0.00', '∞', 'currency'),
		);
	}

	/**
	 * @test
	 * @dataProvider dataForSpecificFormattingMethods
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function specificFormattingMethodsWork($unformattedNumber, $formatString, $expectedResult, $formattingType, $currencySign = NULL) {
		$mockModel = $this->getMock('F3\FLOW3\Locale\CLDR\HierarchicalCLDRModel');
		$mockModel->expects($this->once())->method('getOneElement')->with('numbers/' . $formattingType . 'Formats/' . $formattingType . 'FormatLength/' . $formattingType . 'Format/pattern')->will($this->returnValue($formatString));

		$mockRepository = $this->getMock('F3\FLOW3\Locale\CLDR\CLDRRepository');
		$mockRepository->expects($this->once())->method('getHierarchicalModel')->with('main', $this->dummyLocale)->will($this->returnValue($mockModel));

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->at(0))->method('has')->with('parsedFormats')->will($this->returnValue(FALSE));
		$mockCache->expects($this->at(1))->method('has')->with('parsedFormatsIndices')->will($this->returnValue(FALSE));
		$mockCache->expects($this->at(2))->method('has')->with('localizedSymbols')->will($this->returnValue(FALSE));
		$mockCache->expects($this->at(3))->method('set')->with('parsedFormats');
		$mockCache->expects($this->at(4))->method('set')->with('parsedFormatsIndices');
		$mockCache->expects($this->at(5))->method('set')->with('localizedSymbols');

		$reader = $this->getAccessibleMock('F3\FLOW3\Locale\CLDR\Reader\NumbersReader', array('getLocalizedSymbolsForLocale'));
		$reader->expects($this->once())->method('getLocalizedSymbolsForLocale')->will($this->returnValue($this->mockLocalizedSymbols));
		$reader->injectCLDRRepository($mockRepository);
		$reader->injectCache($mockCache);
		$reader->initializeObject();

		if ($formattingType === 'currency') {
			$result = $reader->formatCurrencyNumber($unformattedNumber, $this->dummyLocale, $currencySign);
		} else {
			$methodName = 'format' . ucfirst($formattingType) . 'Number';
			$result = $reader->$methodName($unformattedNumber, $this->dummyLocale);
		}
		$this->assertEquals($expectedResult, $result);

		$reader->shutdownObject();
	}

	/**
	 * Data provider with formats not supported by current implementation of
	 * NumbersReader.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
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
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function unsupportedFormatsAreNotParsed($format) {
		$reader = $this->getAccessibleMock('F3\FLOW3\Locale\CLDR\Reader\NumbersReader', array('dummy'));

		$result = $reader->_call('parseFormat', $format);
		$this->assertEquals(FALSE, $result);
	}
}

?>