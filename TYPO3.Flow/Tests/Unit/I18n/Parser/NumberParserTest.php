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
 * Testcase for the NumberParser
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NumberParserTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $dummyLocale;

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
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->dummyLocale = new \F3\FLOW3\I18n\Locale('en_GB');
	}

	/**
	 * Data provider with valid numbers in strings, expected results and dummy
	 * parsedFormat arrays to use.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function parseStrings() {
		return array(
			array('+1 000,50foo', 1000.5, 'decimal', array_merge($this->templateFormat, array('positivePrefix' => '+', 'positiveSuffix' => 'foo'))),
			array('-98%', -0.98, 'percent', array_merge($this->templateFormat, array('positiveSuffix' => '%', 'negativeSuffix' => '%', 'multiplier' => 100))),
		);
	}

	/**
	 * @test
	 * @dataProvider parseStrings
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function strictParsingWorks($numberToParse, $expectedResult, $formatType, $parsedFormat) {
		$mockReader = $this->getMock('F3\FLOW3\I18n\Cldr\Reader\NumbersReader');
		$mockReader->expects($this->at(0))->method('parseFormatFromCldr')->with($this->dummyLocale, $formatType)->will($this->returnValue($parsedFormat));
		$mockReader->expects($this->at(1))->method('getLocalizedSymbolsForLocale')->with($this->dummyLocale)->will($this->returnValue($this->mockLocalizedSymbols));

		$parser = new \F3\FLOW3\I18n\Parser\NumberParser();
		$parser->injectNumbersReader($mockReader);

		$result = $parser->parse($numberToParse, $this->dummyLocale, $formatType, 'strict');
		$this->assertEquals($expectedResult, $result);
	}
}

?>