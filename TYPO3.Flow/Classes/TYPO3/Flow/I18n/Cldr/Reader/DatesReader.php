<?php
namespace TYPO3\Flow\I18n\Cldr\Reader;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A reader for data placed in "dates" tag in CLDR.
 *
 * This is not full implementation of features from CLDR. These are missing:
 * - support for other calendars than Gregorian
 * - some data from "dates" tag is not used (fields, timeZoneNames)
 *
 * @Flow\Scope("singleton")
 * @see http://www.unicode.org/reports/tr35/#Date_Elements
 * @see http://www.unicode.org/reports/tr35/#Date_Format_Patterns
 */
class DatesReader {

	/**
	 * Constants for available format types.
	 */
	const FORMAT_TYPE_DATE = 'date';
	const FORMAT_TYPE_TIME = 'time';
	const FORMAT_TYPE_DATETIME = 'dateTime';

	/**
	 * Constants for available format lengths.
	 */
	const FORMAT_LENGTH_DEFAULT = 'default';
	const FORMAT_LENGTH_FULL = 'full';
	const FORMAT_LENGTH_LONG = 'long';
	const FORMAT_LENGTH_MEDIUM = 'medium';
	const FORMAT_LENGTH_SHORT = 'short';

	/**
	 * @var \TYPO3\Flow\I18n\Cldr\CldrRepository
	 */
	protected $cldrRepository;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * Static array of date / time formatters supported by this class, and
	 * maximal lengths particular formats can get.
	 *
	 * For example, era (G) can be defined in three formats, abbreviated (G, GG,
	 * GGG), wide (GGGG), or narrow (GGGGG), so maximal length is set to 5.
	 *
	 * When length is set to zero, it means that corresponding format has no
	 * maximal length.
	 *
	 * @var array
	 */
	static protected $maxLengthOfSubformats = array(
		'G' => 5,
		'y' => 0,
		'Y' => 0,
		'u' => 0,
		'Q' => 4,
		'q' => 4,
		'M' => 5,
		'L' => 5,
		'l' => 1,
		'w' => 2,
		'W' => 1,
		'd' => 2,
		'D' => 3,
		'F' => 1,
		'g' => 0,
		'E' => 5,
		'e' => 5,
		'c' => 5,
		'a' => 1,
		'h' => 2,
		'H' => 2,
		'K' => 2,
		'k' => 2,
		'j' => 2,
		'm' => 2,
		's' => 2,
		'S' => 0,
		'A' => 0,
		'z' => 4,
		'Z' => 4,
		'v' => 4,
		'V' => 4,
	);

	/**
	 * An array of parsed formats, indexed by format strings.
	 *
	 * Example of data stored in this array:
	 * 'HH:mm:ss zzz' => array(
	 *   'HH',
	 *   array(':'),
	 *   'mm',
	 *   array(':'),
	 *   'ss',
	 *   array(' '),
	 *   'zzz',
	 * );
	 *
	 * Please note that subformats are stored as array elements, and literals
	 * are stored as one-element arrays in the same array. Order of elements
	 * in array is important.
	 *
	 * @var array
	 */
	protected $parsedFormats;

	/**
	 * An array which stores references to formats used by particular locales.
	 *
	 * As for one locale there can be defined many formats (at most 2 format
	 * types supported by this class - date, time - multiplied by at most 4
	 * format lengths - full, long, medium, short), references are organized in
	 * arrays.
	 *
	 * Example of data stored in this array:
	 * 'pl' => array(
	 *     'date' => array(
	 *         'full' => 'EEEE, d MMMM y',
	 *         ...
	 *     ),
	 *     ...
	 * );
	 *
	 * @var array
	 */
	protected $parsedFormatsIndices;

	/**
	 * Associative array of literals used in particular locales.
	 *
	 * Locale tags are keys for this array. Values are arrays of literals, i.e.
	 * names defined in months, days, quarters etc tags.
	 *
	 * @var array
	 */
	protected $localizedLiterals;

	/**
	 * @param \TYPO3\Flow\I18n\Cldr\CldrRepository $repository
	 * @return void
	 */
	public function injectCldrRepository(\TYPO3\Flow\I18n\Cldr\CldrRepository $repository) {
		$this->cldrRepository = $repository;
	}

