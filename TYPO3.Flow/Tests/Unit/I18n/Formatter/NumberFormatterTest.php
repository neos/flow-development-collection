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
 * Testcase for the NumberFormatter
 *
 */
class NumberFormatterTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\I18n\Locale
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
	 */
	public function setUp() {
		$this->sampleLocale = new \TYPO3\Flow\I18n\Locale('en');
	}

	/**
	 * @test
	 */
	public function formatMethodsAreChoosenCorrectly() {
		$sampleNumber = 123.456;

		$formatter = $this->getAccessibleMock('TYPO3\Flow\I18n\Formatter\NumberFormatter', array('formatDecimalNumber', 'formatPercentNumber'));
		$formatter->expects($this->at(0))->method('formatDecimalNumber')->with($sampleNumber, $this->sampleLocale, \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_DEFAULT)->will($this->returnValue('bar1'));
		$formatter->expects($this->at(1))->method('formatPercentNumber')->with($sampleNumber, $this->sampleLocale, \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_DEFAULT)->will($this->returnValue('bar2'));

		$result = $formatter->format($sampleNumber, $this->sampleLocale);
		$this->assertEquals('bar1', $result);

		$result = $formatter->format($sampleNumber, $this->sampleLocale, array('percent'));
		$this->assertEquals('bar2', $result);
	}

	/**
	 * Data provider with example numbers, parsed formats, and expected results.
	 *
	 * Note: order of elements in returned array is actually different (sample
	 * number, expected result, and parsed format to use), in order to make it
	 * more readable.
	 *
	 * @return array
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
	 */
	public function parsedFormatsAreUsedCorrectly($number, $expectedResult, array $parsedFormat) {
		$formatter = $this->getAccessibleMock('TYPO3\Flow\I18n\Formatter\NumberFormatter', array('dummy'));
		$result = $formatter->_call('doFormattingWithParsedFormat', $number, $parsedFormat, $this->sampleLocalizedSymbols);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with example numbers, parsed formats, and expected results.
	 *
	 * @return array
	 */
	public function customFormatsAndFormatterNumbers() {
		return array(
			array(
				1234.567, '00000.0000',
				array_merge($this->templateFormat, array('minDecimalDigits' => 4, 'maxDecimalDigits' => 4, 'minIntegerDigits' => 5)),
				'01234,5670',
			),
			array(
				0.10004, '0.0###',
				array_merge($this->templateFormat, array('minDecimalDigits' => 1, 'maxDecimalDigits' => 4)),
				'0,1',
			),
			array(
				-1099.99, '#,##0.0;(#)',
				array_merge($this->templateFormat, array('minDecimalDigits' => 1, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'negativePrefix' => '(', 'negativeSuffix' => ')')),
				'(1 100,0)'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider customFormatsAndFormatterNumbers
	 */
	public function formattingUsingCustomPatternWorks($number, $format, array $parsedFormat, $expectedResult) {
		$mockNumbersReader = $this->getMock('TYPO3\Flow\I18n\Cldr\Reader\NumbersReader');
		$mockNumbersReader->expects($this->once())->method('parseCustomFormat')->with($format)->will($this->returnValue($parsedFormat));
		$mockNumbersReader->expects($this->once())->method('getLocalizedSymbolsForLocale')->with($this->sampleLocale)->will($this->returnValue($this->sampleLocalizedSymbols));

		$formatter = new \TYPO3\Flow\I18n\Formatter\NumberFormatter();
		$formatter->injectNumbersReader($mockNumbersReader);

		$result = $formatter->formatNumberWithCustomPattern($number, $format, $this->sampleLocale);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Data provider with numbers, parsed formats, expected results, format types
	 * (decimal, percent or currency) and currency sign if applicable.
	 *
	 * @return array
	 */
	public function sampleDataForSpecificFormattingMethods() {
		return array(
			array(
				9999.9,
				array_merge($this->templateFormat, array('maxDecimalDigits' => 3, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3)),
				'9 999,9', \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL
			),
			array(
				0.85,
				array_merge($this->templateFormat, array('multiplier' => 100, 'positiveSuffix' => '%', 'negativeSuffix' => '%')),
				'85%', \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_PERCENT),
			array(
				5.5,
				array_merge($this->templateFormat, array('minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'positiveSuffix' => ' ¤', 'negativeSuffix' => ' ¤')),
				'5,50 zł', \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_CURRENCY, 'zł'
			),
			array(
				acos(8),
				array_merge($this->templateFormat, array('minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3)),
				'NaN', \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_DECIMAL
			),
			array(
				log(0),
				array_merge($this->templateFormat, array('minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3)),
				'-∞', \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_PERCENT
			),
			array(
				-log(0),
				array_merge($this->templateFormat, array('minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3)),
				'∞', \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_CURRENCY
			),
		);
	}

	/**
	 * @test
	 * @dataProvider sampleDataForSpecificFormattingMethods
	 */
	public function specificFormattingMethodsWork($number, array $parsedFormat, $expectedResult, $formatType, $currencySign = NULL) {
		$mockNumbersReader = $this->getMock('TYPO3\Flow\I18n\Cldr\Reader\NumbersReader');
		$mockNumbersReader->expects($this->once())->method('parseFormatFromCldr')->with($this->sampleLocale, $formatType, 'default')->will($this->returnValue($parsedFormat));
		$mockNumbersReader->expects($this->once())->method('getLocalizedSymbolsForLocale')->with($this->sampleLocale)->will($this->returnValue($this->sampleLocalizedSymbols));

		$formatter = new \TYPO3\Flow\I18n\Formatter\NumberFormatter();
		$formatter->injectNumbersReader($mockNumbersReader);

		if ($formatType === 'currency') {
			$result = $formatter->formatCurrencyNumber($number, $this->sampleLocale, $currencySign);
		} else {
			$methodName = 'format' . ucfirst($formatType) . 'Number';
			$result = $formatter->$methodName($number, $this->sampleLocale);
		}
		$this->assertEquals($expectedResult, $result);
	}
}

?>