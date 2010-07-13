<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n\Cldr\Reader;

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
 * A reader for data placed in "dates" tag in CLDR.
 *
 * This is not full implementation of features from CLDR. These are missing:
 * - support for other calendars than Gregorian
 * - rules for displaying timezone names are simplified
 * - some data from "dates" tag is not used (fields, timeZoneNames)
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @see http://www.unicode.org/reports/tr35/#Date_Elements
 * @see http://www.unicode.org/reports/tr35/#Date_Format_Patterns
 */
class DatesReader {

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
	 * @var \F3\FLOW3\I18n\Cldr\CldrRepository
	 */
	protected $cldrRepository;

	/**
	 * @var \F3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

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
	 * @param \F3\FLOW3\I18n\Cldr\CldrRepository $repository
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectCldrRepository(\F3\FLOW3\I18n\Cldr\CldrRepository $repository) {
		$this->cldrRepository = $repository;
	}

	/**
	 * Injects the FLOW3_I18n_Cldr_Reader_DatesReader cache
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

		if ($this->cache->has('localizedLiterals')) {
			$this->localizedLiterals = $this->cache->get('localizedLiterals');
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
		$this->cache->set('localizedLiterals', $this->localizedLiterals);
	}

	/**
	 * Returns dateTime formatted by custom format, string provided in parameter.
	 *
	 * Format must obey syntax defined in CLDR specification, excluding
	 * unimplemented features (see documentation for this class).
	 *
	 * Format is remembered in this classes cache and won't be parsed again for
	 * some time.
	 *
	 * @param \DateTime $dateTime PHP object representing particular point in time
	 * @param string $format Format string
	 * @param \F3\FLOW3\I18n\Locale $locale A locale used for finding literals array
	 * @return string Formatted date / time. Unimplemented subformats in format string will be silently ignored
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatDateTimeWithCustomPattern(\DateTime $dateTime, $format, \F3\FLOW3\I18n\Locale $locale) {
		if (isset($this->parsedFormats[$format])) {
			$parsedFormat = $this->parsedFormats[$format];
		} else {
			$this->parsedFormats[$format] = $this->parseFormat($format);
		}

		return $this->doFormattingWithParsedFormat($dateTime, $this->parsedFormats[$format], $this->getLocalizedLiteralsForLocale($locale));
	}

	/**
	 * Formats date with format string for date defined in CLDR for particular
	 * locale.
	 *
	 * @param \DateTime $dateTime PHP object representing particular point in time
	 * @param \F3\FLOW3\I18n\Locale $locale
	 * @param string $length One of: full, long, medium, short, or 'default' in order to use default length from CLDR
	 * @return string Formatted date
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatDate(\DateTime $date, \F3\FLOW3\I18n\Locale $locale, $length = 'default') {
		return $this->doFormattingWithParsedFormat($date, $this->getParsedFormat($locale, 'date', $length), $this->getLocalizedLiteralsForLocale($locale));
	}

	/**
	 * Formats time with format string for time defined in CLDR for particular
	 * locale.
	 *
	 * @param \DateTime $dateTime PHP object representing particular point in time
	 * @param \F3\FLOW3\I18n\Locale $locale
	 * @param string $length One of: full, long, medium, short, or 'default' in order to use default length from CLDR
	 * @return string Formatted time
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatTime(\DateTime $time, \F3\FLOW3\I18n\Locale $locale, $length = 'default') {
		return $this->doFormattingWithParsedFormat($time, $this->getParsedFormat($locale, 'time', $length), $this->getLocalizedLiteralsForLocale($locale));
	}

	/**
	 * Formats dateTime with format string for date and time defined in CLDR for
	 * particular locale.
	 *
	 * First date and time are formatted separately, and then dateTime format
	 * from CLDR is used to place date and time in correct order.
	 *
	 * @param \DateTime $dateTime PHP object representing particular point in time
	 * @param \F3\FLOW3\I18n\Locale $locale
	 * @param string $length One of: full, long, medium, short, or 'default' in order to use default length from CLDR
	 * @return string Formatted date and time
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function formatDateTime(\DateTime $dateTime, \F3\FLOW3\I18n\Locale $locale, $length = 'default') {
		$formattedDate = $this->formatDate($dateTime, $locale, $length);
		$formattedTime = $this->formatTime($dateTime, $locale, $length);

		$format = $this->getParsedFormat($locale, 'dateTime', $length);

		return str_replace(array('{0}', '{1}'), array($formattedTime, $formattedDate), $format);
	}

	/**
	 * Formats provided dateTime object.
	 *
	 * Format rules defined in $parsedFormat array are used. Localizable literals
	 * are replaced with elelements from $localizedLiterals array.
	 *
	 * @param \DateTime $dateTime PHP object representing particular point in time
	 * @param array $parsedFormat An array describing format (as in $parsedFormats property)
	 * @param array $localizedLiterals An array with literals to use (as in $localizedLiterals property)
	 * @return string Formatted date / time
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function doFormattingWithParsedFormat(\DateTime $dateTime, array $parsedFormat, array $localizedLiterals) {
		$formattedDateTime = '';

		foreach ($parsedFormat as $subformat) {
			if (is_array($subformat)) {
					// This is just a simple string we use literally
				$formattedDateTime .= $subformat[0];
			} else {
				$formattedDateTime .= $this->doFormattingForSubpattern($dateTime, $subformat, $localizedLiterals);
			}
		}

		return $formattedDateTime;
	}

	/**
	 * Formats date or time element according to the subpattern provided.
	 * 
	 * Returns a string with formatted one "part" of DateTime object (seconds,
	 * day, month etc).
	 *
	 * Not all pattern symbols defined in CLDR are supported; some of the rules
	 * are simplified. Please see the documentation for this class for details.
	 *
	 * Cases in the code are ordered in such way that probably mostly used are
	 * on the top (but they are also grouped by similarity).
	 *
	 * @param \DateTime $dateTime PHP object representing particular point in time
	 * @param string $subformat One element of format string (e.g., 'yyyy', 'mm', etc)
	 * @return string Formatted part of date / time
	 * @throws \F3\FLOW3\I18n\Exception\InvalidArgumentException When $subformat use symbol that is not recognized
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function doFormattingForSubpattern(\DateTime $dateTime, $subformat, array $localizedLiterals) {
		$lengthOfSubformat = strlen($subformat);

		switch ($subformat[0]) {
			case 'h':
				return $this->padString($dateTime->format('g'), $lengthOfSubformat);
			case 'H':
				return $this->padString($dateTime->format('G'), $lengthOfSubformat);
			case 'K':
				$hour = (int)($dateTime->format('g'));
				if ($hour === 12) $hour = 0;
				return $this->padString($hour, $lengthOfSubformat);
			case 'k':
				$hour = (int)($dateTime->format('G'));
				if ($hour === 0) $hour = 24;
				return $this->padString($hour, $lengthOfSubformat);
			case 'a':
				return $localizedLiterals['dayPeriods']['format']['wide'][$dateTime->format('a')];
			case 'm':
				return $this->padString((int)($dateTime->format('i')), $lengthOfSubformat);
			case 's':
				return $this->padString((int)($dateTime->format('s')), $lengthOfSubformat);
			case 'S':
				return (string)round($dateTime->format('u'), $lengthOfSubformat);
			case 'd':
				return $this->padString($dateTime->format('j'), $lengthOfSubformat);
			case 'D':
				return $this->padString((int)($dateTime->format('z') + 1), $lengthOfSubformat);
			case 'F':
				return (int)(($dateTime->format('j') + 6) / 7);
			case 'M':
			case 'L':
				$month = (int)$dateTime->format('n');
				$type = ($subformat[0] === 'L') ? 'stand-alone' : 'format';
				if ($lengthOfSubformat <= 2) {
					return $this->padString($month, $lengthOfSubformat);
				} else if ($lengthOfSubformat === 3) {
					return $localizedLiterals['months'][$type]['abbreviated'][$month];
				} else if ($lengthOfSubformat === 4) {
					return $localizedLiterals['months'][$type]['wide'][$month];
				} else {
					return $localizedLiterals['months'][$type]['narrow'][$month];
				}
			case 'y':
				$year = (int)$dateTime->format('Y');
				if ($lengthOfSubformat === 2) $year %= 100;
				return $this->padString($year, $lengthOfSubformat);
			case 'E':
				$day = strtolower($dateTime->format('D'));
				if ($lengthOfSubformat <= 3) {
					return $localizedLiterals['days']['format']['abbreviated'][$day];
				} else if ($lengthOfSubformat === 4) {
					return $localizedLiterals['days']['format']['wide'][$day];
				} else {
					return $localizedLiterals['days']['format']['narrow'][$day];
				}
			case 'w':
				return $this->padString($dateTime->format('W'), $lengthOfSubformat);
			case 'W':
				return (string)((((int)$dateTime->format('W') - 1) % 4) + 1);
			case 'Q':
			case 'q':
				$quarter = (int)($dateTime->format('n') / 3.1) + 1;
				$type = ($subformat[0] === 'q') ? 'stand-alone' : 'format';
				if ($lengthOfSubformat <= 2) {
					return $this->padString($quarter, $lengthOfSubformat);
				} else if ($lengthOfSubformat === 3) {
					return $localizedLiterals['quarters'][$type]['abbreviated'][$quarter];
				} else {
					return $localizedLiterals['quarters'][$type]['wide'][$quarter];
				}
			case 'G':
				$era = (int)($dateTime->format('Y') > 0);
				if ($lengthOfSubformat <= 3) {
					return $localizedLiterals['eras']['eraAbbr'][$era];
				} else if ($lengthOfSubformat === 4) {
					return $localizedLiterals['eras']['eraNames'][$era];
				} else {
					return $localizedLiterals['eras']['eraNarrow'][$era];
				}
			case 'v':
			case 'z':
				if ($lengthOfSubformat <= 3) {
					return $dateTime->format('T');
				} else {
					return $dateTime->format('e');
				}
			case 'Y':
			case 'u':
			case 'l':
			case 'g':
			case 'e':
			case 'c':
			case 'A':
			case 'Z':
			case 'V':
					// Silently ignore unsupported formats
				return '';
			default:
				throw new \F3\FLOW3\I18n\Exception\InvalidArgumentException('Unexpected format symbol, "' . $subformat[0] . '" detected for date / time formatting.', 1276106678);
		}
	}

	/**
	 * Parses a date / time format (with syntax defined in CLDR).
	 *
	 * Not all features from CLDR specification are implemented. Please see the
	 * documentation for this class for details what is missing.
	 *
	 * @param string $format
	 * @return string Parsed format
	 * @throws \F3\FLOW3\I18n\Cldr\Reader\Exception\InvalidDateTimeFormatException When subformat is longer than maximal value defined in $maxLengthOfSubformats property
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @see \F3\FLOW3\I18n\Cldr\Reader\DatesReader::$parsedFormats
	 */
	protected function parseFormat($format) {
		$parsedFormat = array();
		$lengthOfFormat = strlen($format);
		$duringCompletionOfLiteral = FALSE;
		$literal = '';

		for ($i = 0; $i < $lengthOfFormat; ++$i) {
			$subformatSymbol = $format[$i];

			if ($subformatSymbol === '\'') {
				if ($i < $lengthOfFormat - 1 && $format[$i + 1] === '\'') {
						// Two apostrophes means that one apostrophe is escaped
					if ($duringCompletionOfLiteral) {
							// We are already reading some literal, save it and continue
						$parsedFormat[] = array($literal);
						$literal = '';
					}

					$parsedFormat[] = array('\'');
					++$i;
				} else if ($duringCompletionOfLiteral) {
					$parsedFormat[] = array($literal);
					$literal = '';
					$duringCompletionOfLiteral = FALSE;
				} else {
					$duringCompletionOfLiteral = TRUE;
				}
			} else if ($duringCompletionOfLiteral) {
				$literal .= $subformatSymbol;
			} else {
					// Count the length of subformat
				for ($j = $i + 1; $j < $lengthOfFormat; ++$j) {
					if($format[$j] !== $subformatSymbol) break;
				}
				
				$subformat = str_repeat($subformatSymbol, $j - $i);

				if (isset(self::$maxLengthOfSubformats[$subformatSymbol])) {
					if (self::$maxLengthOfSubformats[$subformatSymbol] === 0 || strlen($subformat) <= self::$maxLengthOfSubformats[$subformatSymbol]) {
						$parsedFormat[] = $subformat;
					} else throw new \F3\FLOW3\I18n\Cldr\Reader\Exception\InvalidDateTimeFormatException('Date / time pattern is too long: ' . $subformat . ', specification allows up to ' . self::$maxLengthOfSubformats[$subformatSymbol] . ' chars.', 1276114248);
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
	 * Returns parsed date or time format basing on locale and desired format
	 * length.
	 *
	 * When third parameter ($length) equals 'default', default format for a
	 * locale will be used.
	 *
	 * @param \F3\FLOW3\I18n\Locale $locale
	 * @param string $type A type of format (date, time)
	 * @param string $length A length of format (full, long, medium, short) or 'default' to use default one from CLDR
	 * @return mixed An array representing parsed format or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function getParsedFormat(\F3\FLOW3\I18n\Locale $locale, $type, $length) {
		if (isset($this->parsedFormatsIndices[(string)$locale][$type][$length])) {
			return $this->parsedFormats[$this->parsedFormatsIndices[(string)$locale][$type][$length]];
		}

		$model = $this->cldrRepository->getModelCollection('main', $locale);

		if ($length === 'default') {
			$defaultChoice = $model->getRawArray('dates/calendars/calendar/type="gregorian"/' . $type . 'Formats/default');
			$defaultChoice = array_keys($defaultChoice);
			$length = \F3\FLOW3\I18n\Cldr\CldrParser::getValueOfAttributeByName($defaultChoice[0], 'choice');
		}

		$format = $model->getElement('dates/calendars/calendar/type="gregorian"/' . $type . 'Formats/' . $type . 'FormatLength/type="' . $length . '"/' . $type . 'Format/pattern');

		if (empty($format)) {
			return FALSE;
		}

		if ($type === 'dateTime') {
				// DateTime is a simple format like this: '{0} {1}' which denotes where to insert date and time, it needs not to be parsed
			$parsedFormat = $format;
		} else {
			$parsedFormat = $this->parseFormat($format);
		}

		$this->parsedFormatsIndices[(string)$locale][$type][$length] = $format;
		return $this->parsedFormats[$format] = $parsedFormat;
	}

	/**
	 * Returns literals array for locale provided.
	 *
	 * If array was not generated earlier, it will be generated and cached.
	 *
	 * @param \F3\FLOW3\I18n\Locale $locale
	 * @return array An array with localized literals
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function getLocalizedLiteralsForLocale(\F3\FLOW3\I18n\Locale $locale) {
		if (isset($this->localizedLiterals[(string)$locale])) {
			return $this->localizedLiterals[(string)$locale];
		}

		$model = $this->cldrRepository->getModelCollection('main', $locale);

		$localizedLiterals['months'] = $this->parseLocalizedLiterals($model, 'month');
		$localizedLiterals['days'] = $this->parseLocalizedLiterals($model, 'day');
		$localizedLiterals['quarters'] = $this->parseLocalizedLiterals($model, 'quarter');
		$localizedLiterals['dayPeriods'] = $this->parseLocalizedLiterals($model, 'dayPeriod');
		$localizedLiterals['eras'] = $this->parseLocalizedEras($model);

		return $this->localizedLiterals[(string)$locale] = $localizedLiterals;
	}

	/**
	 * Parses one CLDR child of "dates" node and returns it's array representation.
	 *
	 * Many children of "dates" node have common structure, so one method can
	 * be used to parse them all.
	 *
	 * @param \F3\FLOW3\I18n\Cldr\CldrModelCollection $model CldrModelCollection to read data from
	 * @param string $literalType One of: month, day, quarter, dayPeriod
	 * @return array An array with localized literals for given type
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function parseLocalizedLiterals(\F3\FLOW3\I18n\Cldr\CldrModelCollection $model, $literalType) {
		$data = array();
		$context = $model->getRawArray('dates/calendars/calendar/type="gregorian"/' . $literalType . 's/' . $literalType . 'Context');

		foreach ($context as $contextType => $literalsWidths) {
			$contextType = \F3\FLOW3\I18n\Cldr\CldrParser::getValueOfAttributeByName($contextType, 'type');

			foreach ($literalsWidths[$literalType . 'Width'] as $widthType => $literals) {
				$widthType = \F3\FLOW3\I18n\Cldr\CldrParser::getValueOfAttributeByName($widthType, 'type');

				foreach ($literals[$literalType] as $literalName => $literalValue) {
					$literalName = \F3\FLOW3\I18n\Cldr\CldrParser::getValueOfAttributeByName($literalName, 'type');

					$data[$contextType][$widthType][$literalName] = $literalValue;
				}
			}
		}

		return $data;
	}

	/**
	 * Parses "eras" child of "dates" node and returns it's array representation.
	 *
	 * @param \F3\FLOW3\I18n\Cldr\CldrModelCollection $model CldrModel to read data from
	 * @return array An array with localized literals for "eras" node
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function parseLocalizedEras(\F3\FLOW3\I18n\Cldr\CldrModelCollection $model) {
		$data = array();
		foreach ($model->getRawArray('dates/calendars/calendar/type="gregorian"/eras') as $widthType => $eras) {
			foreach ($eras['era'] as $eraName => $eraValue) {
				$eraName = \F3\FLOW3\I18n\Cldr\CldrParser::getValueOfAttributeByName($eraName, 'type');

				$data[$widthType][$eraName] = $eraValue;
			}
		}

		return $data;
	}

	/**
	 * Pads given string to the specified length with zeros.
	 *
	 * @param string $string
	 * @param int $length
	 * @return string Padded string (can be unchanged if $length is lower than length of string)
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function padString($string, $length) {
		return str_pad($string, $length, '0', \STR_PAD_LEFT);
	}
}

?>