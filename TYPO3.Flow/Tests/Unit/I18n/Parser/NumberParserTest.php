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
	protected $sampleLocale;

	/**
	 * Localized symbols array used during formatting.
	 *
	 * @var array
	 */
	protected $sampleLocalizedSymbols = array(
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
		$this->sampleLocale = new \F3\FLOW3\I18n\Locale('en_GB');
	}

	/**
	 * Sample data for all test methods, with format type, string number to parse,
	 * expected parsed number, string format, and parsed format.
	 *
	 * Note that this data provider has everything needed by any test method, so
	 * not every element is used by every method.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function sampleNumbersEasyToParse() {
		return array(
			array('decimal', '01234,5670', 1234.567, '0000.0000#', array_merge($this->templateFormat, array('minDecimalDigits' => 4, 'maxDecimalDigits' => 5, 'minIntegerDigits' => 5))),
			array('decimal', '0,1', 0.1, '0.0###', array_merge($this->templateFormat, array('minDecimalDigits' => 1, 'maxDecimalDigits' => 4))),
			array('decimal', '1 000,25', 1000.25, '#,##0.05', array_merge($this->templateFormat, array('maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.05))),
			array('decimal', '9 999,9', 9999.9, '#,##0.0', array_merge($this->templateFormat, array('maxDecimalDigits' => 3, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3))),
			array('decimal', '(1 100,0)', -1100.0, '#,##0.0;(#)', array_merge($this->templateFormat, array('minDecimalDigits' => 1, 'maxDecimalDigits' => 1, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'negativePrefix' => '(', 'negativeSuffix' => ')'))),
			array('decimal', '-1,0', -1.0, '0.0;-#', array_merge($this->templateFormat, array('minDecimalDigits' => 1, 'maxDecimalDigits' => 1, 'negativePrefix' => '-'))),
			array('decimal', 'd1,0b', 1.0, 'd0.0b', array_merge($this->templateFormat, array('minDecimalDigits' => 1, 'maxDecimalDigits' => 1, 'positivePrefix' => 'd', 'positiveSuffix' => 'b'))),
			array('percent', '85%', 0.85, '#0%', array_merge($this->templateFormat, array('multiplier' => 100, 'positiveSuffix' => '%', 'negativeSuffix' => '%'))),
		);
	}

	/**
	 * Sample data with structure like in sampleNumbersEasyToParse(), but with
	 * number harder to parse - only lenient parsing mode should be able to
	 * parse them.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function sampleNumbersHardToParse() {
		return array(
			array('decimal', 'foo01234,56780bar', 1234.5678, '0000.0000#', array_merge($this->templateFormat, array('minDecimalDigits' => 4, 'maxDecimalDigits' => 5, 'minIntegerDigits' => 5))),
			array('decimal', 'foo+2 10 00,33baz', 21000.33, '#,##0.05', array_merge($this->templateFormat, array('maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.05))),
			array('decimal', '1foo10-', -110, '0.0;#-', array_merge($this->templateFormat, array('minDecimalDigits' => 1, 'maxDecimalDigits' => 1, 'negativePrefix' => '', 'negativeSuffix' => '-'))),
			array('percent', '%5,3%%', 0.053, '#00.00%', array_merge($this->templateFormat, array('multiplier' => 100, 'positiveSuffix' => '%', 'negativeSuffix' => '%', 'minIntegerDigits' => 2, 'minDecimalDigits' => 2))),
		);
	}

	/**
	 * @test
	 * @dataProvider sampleNumbersEasyToParse
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function strictParsingWorksCorrectlyForEasyNumbers($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat) {
		$parser = $this->getAccessibleMock('F3\FLOW3\I18n\Parser\NumberParser', array('dummy'));
		$result = $parser->_call('doParsingInStrictMode', $numberToParse, $parsedFormat, $this->sampleLocalizedSymbols);
		$this->assertEquals($expectedParsedNumber, $result);
	}

	/**
	 * @test
	 * @dataProvider sampleNumbersHardToParse
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function strictParsingReturnsFalseForHardNumbers($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat) {
		$parser = $this->getAccessibleMock('F3\FLOW3\I18n\Parser\NumberParser', array('dummy'));
		$result = $parser->_call('doParsingInStrictMode', $numberToParse, $parsedFormat, $this->sampleLocalizedSymbols);
		$this->assertEquals(FALSE, $result);
	}

	/**
	 * @test
	 * @dataProvider sampleNumbersEasyToParse
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function lenientParsingWorksCorrectlyForEasyNumbers($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat) {
		$parser = $this->getAccessibleMock('F3\FLOW3\I18n\Parser\NumberParser', array('dummy'));
		$result = $parser->_call('doParsingInLenientMode', $numberToParse, $parsedFormat, $this->sampleLocalizedSymbols);
		$this->assertEquals($expectedParsedNumber, $result);
	}

	/**
	 * @test
	 * @dataProvider sampleNumbersHardToParse
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function lenientParsingWorksCorrectlyForHardNumbers($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat) {
		$parser = $this->getAccessibleMock('F3\FLOW3\I18n\Parser\NumberParser', array('dummy'));
		$result = $parser->_call('doParsingInLenientMode', $numberToParse, $parsedFormat, $this->sampleLocalizedSymbols);
		$this->assertEquals($expectedParsedNumber, $result);
	}

	/**
	 * @test
	 * @dataProvider sampleNumbersEasyToParse
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function parsingUsingCustomPatternWorks($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat) {
		$mockNumbersReader = $this->getMock('F3\FLOW3\I18n\Cldr\Reader\NumbersReader');
		$mockNumbersReader->expects($this->once())->method('parseCustomFormat')->with($stringFormat)->will($this->returnValue($parsedFormat));
		$mockNumbersReader->expects($this->once())->method('getLocalizedSymbolsForLocale')->with($this->sampleLocale)->will($this->returnValue($this->sampleLocalizedSymbols));

		$parser = new \F3\FLOW3\I18n\Parser\NumberParser();
		$parser->injectNumbersReader($mockNumbersReader);

		$result = $parser->parseNumberWithCustomPattern($numberToParse, $stringFormat, $this->sampleLocale, TRUE);
		$this->assertEquals($expectedParsedNumber, $result);
	}

	/**
	 * @test
	 * @dataProvider sampleNumbersEasyToParse
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function specificFormattingMethodsWork($formatType, $numberToParse, $expectedParsedNumber, $stringFormat, array $parsedFormat) {
		$mockNumbersReader = $this->getMock('F3\FLOW3\I18n\Cldr\Reader\NumbersReader');
		$mockNumbersReader->expects($this->once())->method('parseFormatFromCldr')->with($this->sampleLocale, $formatType, 'default')->will($this->returnValue($parsedFormat));
		$mockNumbersReader->expects($this->once())->method('getLocalizedSymbolsForLocale')->with($this->sampleLocale)->will($this->returnValue($this->sampleLocalizedSymbols));

		$formatter = new \F3\FLOW3\I18n\Parser\NumberParser();
		$formatter->injectNumbersReader($mockNumbersReader);

		$methodName = 'parse' . ucfirst($formatType) . 'Number';
		$result = $formatter->$methodName($numberToParse, $this->sampleLocale);

		$this->assertEquals($expectedParsedNumber, $result);
	}
}

?>