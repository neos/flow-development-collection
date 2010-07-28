<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n\Parser;

/* *
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
 * Parser for numbers.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class NumberParser {

	/**
	 * @var \F3\FLOW3\I18n\Cldr\Reader\NumbersReader
	 */
	protected $numbersReader;

	/**
	 * @param \F3\FLOW3\I18n\Cldr\Reader\NumbersReader $numbersReader
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectNumbersReader(\F3\FLOW3\I18n\Cldr\Reader\NumbersReader $numbersReader) {
		$this->numbersReader = $numbersReader;
	}

	/**
	 * Parses number given as a string using locale information.
	 *
	 * Corresponding number format is taken from CLDR basing on locale object
	 * and type of number (decimal, percent, currency).
	 *
	 * Can work in strict or lenient mode.
	 *
	 * @param string $numberToParse Number to be parsed
	 * @param \F3\FLOW3\I18n\Locale $locale Locale to use
	 * @param string $formatType Type of format: decimal, percent, currency
	 * @param string $mode Work mode, one of: strict, lenient
	 * @return mixed Parsed float number or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function parse($numberToParse, \F3\FLOW3\I18n\Locale $locale, $formatType = 'decimal', $mode = 'strict') {
		if ($mode === 'strict') {
			return $this->doParsingInStrictMode($numberToParse, $locale, $formatType);
		} elseif ($mode === 'lenient') {
			return $this->doParsingInLenientMode($numberToParse, $locale, $formatType);
		} else {
			throw new \F3\FLOW3\I18n\Parser\Exception\UnsupportedParserModeException('Parsing mode "' . $mode . '" is not supported by NumberParser.', 1279723128);
		}
	}

	/**
	 * Parses number in strict mode.
	 *
	 * @param string $numberToParse Number to be parsed
	 * @param \F3\FLOW3\I18n\Locale $locale Locale to use
	 * @param string $formatType Type of format: decimal, percent, currency
	 * @return mixed Parsed float number or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function doParsingInStrictMode($numberToParse, \F3\FLOW3\I18n\Locale $locale, $formatType) {
		$parsedFormat = $this->numbersReader->parseFormatFromCldr($locale, $formatType);
		$localizedSymbols = $this->numbersReader->getLocalizedSymbolsForLocale($locale);

		$numberIsNegative = FALSE;
		if (!empty($parsedFormat['negativePrefix']) && \F3\FLOW3\I18n\Utility::stringBeginsWith($numberToParse, $parsedFormat['negativePrefix'])) {
			if (!empty($parsedFormat['negativeSuffix'])) {
				$numberToParse = substr($numberToParse, strlen($parsedFormat['negativePrefix']), - strlen($parsedFormat['negativeSuffix']));
			} else {
				$numberToParse = substr($numberToParse, strlen($parsedFormat['negativePrefix']));
			}

			$numberIsNegative = TRUE;
		} elseif (!empty($parsedFormat['positivePrefix']) && \F3\FLOW3\I18n\Utility::stringBeginsWith($numberToParse, $parsedFormat['positivePrefix'])) {
			if (!empty($parsedFormat['positiveSuffix'])) {
				$numberToParse = substr($numberToParse, strlen($parsedFormat['positivePrefix']), - strlen($parsedFormat['positiveSuffix']));
			} else {
				$numberToParse = substr($numberToParse, strlen($parsedFormat['positivePrefix']));
			}
		}

		if (strpos($numberToParse, $localizedSymbols['decimal']) === FALSE) {
			$numberToParse = str_replace($localizedSymbols['group'], '', $numberToParse);

			/**
			 * @todo Check if number of digits is in bounds
			 * @todo Check if there are only digits
			 */

			$floatValue = (int)$numberToParse;
		} else {
			/**
			 * @todo Check if position of decimal separator is not first or last in string
			 * @todo Check if number of digits is in bounds (integer and decimal part)
			 * @todo Check if there are only digits and decimal separator
			 */

			$floatValue = (float)(str_replace(array($localizedSymbols['group'], $localizedSymbols['decimal']), array('', '.'), $numberToParse));
		}

		$floatValue /= $parsedFormat['multiplier'];

		/**
		 * @todo Check the rounding
		 * @todo Currency
		 */

		if ($numberIsNegative) {
			$floatValue = 0 - $floatValue;
		}

		return $floatValue;
	}

	/**
	 * Parses number in lenient mode.
	 *
	 * @param string $numberToParse Number to be parsed
	 * @param \F3\FLOW3\I18n\Locale $locale Locale to use
	 * @param string $formatType Type of format: decimal, percent, currency
	 * @return mixed Parsed float number or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @todo Implement lenient parsing
	 */
	protected function doParsingInLenientMode($numberToParse, \F3\FLOW3\I18n\Locale $locale, $formatType) {
		return FALSE;
	}
}

?>