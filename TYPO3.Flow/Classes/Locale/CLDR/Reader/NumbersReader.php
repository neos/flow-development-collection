<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale\CLDR\Reader;

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
 * A reader for data placed in "numbers" tag in CLDR.
 *
 * The most important functionality of this class is formatting numbers. This is
 * an implementation of Number Format Patterns as defined in Unicode Technical
 * Standard #35. However, it's not complete implementation as for now.
 *
 * Following features are missing (in brackets - chapter from specification):
 * - support for escaping of special characters in format string [part of G.2]
 * - formatting numbers to scientific notation [G.4]
 * - support for significant digits [G.5]
 * - support for padding [G.6]
 * - formatting numbers in other number systems than "latn"
 * - currency symbol substitution is simplified
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @see http://www.unicode.org/reports/tr35/#Number_Elements
 * @see http://www.unicode.org/reports/tr35/#Number_Format_Patterns
 */
class NumbersReader {

	/**
	 * An expression to catch one subformat. One format string can have
	 * one or two subformats (positive and negative, separated by semicolon).
	 */
	const PATTERN_MATCH_SUBFORMAT = '/^(.*?)[0-9#\.,]+(.*?)$/';

	/**
	 * An expression to catch float or decimal number embedded in the format
	 * string, which sets a rounding used during formatting. For example, when
	 * format string looks like '#,##0.05', it means that formatted number
	 * should be rounded to the nearest 0.05.
	 */
	const PATTERN_MATCH_ROUNDING = '/([0-9]+(?:\.[0-9]+)?)/';

	/**
	 * @var \F3\FLOW3\Locale\CLDR\CLDRRepository
	 */
	protected $CLDRRepository;

	/**
	 * @var \F3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * An array of parsed formats, indexed by format strings.
	 *
	 * Example of data stored in this array (default values):
	 * '#,##0.###' => array(
	 *     'positivePrefix' => '',
	 *     'positiveSuffix' => '',
	 *     'negativePrefix' => '-',
	 *     'negativeSuffix' => '',
	 *
	 *     'multiplier' => 1,
	 *
	 *     'minDecimalDigits' => 0,
	 *     'maxDecimalDigits' => 0,
	 *
	 *     'minIntegerDigits' => 1,
	 *
	 *     'primaryGroupingSize' => 0,
	 *     'secondaryGroupingSize' => 0,
	 *
	 *     'rounding' => 0,
	 * );
	 *
	 * Legend:
	 * - positivePrefix / positiveSuffix: a character to place before / after
	 *     the number, if it's positive.
	 * - negativePrefix / Suffix: same as above, but for negative numbers.
	 * - multiplier: Used for percents or permiles (100 and 1000 accordingly).
	 * - minDecimalDigits: same as above, but for decimal part of the number.
	 *     No less than 0 (which means no decimal part).
	 * - maxDecimalDigits: same as above, but for decimal part of the number.
	 *     No less than 0 (which means no decimal part).
	 * - minIntegerDigits: at least so many digits will be printed for integer
	 *     part of the number (padded with zeros if needed). No less than 1.
	 * - primaryGroupingSize: Where to put the first grouping separator (e.g.
	 *     thousands). Zero means no separator (also no secondary separator!).
	 * - secondaryGroupingSize: Where to put the second grouping separators (used
	 *     after the primary separator - eg for primaryGroupingSize set to 3 and
	 *     secondaryGroupingSize set to 2, number 123456789 will be 12,34,56,789).
	 *     For most languages, this is the same as primaryGroupingSize.
	 * - rounding: If set, number will be rounded to the multiple of this value.
	 *     Can be float or integer. Zero means no rounding.
	 *
	 * Note: there can be characters in prefix / suffix elements which will be
	 * localized during formatting (eg minus sign, percent etc), or other chars
	 * which will be used as-is.
	 *
	 * There can be FALSE assigned to any format string. This means that given
	 * string was parsed unsuccessfully (no more parsing attempts will be done
	 * for this format string until cache clears).
	 *
	 * @var array
	 */
	protected $parsedFormats;