	/**
	 * Injects the Flow_I18n_Cldr_Reader_DatesReader cache
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 */
	public function injectCache(\TYPO3\Flow\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Constructs the reader, loading parsed data from cache if available.
	 *
	 * @return void
	 */
	public function initializeObject() {
		if ($this->cache->has('parsedFormats') && $this->cache->has('parsedFormatsIndices') && $this->cache->has('localizedLiterals')) {
			$this->parsedFormats = $this->cache->get('parsedFormats');
			$this->parsedFormatsIndices = $this->cache->get('parsedFormatsIndices');
			$this->localizedLiterals = $this->cache->get('localizedLiterals');
		}
	}

	/**
	 * Shutdowns the object, saving parsed format strings to the cache.
	 *
	 * @return void
	 */
	public function shutdownObject() {
		$this->cache->set('parsedFormats', $this->parsedFormats);
		$this->cache->set('parsedFormatsIndices', $this->parsedFormatsIndices);
		$this->cache->set('localizedLiterals', $this->localizedLiterals);
	}

	/**
	 * Returns parsed date or time format basing on locale and desired format
	 * length.
	 *
	 * When third parameter ($formatLength) equals 'default', default format for a
	 * locale will be used.
	 *
	 * @param \TYPO3\Flow\I18n\Locale $locale
	 * @param string $formatType A type of format (one of constant values)
	 * @param string $formatLength A length of format (one of constant values)
	 * @return array An array representing parsed format
	 * @throws \TYPO3\Flow\I18n\Cldr\Reader\Exception\UnableToFindFormatException When there is no proper format string in CLDR
	 * @todo make default format reading nicer
	 */
	public function parseFormatFromCldr(\TYPO3\Flow\I18n\Locale $locale, $formatType, $formatLength) {
		self::validateFormatType($formatType);
		self::validateFormatLength($formatLength);

		if (isset($this->parsedFormatsIndices[(string)$locale][$formatType][$formatLength])) {
			return $this->parsedFormats[$this->parsedFormatsIndices[(string)$locale][$formatType][$formatLength]];
		}

		$model = $this->cldrRepository->getModelForLocale($locale);

		if ($formatLength === 'default') {
				// the default thing only has an attribute. ugly fetch code. was a nice three-liner before 2011-11-21
			$formats = $model->getRawArray('dates/calendars/calendar[@type="gregorian"]/' . $formatType . 'Formats');
			foreach (array_keys($formats) as $k) {
				$realFormatLength = \TYPO3\Flow\I18n\Cldr\CldrModel::getAttributeValue($k, 'choice');
				if ($realFormatLength !== FALSE) {
					break;
				}
			}
		} else {
			$realFormatLength = $formatLength;
		}

		$format = $model->getElement('dates/calendars/calendar[@type="gregorian"]/' . $formatType . 'Formats/' . $formatType . 'FormatLength[@type="' . $realFormatLength . '"]/' . $formatType . 'Format/pattern');

		if (empty($format)) {
			throw new \TYPO3\Flow\I18n\Cldr\Reader\Exception\UnableToFindFormatException('Date / time format was not found. Please check whether CLDR repository is valid.', 1280218994);
		}

		if ($formatType === 'dateTime') {
				// DateTime is a simple format like this: '{0} {1}' which denotes where to insert date and time
			$parsedFormat = $this->prepareDateAndTimeFormat($format, $locale, $formatLength);
		} else {
			$parsedFormat = $this->parseFormat($format);
		}

		$this->parsedFormatsIndices[(string)$locale][$formatType][$formatLength] = $format;
		return $this->parsedFormats[$format] = $parsedFormat;
	}

	/**
	 * Returns parsed date or time format string provided as parameter.
	 *
	 * @param string $format Format string to parse
	 * @return array An array representing parsed format
	 */
	public function parseCustomFormat($format) {
		if (isset($this->parsedFormats[$format])) {
			return $this->parsedFormats[$format];
		}

		return $this->parsedFormats[$format] = $this->parseFormat($format);
	}

	/**
	 * Returns literals array for locale provided.
	 *
	 * If array was not generated earlier, it will be generated and cached.
	 *
	 * @param \TYPO3\Flow\I18n\Locale $locale
	 * @return array An array with localized literals
	 */
	public function getLocalizedLiteralsForLocale(\TYPO3\Flow\I18n\Locale $locale) {
		if (isset($this->localizedLiterals[(string)$locale])) {
			return $this->localizedLiterals[(string)$locale];
		}

		$model = $this->cldrRepository->getModelForLocale($locale);

		$localizedLiterals['months'] = $this->parseLocalizedLiterals($model, 'month');
		$localizedLiterals['days'] = $this->parseLocalizedLiterals($model, 'day');
		$localizedLiterals['quarters'] = $this->parseLocalizedLiterals($model, 'quarter');
		$localizedLiterals['dayPeriods'] = $this->parseLocalizedLiterals($model, 'dayPeriod');
		$localizedLiterals['eras'] = $this->parseLocalizedEras($model);

		return $this->localizedLiterals[(string)$locale] = $localizedLiterals;
	}

	/**
	 * Validates provided format type and throws exception if value is not
	 * allowed.
	 *
	 * @param string $formatType
	 * @return void
	 * @throws \TYPO3\Flow\I18n\Cldr\Reader\Exception\InvalidFormatTypeException When value is unallowed
	 */
	static public function validateFormatType($formatType) {
		if (!in_array($formatType, array(self::FORMAT_TYPE_DATE, self::FORMAT_TYPE_TIME, self::FORMAT_TYPE_DATETIME))) {
			throw new \TYPO3\Flow\I18n\Cldr\Reader\Exception\InvalidFormatTypeException('Provided formatType, "' . $formatType . '", is not one of allowed values.', 1281442590);
		}
	}

	/**
	 * Validates provided format length and throws exception if value is not
	 * allowed.
	 *
	 * @param string $formatLength
	 * @return void
	 * @throws \TYPO3\Flow\I18n\Cldr\Reader\Exception\InvalidFormatLengthException When value is unallowed
	 */
	static public function validateFormatLength($formatLength) {
		if (!in_array($formatLength, array(self::FORMAT_LENGTH_DEFAULT, self::FORMAT_LENGTH_FULL, self::FORMAT_LENGTH_LONG, self::FORMAT_LENGTH_MEDIUM, self::FORMAT_LENGTH_SHORT))) {
			throw new \TYPO3\Flow\I18n\Cldr\Reader\Exception\InvalidFormatLengthException('Provided formatLength, "' . $formatLength . '", is not one of allowed values.', 1281442591);
		}
	}

	/**
	 * Parses a date / time format (with syntax defined in CLDR).
	 *
	 * Not all features from CLDR specification are implemented. Please see the
	 * documentation for this class for details what is missing.
	 *
	 * @param string $format
	 * @return array Parsed format
	 * @throws \TYPO3\Flow\I18n\Cldr\Reader\Exception\InvalidDateTimeFormatException When subformat is longer than maximal value defined in $maxLengthOfSubformats property
	 * @see \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::$parsedFormats
	 */
	protected function parseFormat($format) {
		$parsedFormat = array();
		$formatLengthOfFormat = strlen($format);
		$duringCompletionOfLiteral = FALSE;
		$literal = '';

		for ($i = 0; $i < $formatLengthOfFormat; ++$i) {
			$subformatSymbol = $format[$i];

			if ($subformatSymbol === '\'') {
				if ($i < $formatLengthOfFormat - 1 && $format[$i + 1] === '\'') {
						// Two apostrophes means that one apostrophe is escaped
					if ($duringCompletionOfLiteral) {
							// We are already reading some literal, save it and continue
						$parsedFormat[] = array($literal);
						$literal = '';
					}

					$parsedFormat[] = array('\'');
					++$i;
				} elseif ($duringCompletionOfLiteral) {
					$parsedFormat[] = array($literal);
					$literal = '';
					$duringCompletionOfLiteral = FALSE;
				} else {
					$duringCompletionOfLiteral = TRUE;
				}
			} elseif ($duringCompletionOfLiteral) {
				$literal .= $subformatSymbol;
			} else {
					// Count the length of subformat
				for ($j = $i + 1; $j < $formatLengthOfFormat; ++$j) {
					if ($format[$j] !== $subformatSymbol) {
						break;
					}
				}

				$subformat = str_repeat($subformatSymbol, $j - $i);

				if (isset(self::$maxLengthOfSubformats[$subformatSymbol])) {
					if (self::$maxLengthOfSubformats[$subformatSymbol] === 0 || strlen($subformat) <= self::$maxLengthOfSubformats[$subformatSymbol]) {
						$parsedFormat[] = $subformat;
					} else {
						throw new \TYPO3\Flow\I18n\Cldr\Reader\Exception\InvalidDateTimeFormatException('Date / time pattern is too long: ' . $subformat . ', specification allows up to ' . self::$maxLengthOfSubformats[$subformatSymbol] . ' chars.', 1276114248);
					}
				} else {
					$parsedFormat[] = array($subformat);
				}

				$i = $j - 1;
			}
		}

		if ($literal !== '') {
			$parsedFormat[] = array($literal);
		}

		return $parsedFormat;
	}

	/**
	 * Parses one CLDR child of "dates" node and returns it's array representation.
	 *
	 * Many children of "dates" node have common structure, so one method can
	 * be used to parse them all.
	 *
	 * @param \TYPO3\Flow\I18n\Cldr\CldrModel $model CldrModel to read data from
	 * @param string $literalType One of: month, day, quarter, dayPeriod
	 * @return array An array with localized literals for given type
	 * @todo the two array checks should go away - but that needs clean input data
	 */
	protected function parseLocalizedLiterals(\TYPO3\Flow\I18n\Cldr\CldrModel $model, $literalType) {
		$data = array();
		$context = $model->getRawArray('dates/calendars/calendar[@type="gregorian"]/' . $literalType . 's');

		foreach ($context as $contextNodeString => $literalsWidths) {
			$contextType = $model->getAttributeValue($contextNodeString, 'type');
			if (!is_array($literalsWidths)) {
				continue;
			}
			foreach ($literalsWidths as $widthNodeString => $literals) {
				$widthType = $model->getAttributeValue($widthNodeString, 'type');
				if (!is_array($literals)) {
					continue;
				}
				foreach ($literals as $literalNodeString => $literalValue) {
					$literalName = $model->getAttributeValue($literalNodeString, 'type');

					$data[$contextType][$widthType][$literalName] = $literalValue;
				}
			}
		}

		return $data;
	}

	/**
	 * Parses "eras" child of "dates" node and returns it's array representation.
	 *
	 * @param \TYPO3\Flow\I18n\Cldr\CldrModel $model CldrModel to read data from
	 * @return array An array with localized literals for "eras" node
	 */
	protected function parseLocalizedEras(\TYPO3\Flow\I18n\Cldr\CldrModel $model) {
		$data = array();
		foreach ($model->getRawArray('dates/calendars/calendar[@type="gregorian"]/eras') as $widthType => $eras) {
			foreach ($eras as $eraNodeString => $eraValue) {
				$eraName = $model->getAttributeValue($eraNodeString, 'type');

				$data[$widthType][$eraName] = $eraValue;
			}
		}

		return $data;
	}

	/**
	 * Creates one parsed datetime format from date and time formats merged
	 * together.
	 *
	 * The dateTime format from CLDR looks like "{0} {1}" and denotes where to
	 * place time and date, and what literals should be placed before, between
	 * and / or after them.
	 *
	 * @param string $format DateTime format
	 * @param \TYPO3\Flow\I18n\Locale $locale Locale to use
	 * @param string $formatLength A length of format (full, long, medium, short) or 'default' to use default one from CLDR
	 * @return array Merged formats of date and time
	 */
	protected function prepareDateAndTimeFormat($format, \TYPO3\Flow\I18n\Locale $locale, $formatLength) {
		$parsedFormatForDate = $this->parseFormatFromCldr($locale, 'date', $formatLength);
		$parsedFormatForTime = $this->parseFormatFromCldr($locale, 'time', $formatLength);

		$positionOfTimePlaceholder = strpos($format, '{0}');
		$positionOfDatePlaceholder = strpos($format, '{1}');

		if ($positionOfTimePlaceholder < $positionOfDatePlaceholder) {
			$positionOfFirstPlaceholder = $positionOfTimePlaceholder;
			$positionOfSecondPlaceholder = $positionOfDatePlaceholder;
			$firstParsedFormat = $parsedFormatForTime;
			$secondParsedFormat = $parsedFormatForDate;
		} else {
			$positionOfFirstPlaceholder = $positionOfDatePlaceholder;
			$positionOfSecondPlaceholder = $positionOfTimePlaceholder;
			$firstParsedFormat = $parsedFormatForDate;
			$secondParsedFormat = $parsedFormatForTime;
		}

		$parsedFormat = array();

		if ($positionOfFirstPlaceholder !== 0) {
				// Add everything before placeholder as literal
			$parsedFormat[] = array(substr($format, 0, $positionOfFirstPlaceholder));
		}

		$parsedFormat = array_merge($parsedFormat, $firstParsedFormat);

		if ($positionOfSecondPlaceholder - $positionOfFirstPlaceholder > 3) {
				// There is something between the placeholders
			$parsedFormat[] = array(substr($format, $positionOfFirstPlaceholder + 3, $positionOfSecondPlaceholder - ($positionOfFirstPlaceholder + 3)));
		}

		$parsedFormat = array_merge($parsedFormat, $secondParsedFormat);

		if ($positionOfSecondPlaceholder !== strlen($format) - 1) {
				// Add everything before placeholder as literal
			$parsedFormat[] = array(substr($format, $positionOfSecondPlaceholder + 3));
		}

		return $parsedFormat;
	}
}
