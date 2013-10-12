<?php
namespace TYPO3\Flow\I18n\Parser;

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
 * Parser for date and time.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class DatetimeParser {

	/**
	 * Regex pattern for matching abbreviated timezones, like GMT, CEST, etc.
	 * Two versions for strict and lenient matching modes.
	 */
	const PATTERN_MATCH_STRICT_TIMEZONE_ABBREVIATION = '/^[A-Z]{3,5}/';
	const PATTERN_MATCH_LENIENT_TIMEZONE_ABBREVIATION = '/[A-Z]{3,5}/';

	/**
	 * Regex pattern for matching TZ database timezones, like Europe/London.
	 * Two versions for strict and lenient matching modes.
	 */
	const PATTERN_MATCH_STRICT_TIMEZONE_TZ = '/^[A-z]+\/[A-z_]+(:?\/[A-z_]+)?/';
	const PATTERN_MATCH_LENIENT_TIMEZONE_TZ = '/[A-z]+\/[A-z_]+(:?\/[A-z_]+)?/';

	/**
	 * @var \TYPO3\Flow\I18n\Cldr\Reader\DatesReader
	 */
	protected $datesReader;

	/**
	 * @param \TYPO3\Flow\I18n\Cldr\Reader\DatesReader $datesReader
	 * @return void
	 */
	public function injectDatesReader(\TYPO3\Flow\I18n\Cldr\Reader\DatesReader $datesReader) {
		$this->datesReader = $datesReader;
	}

	/**
	 * Returns dateTime parsed by custom format (string provided in parameter).
	 *
	 * Format must obey syntax defined in CLDR specification, excluding
	 * unimplemented features (see documentation for DatesReader class).
	 *
	 * Format is remembered in cache and won't be parsed again for some time.
	 *
	 * @param string $datetimeToParse Date/time to be parsed
	 * @param string $format Format string
	 * @param \TYPO3\Flow\I18n\Locale $locale A locale used for finding literals array
	 * @param boolean $strictMode Work mode (strict when TRUE, lenient when FALSE)
	 * @return mixed Array of parsed date / time elements, FALSE on failure
	 * @api
	 * @see \TYPO3\Flow\I18n\Cldr\Reader\DatesReader
	 */
	public function parseDatetimeWithCustomPattern($datetimeToParse, $format, \TYPO3\Flow\I18n\Locale $locale, $strictMode = TRUE) {
		return $this->doParsingWithParsedFormat($datetimeToParse, $this->datesReader->parseCustomFormat($format), $this->datesReader->getLocalizedLiteralsForLocale($locale), $strictMode);
	}

	/**
	 * Parses date with format string for date defined in CLDR for particular
	 * locale.
	 *
	 * @param string $dateToParse date to be parsed
	 * @param \TYPO3\Flow\I18n\Locale $locale
	 * @param string $formatLength One of: full, long, medium, short, or 'default' in order to use default length from CLDR
	 * @param boolean $strictMode Work mode (strict when TRUE, lenient when FALSE)
	 * @return mixed Array of parsed date elements, FALSE on failure
	 * @api
	 */
	public function parseDate($dateToParse, \TYPO3\Flow\I18n\Locale $locale, $formatLength = \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT, $strictMode = TRUE) {
		\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::validateFormatLength($formatLength);
		return $this->doParsingWithParsedFormat($dateToParse, $this->datesReader->parseFormatFromCldr($locale, \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATE, $formatLength), $this->datesReader->getLocalizedLiteralsForLocale($locale), $strictMode);
	}

	/**
	 * Parses time with format string for time defined in CLDR for particular
	 * locale.
	 *
	 * @param string $timeToParse Time to be parsed
	 * @param \TYPO3\Flow\I18n\Locale $locale
	 * @param string $formatLength One of: full, long, medium, short, or 'default' in order to use default length from CLDR
	 * @param boolean $strictMode Work mode (strict when TRUE, lenient when FALSE)
	 * @return mixed Array of parsed time elements, FALSE on failure
	 * @api
	 */
	public function parseTime($timeToParse, \TYPO3\Flow\I18n\Locale $locale, $formatLength = \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT, $strictMode = TRUE) {
		\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::validateFormatLength($formatLength);
		return $this->doParsingWithParsedFormat($timeToParse, $this->datesReader->parseFormatFromCldr($locale, \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME, $formatLength), $this->datesReader->getLocalizedLiteralsForLocale($locale), $strictMode);
	}

	/**
	 * Parses dateTime with format string for date and time defined in CLDR for
	 * particular locale.
	 *
	 * @param string $dateAndTimeToParse Date and time to be parsed
	 * @param \TYPO3\Flow\I18n\Locale $locale
	 * @param string $formatLength One of: full, long, medium, short, or 'default' in order to use default length from CLDR
	 * @param boolean $strictMode Work mode (strict when TRUE, lenient when FALSE)
	 * @return mixed Array of parsed date and time elements, FALSE on failure
	 */
	public function parseDateAndTime($dateAndTimeToParse, \TYPO3\Flow\I18n\Locale $locale, $formatLength = \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT, $strictMode = TRUE) {
		\TYPO3\Flow\I18n\Cldr\Reader\DatesReader::validateFormatLength($formatLength);
		return $this->doParsingWithParsedFormat($dateAndTimeToParse, $this->datesReader->parseFormatFromCldr($locale, \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATETIME, $formatLength), $this->datesReader->getLocalizedLiteralsForLocale($locale), $strictMode);
	}

	/**
	 * Parses date and / or time using parsed format, in strict or lenient mode.
	 *
	 * @param string $datetimeToParse Date/time to be parsed
	 * @param array $parsedFormat Parsed format (from DatesReader)
	 * @param array $localizedLiterals Array of date / time literals from CLDR
	 * @param boolean $strictMode Work mode (strict when TRUE, lenient when FALSE)
	 * @return mixed Array of parsed date and / or time elements, FALSE on failure
	 */
	protected function doParsingWithParsedFormat($datetimeToParse, array $parsedFormat, array $localizedLiterals, $strictMode) {
		return ($strictMode) ? $this->doParsingInStrictMode($datetimeToParse, $parsedFormat, $localizedLiterals) : $this->doParsingInLenientMode($datetimeToParse, $parsedFormat, $localizedLiterals);
	}

	/**
	 * Parses date and / or time in strict mode.
	 *
	 * @param string $datetimeToParse Date/time to be parsed
	 * @param array $parsedFormat Format parsed by DatesReader
	 * @param array $localizedLiterals Array of date / time literals from CLDR
	 * @return array Array of parsed date and / or time elements, FALSE on failure
	 * @throws \TYPO3\Flow\I18n\Exception\InvalidArgumentException When unexpected symbol found in format
	 * @see \TYPO3\Flow\I18n\Cldr\Reader\DatesReader
	 */
	protected function doParsingInStrictMode($datetimeToParse, array $parsedFormat, array $localizedLiterals) {
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
					if (\TYPO3\Flow\I18n\Utility::stringBeginsWith($datetimeToParse, $subformat[0])) {
						$datetimeToParse = substr_replace($datetimeToParse, '', 0, strlen($subformat[0]));
						continue;
					} else {
						return FALSE;
					}
				}

				$lengthOfSubformat = strlen($subformat);
				$numberOfCharactersToRemove = 0;

				switch ($subformat[0]) {
					case 'h':
					case 'K':
						$hour = $this->extractAndCheckNumber($datetimeToParse, ($lengthOfSubformat === 2), 1, 12);
						$numberOfCharactersToRemove = ($lengthOfSubformat === 1 && $hour < 10) ? 1 : 2;
						if ($subformat[0] === 'h' && $hour === 12) {
							$hour = 0;
						}
						$datetimeElements['hour'] = $hour;
						$using12HourClock = TRUE;
						break;
					case 'k':
					case 'H':
						$hour = $this->extractAndCheckNumber($datetimeToParse, ($lengthOfSubformat === 2), 1, 24);
						$numberOfCharactersToRemove = ($lengthOfSubformat === 1 && $hour < 10) ? 1 : 2;
						if ($subformat[0] === 'k' && $hour === 24) {
							$hour = 0;
						}
						$datetimeElements['hour'] = $hour;
						break;
					case 'a':
						$dayPeriods = $localizedLiterals['dayPeriods']['format']['wide'];
						if (\TYPO3\Flow\I18n\Utility::stringBeginsWith($datetimeToParse, $dayPeriods['am'])) {
							$numberOfCharactersToRemove = strlen($dayPeriods['am']);
						} elseif (\TYPO3\Flow\I18n\Utility::stringBeginsWith($datetimeToParse, $dayPeriods['pm'])) {
							$timeIsPm = TRUE;
							$numberOfCharactersToRemove = strlen($dayPeriods['pm']);
						} else {
							return FALSE;
						}
						break;
					case 'm':
						$minute = $this->extractAndCheckNumber($datetimeToParse, ($lengthOfSubformat === 2), 0, 59);
						$numberOfCharactersToRemove = ($lengthOfSubformat === 1 && $minute < 10) ? 1 : 2;
						$datetimeElements['minute'] = $minute;
						break;
					case 's':
						$second = $this->extractAndCheckNumber($datetimeToParse, ($lengthOfSubformat === 2), 0, 59);
						$numberOfCharactersToRemove = ($lengthOfSubformat === 1 && $second < 10) ? 1 : 2;
						$datetimeElements['second'] = $second;
						break;
					case 'd':
						$dayOfTheMonth = $this->extractAndCheckNumber($datetimeToParse, ($lengthOfSubformat === 2), 1, 31);
						$numberOfCharactersToRemove = ($lengthOfSubformat === 1 && $dayOfTheMonth < 10) ? 1 : 2;
						$datetimeElements['day'] = $dayOfTheMonth;
						break;
					case 'M':
					case 'L':
						$typeOfLiteral = ($subformat[0] === 'L') ? 'stand-alone' : 'format';
						if ($lengthOfSubformat <= 2) {
							$month = $this->extractAndCheckNumber($datetimeToParse, ($lengthOfSubformat === 2), 1, 12);
							$numberOfCharactersToRemove = ($lengthOfSubformat === 1 && $month < 10) ? 1 : 2;
						} elseif ($lengthOfSubformat <= 4) {
							$lengthOfLiteral = ($lengthOfSubformat === 3) ? 'abbreviated' : 'wide';

							$month = 0;
							foreach ($localizedLiterals['months'][$typeOfLiteral][$lengthOfLiteral] as $monthId => $monthName) {
								if (\TYPO3\Flow\I18n\Utility::stringBeginsWith($datetimeToParse, $monthName)) {
									$month = $monthId;
									break;
								}
							}
						} else {
							throw new \TYPO3\Flow\I18n\Exception\InvalidArgumentException('Cannot parse formats with narrow month pattern as it is not unique.', 1279965245);
						}

						if ($month === 0) {
							return FALSE;
						}
						$datetimeElements['month'] = $month;
						break;
					case 'y':
						if ($lengthOfSubformat === 2) {
							/** @todo How should the XX date be returned? Like 19XX? **/
							$year = substr($datetimeToParse, 0, 2);
							$numberOfCharactersToRemove = 2;
						} else {
							$year = substr($datetimeToParse, 0, $lengthOfSubformat);

							for ($i = $lengthOfSubformat; $i < strlen($datetimeToParse); ++$i) {
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
						if ($lengthOfSubformat <= 3) {
							$pattern = self::PATTERN_MATCH_STRICT_TIMEZONE_ABBREVIATION;
						} else {
							$pattern = self::PATTERN_MATCH_STRICT_TIMEZONE_TZ;
						}

						if (preg_match($pattern, $datetimeToParse, $matches) !== 1) {
							return FALSE;
						}

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
						throw new \TYPO3\Flow\I18n\Exception\InvalidArgumentException('Unexpected format symbol, "' . $subformat[0] . '" detected for date / time parsing.', 1279965528);
				}

				if ($using12HourClock && $timeIsPm) {
					$datetimeElements['hour'] += 12;
					$timeIsPm = FALSE;
				}

				if ($numberOfCharactersToRemove > 0) {
					$datetimeToParse = substr_replace($datetimeToParse, '', 0, $numberOfCharactersToRemove);
				}
			}
		} catch (\TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException $exception) {
				// Method extractAndCheckNumber() throws exception when constraints in $datetimeToParse are not fulfilled
			return FALSE;
		}

		return $datetimeElements;
	}

	/**
	 * Parses date and / or time in lenient mode.
	 *
	 * Algorithm assumptions:
	 * - ignore all literals
	 * - order of elements in parsed format is important
	 * - length of subformat is not strictly checked (eg. 'h' and 'hh')
	 * - number must be in range in order to be accepted (eg. 1-12 for month)
	 * - some format fallback substitutions can be done (eg. 'Jan' for 'January')
	 *
	 * @param string $datetimeToParse Date/time to be parsed
	 * @param array $parsedFormat Format parsed by DatesReader
	 * @param array $localizedLiterals Array of date / time literals from CLDR
	 * @return array Array of parsed date and / or time elements (can be array of NULLs if nothing was parsed)
	 * @throws \TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException
	 * @throws \TYPO3\Flow\I18n\Exception\InvalidArgumentException When unexpected symbol found in format
	 * @see \TYPO3\Flow\I18n\Cldr\Reader\DatesReader
	 */
	protected function doParsingInLenientMode($datetimeToParse, array $parsedFormat, array $localizedLiterals) {
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

		foreach ($parsedFormat as $subformat) {
			try {
				if (is_array($subformat)) {
						// This is literal string, and we ignore them
					continue;
				}

				$lengthOfSubformat = strlen($subformat);
				$numberOfCharactersToRemove = 0;
				$position = 0;

				switch ($subformat[0]) {
					case 'K':
						$hour = $this->extractNumberAndGetPosition($datetimeToParse, $position);
						if ($hour >= 0 && $hour <= 11) {
							$numberOfCharactersToRemove = $position + strlen($hour);
							$datetimeElements['hour'] = (int)$hour;
							$using12HourClock = TRUE;
							break;
						}
					case 'h':
						if (!isset($hour)) {
							$hour = $this->extractNumberAndGetPosition($datetimeToParse, $position);
						}
						if ($hour >= 1 && $hour <= 12) {
							$numberOfCharactersToRemove = $position + strlen($hour);
							if ((int)$hour === 12) {
								$hour = 0;
							}
							$datetimeElements['hour'] = (int)$hour;
							$using12HourClock = TRUE;
							break;
						}
					case 'H':
						if (!isset($hour)) {
							$hour = $this->extractNumberAndGetPosition($datetimeToParse, $position);
						}
						if ($hour >= 0 && $hour <= 23) {
							$numberOfCharactersToRemove = $position + strlen($hour);
							$datetimeElements['hour'] = (int)$hour;
							break;
						}
					case 'k':
						if (!isset($hour)) {
							$hour = $this->extractNumberAndGetPosition($datetimeToParse, $position);
						}
						if ($hour >= 1 && $hour <= 24) {
							$numberOfCharactersToRemove = $position + strlen($hour);
							if ((int)$hour === 24) {
								$hour = 0;
							}
							$datetimeElements['hour'] = (int)$hour;
							break;
						} else {
							throw new \TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException('Unable to match number string to any hour format.', 1280488645);
						}
					case 'a':
						$dayPeriods = $localizedLiterals['dayPeriods']['format']['wide'];
						$positionOfDayPeriod = strpos($datetimeToParse, $dayPeriods['am']);
						if ($positionOfDayPeriod !== FALSE) {
							$numberOfCharactersToRemove = $positionOfDayPeriod + strlen($dayPeriods['am']);
						} else {
							$positionOfDayPeriod = strpos($datetimeToParse, $dayPeriods['pm']);
							if ($positionOfDayPeriod !== FALSE) {
								$numberOfCharactersToRemove = $positionOfDayPeriod + strlen($dayPeriods['pm']);
								$timeIsPm = TRUE;
							} else {
								throw new \TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException('Unable to match any day period.', 1280489183);
							}
						}
						break;
					case 'm':
						$minute = $this->extractNumberAndGetPosition($datetimeToParse, $position);
						if ($minute < 0 && $minute > 59) {
							throw new \TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException('Expected minute is out of range.', 1280489411);
						}
						$numberOfCharactersToRemove = $position + strlen($minute);
						$datetimeElements['minute'] = (int)$minute;
						break;
					case 's':
						$second = $this->extractNumberAndGetPosition($datetimeToParse, $position);
						if ($second < 0 && $second > 59) {
							throw new \TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException('Expected second is out of range.', 1280489412);
						}
						$numberOfCharactersToRemove = $position + strlen($second);
						$datetimeElements['second'] = (int)$second;
						break;
					case 'd':
						$day = $this->extractNumberAndGetPosition($datetimeToParse, $position);
						if ($day < 1 && $day > 31) {
							throw new \TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException('Expected day is out of range.', 1280489413);
						}
						$numberOfCharactersToRemove = $position + strlen($day);
						$datetimeElements['day'] = (int)$day;
						break;
					case 'M':
					case 'L':
						$typeOfLiteral = ($subformat[0] === 'L') ? 'stand-alone' : 'format';
						switch ($lengthOfSubformat) {
							case 1:
							case 2:
								try {
									$month = $this->extractNumberAndGetPosition($datetimeToParse, $position);
									if ($month >= 1 && $month <= 31) {
										$numberOfCharactersToRemove = $position + strlen($month);
										$datetimeElements['month'] = (int)$month;
										break;
									}
								} catch (\TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException $exception) {
										// Try to match month's name by cases below
								}
							case 3:
								foreach ($localizedLiterals['months'][$typeOfLiteral]['abbreviated'] as $monthId => $monthName) {
									$positionOfMonthName = strpos($datetimeToParse, $monthName);
									if ($positionOfMonthName !== FALSE) {
										$numberOfCharactersToRemove = $positionOfMonthName + strlen($monthName);
										$datetimeElements['month'] = (int)$monthId;
										break;
									}
								}

								if ($datetimeElements['month'] !== NULL) {
									break;
								}
							case 4:
								foreach ($localizedLiterals['months'][$typeOfLiteral]['wide'] as $monthId => $monthName) {
									$positionOfMonthName = strpos($datetimeToParse, $monthName);
									if ($positionOfMonthName !== FALSE) {
										$numberOfCharactersToRemove = $positionOfMonthName + strlen($monthName);
										$datetimeElements['month'] = (int)$monthId;
										break;
									}
								}

								if ($datetimeElements['month'] === NULL) {
									throw new \TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException('Neither month name or number were matched.', 1280497950);
								}
							default:
								throw new \TYPO3\Flow\I18n\Exception\InvalidArgumentException('Cannot parse formats with narrow month pattern as it is not unique.', 1280495827);
						}
						break;
					case 'y':
						$year = $this->extractNumberAndGetPosition($datetimeToParse, $position);
						$numberOfCharactersToRemove = $position + strlen($year);

						/** @todo Two digits date (like 99) shoud be handled here somehow **/
						$datetimeElements['year'] = (int)$year;
						break;
					case 'v':
					case 'z':
						if ($lengthOfSubformat <= 3) {
							$firstPattern = self::PATTERN_MATCH_LENIENT_TIMEZONE_ABBREVIATION;
							$secondPattern = self::PATTERN_MATCH_LENIENT_TIMEZONE_TZ;
						} else {
							$firstPattern = self::PATTERN_MATCH_LENIENT_TIMEZONE_TZ;
							$secondPattern = self::PATTERN_MATCH_LENIENT_TIMEZONE_ABBREVIATION;
						}

						if (preg_match($firstPattern, $datetimeToParse, $matches) === 0) {
							if (preg_match($secondPattern, $datetimeToParse, $matches) === 0) {
								throw new \TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException('Expected timezone identifier was not found.', 1280492312);
							}
						}

						$timezone = $matches[0];
						$numberOfCharactersToRemove = strpos($datetimeToParse, $timezone) + strlen($timezone);
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
						throw new \TYPO3\Flow\I18n\Exception\InvalidArgumentException('Unexpected format symbol, "' . $subformat[0] . '" detected for date / time parsing.', 1279965529);
				}

				if ($using12HourClock && $timeIsPm) {
					$datetimeElements['hour'] += 12;
					$timeIsPm = FALSE;
				}

				if ($numberOfCharactersToRemove > 0) {
					$datetimeToParse = substr_replace($datetimeToParse, '', 0, $numberOfCharactersToRemove);
				}
			} catch (\TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException $exception) {
					// Matching failed, but in lenient mode we ignore it and try to match next element
				continue;
			}
		}

		return $datetimeElements;
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
	 * @param boolean $isTwoDigits TRUE if number has surely two digits, FALSE if it has one or two digits
	 * @param int $minValue
	 * @param int $maxValue
	 * @return int Parsed number
	 * @throws \TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException When string cannot be parsed or number does not conforms constraints
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

		throw new \TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException('Expected one or two-digit number not found at the beginning of the string.', 1279963654);
	}

	/**
	 * Extracts and returns first integer number encountered in provided string.
	 *
	 * Searches for first digit and extracts all adjacent digits. Also returns
	 * position of first digit in string.
	 *
	 * @param string $datetimeToParse String to search number in
	 * @param int $position Index of first digit in string
	 * @return string Extracted number
	 * @throws \TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException When no digit found in string
	 */
	protected function extractNumberAndGetPosition($datetimeToParse, &$position) {
		$characters = str_split($datetimeToParse);

		$number = '';
		$numberStarted = FALSE;
		foreach ($characters as $index => $character) {
			if (ord($character) >= 48 && ord($character) <= 57) {
				if (!$numberStarted) {
					$numberStarted = TRUE;
					$position = $index;
				}
				$number .= $character;
			} elseif ($numberStarted) {
				return $number;
			}
		}

		if ($numberStarted) {
			return $number;
		}

		throw new \TYPO3\Flow\I18n\Parser\Exception\InvalidParseStringException('Expected number not found in the string.', 1280498431);
	}
}