	/**
	 * An array which stores references to formats used by particular locales.
	 *
	 * As for one locale there can be defined many formats (at most 3 format
	 * types supported by this class - decimal, percent, currency - multiplied by
	 * at most 5 format lengths - full, long, medium, short, and implicit length
	 * referred in this class as 'default'), references are organized in arrays.
	 *
	 * Example of data stored in this array:
	 * 'pl' => array(
	 *     'decimal' => array(
	 *         'default' => '#,##0.###',
	 *         ...
	 *     ),
	 *     ...
	 * );
	 *
	 * @var array
	 */
	protected $parsedFormatsIndices;

	/**
	 * Associative array of symbols used in particular locales.
	 *
	 * Locale tags are keys for this array. Values are arrays of symbols, as
	 * defined in /ldml/numbers/symbols path in CLDR files.
	 *
	 * @var array
	 */
	protected $localizedSymbols;

	/**
	 * @param \F3\FLOW3\Locale\CLDR\CLDRRepository $repository
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectCLDRRepository(\F3\FLOW3\Locale\CLDR\CLDRRepository $repository) {
		$this->CLDRRepository = $repository;
	}

	/**
	 * Injects the FLOW3_Locale_CDLR_Reader_NumbersReader cache
	 *
	 * @param \F3\FLOW3\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectCache(\F3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Constructs the reader, loading parsed data from cache if available.
	 *
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function initializeObject() {
		if ($this->cache->has('parsedFormats')) {
			$this->parsedFormats = $this->cache->get('parsedFormats');
		}

		if ($this->cache->has('parsedFormatsIndices')) {
			$this->parsedFormatsIndices = $this->cache->get('parsedFormatsIndices');
		}

		if ($this->cache->has('localizedSymbols')) {
			$this->localizedSymbols = $this->cache->get('localizedSymbols');
		}
	}

	/**
	 * Shutdowns the object, saving parsed format strings to the cache.
	 * 
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function shutdownObject() {
		$this->cache->set('parsedFormats', $this->parsedFormats);
		$this->cache->set('parsedFormatsIndices', $this->parsedFormatsIndices);
		$this->cache->set('localizedSymbols', $this->localizedSymbols);
	}

	/**
	 * Returns number formatted by custom format, string provided in parameter.
	 *
	 * Format must obey syntax defined in CLDR specification, excluding
	 * unimplemented features (see documentation for this class).
	 *
	 * Format is remembered in this classes cache and won't be parsed again for
	 * some time.
	 *
	 * @param mixed $number Float or int, can be negative, can be NaN or infinite
	 * @param string $format Format string
	 * @param \F3\FLOW3\Locale\Locale $locale A locale used for finding symbols array
	 * @return string Formatted number. Will return string-casted version of $number if pattern is not valid / supported
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatNumberWithCustomPattern($number, $format, \F3\FLOW3\Locale\Locale $locale) {
		if (isset($this->parsedFormats[$format])) {
			$parsedFormat = $this->parsedFormats[$format];
		} else {
			$this->parsedFormats[$format] = $this->parseFormat($format);
		}

		return $this->doFormattingWithParsedFormat($number, $this->parsedFormats[$format], $this->getLocalizedSymbolsForLocale($locale));
	}

	/**
	 * Formats number with format string for decimal numbers defined in CLDR for
	 * particular locale.
	 * 
	 * Note: currently length is not used in decimalFormats from CLDR.
	 * But it's defined in the specification, so we support it here.
	 *
	 * @param mixed $number Float or int, can be negative, can be NaN or infinite
	 * @param \F3\FLOW3\Locale\Locale $locale
	 * @param string $length One of: full, long, medium, short, or 'default' in order to not use $length parameter
	 * @return string Formatted number. Will return string-casted version of $number if there is no pattern for given $locale / $length
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatDecimalNumber($number, \F3\FLOW3\Locale\Locale $locale, $length = 'default') {
		return $this->doFormattingWithParsedFormat($number, $this->getParsedFormat($locale, 'decimal', $length), $this->getLocalizedSymbolsForLocale($locale));
	}

	/**
	 * Formats number with format string for percentage defined in CLDR for
	 * particular locale.
	 *
	 * Note: currently length is not used in percentFormats from CLDR.
	 * But it's defined in the specification, so we support it here.
	 *
	 * @param mixed $number Float or int, can be negative, can be NaN or infinite
	 * @param \F3\FLOW3\Locale\Locale $locale
	 * @param string $length One of: full, long, medium, short, or 'default' in order to not use $length parameter
	 * @return string Formatted number. Will return string-casted version of $number if there is no pattern for given $locale / $length
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatPercentNumber($number, \F3\FLOW3\Locale\Locale $locale, $length = 'default') {
		return $this->doFormattingWithParsedFormat($number, $this->getParsedFormat($locale, 'percent', $length), $this->getLocalizedSymbolsForLocale($locale));
	}

	/**
	 * Formats number with format string for currency defined in CLDR for
	 * particular locale.
	 *
	 * Currency symbol provided will be inserted into formatted number string.
	 *
	 * Note: currently length is not used in currencyFormats from CLDR.
	 * But it's defined in the specification, so we support it here.
	 *
	 * @param mixed $number Float or int, can be negative, can be NaN or infinite
	 * @param \F3\FLOW3\Locale\Locale $locale
	 * @param string $currency Currency symbol (or name)
	 * @param string $length One of: full, long, medium, short, or 'default' in order to not use $length parameter
	 * @return string Formatted number. Will return string-casted version of $number if there is no pattern for given $locale / $length
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatCurrencyNumber($number, \F3\FLOW3\Locale\Locale $locale, $currency, $length = 'default') {
		return $this->doFormattingWithParsedFormat($number, $this->getParsedFormat($locale, 'currency', $length), $this->getLocalizedSymbolsForLocale($locale), $currency);
	}

	/**
	 * Formats provided float or integer.
	 *
	 * Format rules defined in $parsedFormat array are used. Localizable symbols
	 * are replaced with elelements from $symbols array, and currency
	 * placeholder is replaced with the value of $currency, if not NULL.
	 *
	 * If $number is NaN or infite, proper localized symbol will be returned,
	 * as defined in CLDR specification.
	 *
	 * @param mixed $number Float or int, can be negative, can be NaN or infinite
	 * @param array $parsedFormat An array describing format (as in $parsedFormats property)
	 * @param array $symbols An array with symbols to use (as in $localeSymbols property)
	 * @param string $currency Currency symbol to be inserted into formatted number (if applicable)
	 * @return string Formatted number. Will return string-casted version of $number if pattern is FALSE
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function doFormattingWithParsedFormat($number, $parsedFormat, $symbols, $currency = NULL) {
		if ($parsedFormat === FALSE) {
			return (string)$number;
		}

		if (is_nan($number)) {
			return $symbols['nan'];
		}

		if (is_infinite($number)) {
			if ($number < 0) {
				return $parsedFormat['negativePrefix'] . $symbols['infinity'] . $parsedFormat['negativeSuffix'];
			} else {
				return $parsedFormat['positivePrefix'] . $symbols['infinity'] . $parsedFormat['positiveSuffix'];
			}
		}

		$isNegative = $number < 0;
		$number = abs($number * $parsedFormat['multiplier']);

		if ($parsedFormat['rounding'] > 0) {
			$number = round($number / $parsedFormat['rounding'], 0, \PHP_ROUND_HALF_EVEN) * $parsedFormat['rounding'];
		}

		if ($parsedFormat['maxDecimalDigits'] >= 0) {
			$number = round($number, $parsedFormat['maxDecimalDigits']);
		}

		$number = (string)$number;

		if (($positionOfDecimalSeparator = strpos($number, '.')) !== FALSE) {
			$integerPart = substr($number, 0, $positionOfDecimalSeparator);
			$decimalPart = substr($number, $positionOfDecimalSeparator + 1);
		} else {
			$integerPart = $number;
			$decimalPart = '';
		}

		if ($parsedFormat['minDecimalDigits'] > strlen($decimalPart)) {
			$decimalPart = str_pad($decimalPart, $parsedFormat['minDecimalDigits'], '0');
		}

		$integerPart = str_pad($integerPart, $parsedFormat['minIntegerDigits'], '0', STR_PAD_LEFT);

		if ($parsedFormat['primaryGroupingSize'] > 0 && strlen($integerPart) > $parsedFormat['primaryGroupingSize']) {
			$primaryGroupOfIntegerPart = substr($integerPart, - $parsedFormat['primaryGroupingSize']);
			$restOfIntegerPart = substr($integerPart, 0, - $parsedFormat['primaryGroupingSize']);

			// Pad the numbers with spaces from the left, so the length of the string is a multiply of secondaryGroupingSize (and str_split() can split on equal parts)
			$padLengthToGetEvenSize = (int)((strlen($restOfIntegerPart) + $parsedFormat['secondaryGroupingSize'] - 1) / $parsedFormat['secondaryGroupingSize']) * $parsedFormat['secondaryGroupingSize'];
			$restOfIntegerPart = str_pad($restOfIntegerPart, $padLengthToGetEvenSize, ' ', STR_PAD_LEFT);
			
			// Insert localized group separators between every secondary groups and primary group (using str_split() and implode())
			$secondaryGroupsOfIntegerPart = str_split($restOfIntegerPart, $parsedFormat['secondaryGroupingSize']);
			$integerPart = ltrim(implode($symbols['group'], $secondaryGroupsOfIntegerPart)) . $symbols['group'] . $primaryGroupOfIntegerPart;
		}

		if (strlen($decimalPart) > 0) {
			$decimalPart = $symbols['decimal'] . $decimalPart;
		}

		if ($isNegative) {
			$number = $parsedFormat['negativePrefix'] . $integerPart . $decimalPart . $parsedFormat['negativeSuffix'];
		} else {
			$number = $parsedFormat['positivePrefix'] . $integerPart . $decimalPart . $parsedFormat['positiveSuffix'];
		}

		$number = str_replace(array('%', '‰', '-'), array($symbols['percentSign'], $symbols['perMille'], $symbols['minusSign']), $number);
		if ($currency !== NULL) {
			/** @todo: When currency is set, min / max DecimalDigits and rounding is overrided with CLDR data **/
			$number = str_replace('¤', $currency, $number);
		}

		return $number;
	}

	/**
	 * Parses a number format (with syntax defined in CLDR).
	 *
	 * Not all features from CLDR specification are implemented. Please see the
	 * documentation for this class for details what is missing.
	 *
	 * @see documentation for $parsedFormats property for details about internal
	 * structure of parsed format.
	 *
	 * @param string $format
	 * @return mixed Parsed format (or FALSE when unsupported format string detected)
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function parseFormat($format) {
		foreach (array('E', '@', '*', '\'') as $unsupportedFeature) {
			if (strpos($format, $unsupportedFeature) !== FALSE) {
				return FALSE;
			}
		}

		$parsedFormat =  array(
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

		if (strpos($format, ';') !== FALSE) {
			list($positiveFormat, $negativeFormat) = explode(';', $format);
			$format = $positiveFormat;
		} else {
			$positiveFormat = $format;
			$negativeFormat = NULL;
		}

		if (preg_match(self::PATTERN_MATCH_SUBFORMAT, $positiveFormat, $matches)) {
			$parsedFormat['positivePrefix'] = $matches[1];
			$parsedFormat['positiveSuffix'] = $matches[2];
		}

		if ($negativeFormat !== NULL && preg_match(self::PATTERN_MATCH_SUBFORMAT, $negativeFormat, $matches)) {
			$parsedFormat['negativePrefix'] = $matches[1];
			$parsedFormat['negativeSuffix'] = $matches[2];
		} else {
			$parsedFormat['negativePrefix'] = '-' . $parsedFormat['positivePrefix'];
			$parsedFormat['negativeSuffix'] = $parsedFormat['positiveSuffix'];
		}

		if (strpos($format, '%') !== FALSE) {
			$parsedFormat['multiplier'] = 100;
		} else if(strpos($format, '‰') !== FALSE) {
			$parsedFormat['multiplier'] = 1000;
		}

		if (preg_match(self::PATTERN_MATCH_ROUNDING, $format, $matches)) {
			$parsedFormat['rounding'] = (float)$matches[1];
			$format = preg_replace('/[1-9]/', '0', $format);
		}

		if (($positionOfDecimalSeparator = strpos($format, '.')) !== FALSE) {
			if (($positionOfLastZero = strrpos($format, '0')) > $positionOfDecimalSeparator) {
				$parsedFormat['minDecimalDigits'] = $positionOfLastZero - $positionOfDecimalSeparator;
			}
			
			if (($positionOfLastHash = strrpos($format, '#')) >= $positionOfLastZero) {
				$parsedFormat['maxDecimalDigits'] = $positionOfLastHash - $positionOfDecimalSeparator;
			} else {
				$parsedFormat['maxDecimalDigits'] = $parsedFormat['minDecimalDigits'];
			}
			
			$format = substr($format, 0, $positionOfDecimalSeparator);
		}

		$formatWithoutGroupSeparators = str_replace(',', '', $format);
		if (($positionOfFirstZero = strpos($formatWithoutGroupSeparators, '0')) !== FALSE) {
			$parsedFormat['minIntegerDigits'] = strrpos($formatWithoutGroupSeparators, '0') - $positionOfFirstZero + 1;
		}

		$formatWithoutHashes = str_replace('#', '0', $format);
		if (($positionOfPrimaryGroupSeparator = strrpos($format, ',')) !== FALSE) {
			$parsedFormat['primaryGroupingSize'] = strrpos($formatWithoutHashes, '0') - $positionOfPrimaryGroupSeparator;

			if (($positionOfSecondaryGroupSeparator = strrpos(substr($formatWithoutHashes, 0, $positionOfPrimaryGroupSeparator), ',')) !== FALSE) {
				$parsedFormat['secondaryGroupingSize'] = $positionOfPrimaryGroupSeparator - $positionOfSecondaryGroupSeparator - 1;
			} else {
				$parsedFormat['secondaryGroupingSize'] = $parsedFormat['primaryGroupingSize'];
			}
		}

		return $parsedFormat;
	}

	/**
	 * Returns parsed number format basing on locale and desired format length
	 * if provided.
	 *
	 * When third parameter ($length) equals 'default', default format for a
	 * locale will be used.
	 *
	 * @param \F3\FLOW3\Locale\Locale $locale
	 * @param string $type A type of format (decimal, percent, currency)
	 * @param string $length A length of format (full, long, medium, short) or 'default' to use default one
	 * @return mixed An array representing parsed format or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function getParsedFormat(\F3\FLOW3\Locale\Locale $locale, $type, $length) {
		if (isset($this->parsedFormatsIndices[(string)$locale][$type][$length])) {
			return $this->parsedFormats[$this->parsedFormatsIndices[(string)$locale][$type][$length]];
		}

		if ($length === 'default') {
			$formatPath = 'numbers/' . $type . 'Formats/' . $type . 'FormatLength/' . $type . 'Format/pattern';
		} else {
			$formatPath = 'numbers/' . $type . 'Formats/' . $type . 'FormatLength/type="' . $length . '/' . $type . 'Format/pattern';
		}

		$model = $this->CLDRRepository->getHierarchicalModel('main', $locale);
		$format = $model->getOneElement($formatPath);

		if (empty($format)) {
			return FALSE;
		}

		$parsedFormat = $this->parseFormat($format);

		$this->parsedFormatsIndices[(string)$locale][$type][$length] = $format;
		return $this->parsedFormats[$format] = $parsedFormat;
	}

	/**
	 * Returns symbols array for provided locale.
	 *
	 * Symbols are elements defined in tag symbols from CLDR. They define
	 * localized versions of various number-related elements, like decimal
	 * separator, group separator or minus sign.
	 *
	 * Symbols arrays for every requested locale are cached.
	 *
	 * @param \F3\FLOW3\Locale\Locale $locale
	 * @return array Symbols array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getLocalizedSymbolsForLocale(\F3\FLOW3\Locale\Locale $locale) {
		if (isset($this->localizedSymbols[(string)$locale])) {
			return $this->localizedSymbols[(string)$locale];
		}

		$model = $this->CLDRRepository->getHierarchicalModel('main', $locale);
		return $this->localizedSymbols[(string)$locale] = $model->getRawArray('numbers/symbols');
	}
}

?>