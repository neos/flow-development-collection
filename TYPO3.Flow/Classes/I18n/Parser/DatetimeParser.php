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
 * Parser for date and time.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class DatetimeParser {

	/**
	 * Regex pattern for matching abbreviated timezones, like GMT, CEST, etc.
	 */
	const PATTERN_MATCH_TIMEZONE_ABBREVIATION = '/^[A-Z]{1,5}/';

	/**
	 * Regex pattern for matching TZ database timezones, like Europe/London.
	 */
	const PATTERN_MATCH_TIMEZONE_TZ = '/^[A-z]+\/[A-z_]+(:?\/[A-z_]+)?/';

	/**
	 * @var \F3\FLOW3\I18n\Cldr\Reader\DatesReader
	 */
	protected $datesReader;

	/**
	 * @param \F3\FLOW3\I18n\Cldr\Reader\DatesReader $datesReader
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectDatesReader(\F3\FLOW3\I18n\Cldr\Reader\DatesReader $datesReader) {
		$this->datesReader = $datesReader;
	}

	/**
	 * Parses date/time given as a string using locale information.
	 *
	 * Corresponding date or time format is taken from CLDR basing on locale
	 * object, type of format (date, time, datetime), and it's length variant
	 * (default, full, long, medium, short).
	 *
	 * Can work in strict or lenient mode.
	 *
	 * @param string $datetimeToParse Date and/or time to be parsed
	 * @param \F3\FLOW3\I18n\Locale $locale Locale to use
	 * @param string $formatType A type of format (date, time, datetime)
	 * @param string $formatLength A length of format (default, full, long, medium, short)
	 * @param string $mode Work mode, one of: strict, lenient
	 * @return mixed \DateTime object or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function parseDatetime($datetimeToParse, \F3\FLOW3\I18n\Locale $locale, $formatType = 'date', $formatLength = 'default', $mode = 'strict') {
		if ($mode === 'strict') {
			$datetimeElements = $this->doParsingInStrictMode($datetimeToParse, $locale, $formatType, $formatLength);
		} elseif ($mode === 'lenient') {
			$datetimeElements = $this->doParsingInLenientMode($datetimeToParse, $locale, $formatType, $formatLength);
		} else {
			throw new \F3\FLOW3\I18n\Parser\Exception\UnsupportedParserModeException('Parsing mode "' . $mode . '" is not supported by DatetimeParser.', 1279724707);
		}

		if ($datetimeElements === FALSE) {
			return FALSE;
		}

			// Set default values for elements that were not parsed (@todo: the year 1970 is maybe not the best default value)
		if ($datetimeElements['year'] === NULL) $datetimeElements['year'] = 1970;
		if ($datetimeElements['month'] === NULL) $datetimeElements['month'] = 1;
		if ($datetimeElements['day'] === NULL) $datetimeElements['day'] = 1;
		if ($datetimeElements['hour'] === NULL) $datetimeElements['hour'] = 0;
		if ($datetimeElements['minute'] === NULL) $datetimeElements['minute'] = 0;
		if ($datetimeElements['second'] === NULL) $datetimeElements['second'] = 0;
		if ($datetimeElements['timezone'] === NULL) $datetimeElements['timezone'] = 'Europe/London';

		$datetime = new \DateTime();
		$datetime->setTimezone(new \DateTimeZone($datetimeElements['timezone']));
		$datetime->setTime($datetimeElements['hour'], $datetimeElements['minute'], $datetimeElements['second']);
		$datetime->setDate($datetimeElements['year'], $datetimeElements['month'], $datetimeElements['day']);
		return $datetime;
	}

	/**
	 * Parses date/time in strict mode.
	 *
	 * @param string $datetimeToParse Date/time to be parsed
	 * @param \F3\FLOW3\I18n\Locale $locale Locale to use
	 * @param string $formatType Type of format: decimal, percent, currency
	 * @param string $formatLength A length of format (default, full, long, medium, short)
	 * @return mixed array An array with parsed elements or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function doParsingInStrictMode($datetimeToParse, \F3\FLOW3\I18n\Locale $locale, $formatType, $formatLength) {
		$parsedFormat = $this->datesReader->parseFormatFromCldr($locale, $formatType, $formatLength);
		$localizedLiterals = $this->datesReader->getLocalizedLiteralsForLocale($locale);

		$datetimeElements = array(
			'year' => NULL,
			'month' => NULL,
			'day' => NULL,
			'hour' => NULL,
			'minute' => NULL,
			'second' => NULL,
			'timezone' => NULL,
		);

		$using12HourClock = FALSE;
		$timeIsPm = FALSE;

		try {
			foreach ($parsedFormat as $subformat) {
				if (is_array($subformat)) {
						// This is literal string, should match exactly
					if (\F3\FLOW3\I18n\Utility::stringBeginsWith($datetimeToParse, $subformat[0])) {
						$datetimeToParse = substr_replace($datetimeToParse, '', 0, strlen($subformat[0]));
						continue;
					} else throw new \F3\FLOW3\I18n\Parser\Exception\InvalidParseStringException('Expected literal was not found.', 1279966164);
				}

				$formatLengthOfSubformat = strlen($subformat);
				$numberOfCharactersToRemove = 0;

				switch ($subformat[0]) {
					case 'h':
					case 'K':
						$hour = $this->extractAndCheckNumber($datetimeToParse, ($formatLengthOfSubformat === 2), 1, 12);
						$numberOfCharactersToRemove = ($formatLengthOfSubformat === 1 && $hour < 10) ? 1 : 2;
						if ($subformat[0] === 'h' && $hour === 12) $hour = 0;
						$datetimeElements['hour'] = $hour;
						$using12HourClock = TRUE;
						break;
					case 'k':
					case 'H':
						$hour = $this->extractAndCheckNumber($datetimeToParse, ($formatLengthOfSubformat === 2), 1, 24);
						$numberOfCharactersToRemove = ($formatLengthOfSubformat === 1 && $hour < 10) ? 1 : 2;
						if ($subformat[0] === 'k' && $hour === 24) $hour = 0;
						$datetimeElements['hour'] = $hour;
						break;
					case 'a':
						$dayPeriods = $localizedLiterals['dayPeriods']['format']['wide'];
						if (\F3\FLOW3\I18n\Utility::stringBeginsWith($datetimeToParse, $dayPeriods['am'])) {
							$numberOfCharactersToRemove = strlen($dayPeriods['am']);
						} elseif (\F3\FLOW3\I18n\Utility::stringBeginsWith($datetimeToParse, $dayPeriods['pm'])) {
							$timeIsPm = TRUE;
							$numberOfCharactersToRemove = strlen($dayPeriods['pm']);
						} else throw new \F3\FLOW3\I18n\Parser\Exception\InvalidParseStringException('Expected localized AM or PM literal was not found.', 1279964396);
						break;
					case 'm':
						$minute = $this->extractAndCheckNumber($datetimeToParse, ($formatLengthOfSubformat === 2), 0, 59);
						$numberOfCharactersToRemove = ($formatLengthOfSubformat === 1 && $minute < 10) ? 1 : 2;
						$datetimeElements['minute'] = $minute;
						break;
					case 's':
						$second = $this->extractAndCheckNumber($datetimeToParse, ($formatLengthOfSubformat === 2), 0, 59);
						$numberOfCharactersToRemove = ($formatLengthOfSubformat === 1 && $second < 10) ? 1 : 2;
						$datetimeElements['second'] = $second;
						break;
					case 'd':
						$dayOfTheMonth = $this->extractAndCheckNumber($datetimeToParse, ($formatLengthOfSubformat === 2), 1, 31);
						$numberOfCharactersToRemove = ($formatLengthOfSubformat === 1 && $dayOfTheMonth < 10) ? 1 : 2;
						$datetimeElements['day'] = $dayOfTheMonth;
						break;
					case 'M':
					case 'L':
						$formatTypeOfLiteral = ($subformat[0] === 'L') ? 'stand-alone' : 'format';
						if ($formatLengthOfSubformat <= 2) {
							$month = $this->extractAndCheckNumber($datetimeToParse, ($formatLengthOfSubformat === 2), 1, 12);
							$numberOfCharactersToRemove = ($formatLengthOfSubformat === 1 && $month < 10) ? 1 : 2;
						} else if ($formatLengthOfSubformat <= 4) {
							$lenghtOfLiteral = ($formatLengthOfSubformat === 3) ? 'abbreviated' : 'wide';

							$month = 0;
							foreach ($localizedLiterals['months'][$formatTypeOfLiteral][$lenghtOfLiteral] as $monthId => $monthName) {
								if (\F3\FLOW3\I18n\Utility::stringBeginsWith($datetimeToParse, $monthName)) {
									$month = $monthId;
									break;
								}
							}
						} else throw new \F3\FLOW3\I18n\Parser\Exception\InvalidParseStringException('Cannot parse formats with narrow month pattern as it is not unique.', 1279965245);

						if ($month === 0) return FALSE;
						$datetimeElements['month'] = $month;
						break;
					case 'y':
						if ($formatLengthOfSubformat === 2) {
							$year = substr($datetimeToParse, 0, 2);
							$numberOfCharactersToRemove = 2;
						} else {
							$year = substr($datetimeToParse, 0, $formatLengthOfSubformat);

							for ($i = $formatLengthOfSubformat; $i < strlen($datetimeToParse); ++$i) {
								if (is_numeric($datetimeToParse[$i])) {
									$year .= $datetimeToParse[$i];
								} else {
									break;
								}
							}

							$numberOfCharactersToRemove = $i;
						}

						if (!is_numeric($year)) {
							return FALSE;
						}

						$year = (int)$year;
						$datetimeElements['year'] = $year;
						break;
					case 'v':
					case 'z':
						if ($formatLengthOfSubformat <= 3) {
							$pattern = self::PATTERN_MATCH_TIMEZONE_ABBREVIATION;
						} else {
							$pattern = self::PATTERN_MATCH_TIMEZONE_TZ;
						}

						if (preg_match($pattern, $datetimeToParse, $matches) === 0) return FALSE;

						$datetimeElements['timezone'] = $matches[0];
						break;
					case 'D':
					case 'F':
					case 'w':
					case 'W':
					case 'Q':
					case 'q':
					case 'G':
					case 'S':
					case 'E':
					case 'Y':
					case 'u':
					case 'l':
					case 'g':
					case 'e':
					case 'c':
					case 'A':
					case 'Z':
					case 'V':
							// Silently ignore unsupported formats or formats that there is no need to parse
						break;
					default:
						throw new \F3\FLOW3\I18n\Exception\InvalidArgumentException('Unexpected format symbol, "' . $subformat[0] . '" detected for date / time parsing.', 1279965528);
				}

				if ($using12HourClock && $timeIsPm) {
					$datetimeElements['hour'] += 12;
					$timeIsPm = FALSE;
				}

				if ($numberOfCharactersToRemove > 0) {
					$datetimeToParse = substr_replace($datetimeToParse, '', 0, $numberOfCharactersToRemove);
				}
			}
		} catch (\F3\FLOW3\I18n\Parser\Exception\InvalidParseStringException $exception) {
			return FALSE;
		}

		return $datetimeElements;
	}

	/**
	 * Parses date/time in lenient mode.
	 *
	 * @param string $datetimeToParse Date/time to be parsed
	 * @param \F3\FLOW3\I18n\Locale $locale Locale to use
	 * @param string $formatType Type of format: decimal, percent, currency
	 * @param string $formatLength A length of format (default, full, long, medium, short)
	 * @return mixed array An array with parsed elements or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @todo Implement lenient parsing
	 */
	protected function doParsingInLenientMode($stringValue, \F3\FLOW3\I18n\Locale $locale, $formatType, $formatLength) {
		return FALSE;
	}

	/**
	 * Extracts one or two-digit number from the beginning of the string.
	 *
	 * If the number has certainly two digits, $isTwoDigits can be set to TRUE
	 * so no additional checking is done (this implies from some date/time
	 * formats, like 'hh').
	 *
	 * Number is also checked for constraints: minimum and maximum value.
	 *
	 * @param string $datetimeToParse Date/time to be parsed
	 * @param bool $isTwoDigits TRUE if number has surely two digits, FALSE if it has one or two digits
	 * @param int $minValue
	 * @param int $maxValue
	 * @return int Parsed number
	 * @throws \F3\FLOW3\I18n\Parser\Exception\InvalidParseStringException When string cannot be parsed or number does not conforms constraints
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function extractAndCheckNumber($datetimeToParse, $isTwoDigits, $minValue, $maxValue) {
		if ($isTwoDigits || is_numeric($datetimeToParse[1])) {
			$number = substr($datetimeToParse, 0, 2);
		} else {
			$number = $datetimeToParse[0];
		}

		if (is_numeric($number)) {
			$number = (int)$number;

			if ($number <= $maxValue || $number >= $minValue) {
				return $number;
			}
		}

		throw new \F3\FLOW3\I18n\Parser\Exception\InvalidParseStringException('DatetimeParser encountered unexpected character sequence in parse string (expecting one or two digit number).', 1279963654);
	}
}

?>