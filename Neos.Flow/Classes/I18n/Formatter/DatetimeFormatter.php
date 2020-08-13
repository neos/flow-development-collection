<?php
namespace Neos\Flow\I18n\Formatter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Cldr\Reader\DatesReader;
use Neos\Flow\I18n\Exception\InvalidArgumentException;
use Neos\Flow\I18n\Locale;

/**
 * Formatter for date and time.
 *
 * This is not full implementation of features from CLDR. These are missing:
 * - support for other calendars than Gregorian
 * - rules for displaying timezone names are simplified
 * - some less frequently used format characters are not supported
 *
 * @Flow\Scope("singleton")
 * @api
 */
class DatetimeFormatter implements FormatterInterface
{
    /**
     * @var DatesReader
     */
    protected $datesReader;

    /**
     * @param DatesReader $datesReader
     * @return void
     */
    public function injectDatesReader(DatesReader $datesReader)
    {
        $this->datesReader = $datesReader;
    }

    /**
     * Formats provided value using optional style properties
     *
     * @param mixed $value Formatter-specific variable to format (can be integer, \DateTime, etc)
     * @param Locale $locale Locale to use
     * @param array $styleProperties Integer-indexed array of formatter-specific style properties (can be empty)
     * @return string String representation of $value provided, or (string)$value
     * @api
     */
    public function format($value, Locale $locale, array $styleProperties = [])
    {
        if (isset($styleProperties[0])) {
            $formatType = $styleProperties[0];
            DatesReader::validateFormatType($formatType);
        } else {
            $formatType = DatesReader::FORMAT_TYPE_DATETIME;
        }

        if (isset($styleProperties[1])) {
            $formatLength = $styleProperties[1];
            DatesReader::validateFormatLength($formatLength);
        } else {
            $formatLength = DatesReader::FORMAT_LENGTH_DEFAULT;
        }

        switch ($formatType) {
            case DatesReader::FORMAT_TYPE_DATE:
                return $this->formatDate($value, $locale, $formatLength);
            case DatesReader::FORMAT_TYPE_TIME:
                return $this->formatTime($value, $locale, $formatLength);
            default:
                return $this->formatDateTime($value, $locale, $formatLength);
        }
    }

    /**
     * Returns dateTime formatted by custom format, string provided in parameter.
     *
     * Format must obey syntax defined in CLDR specification, excluding
     * unimplemented features (see documentation for DatesReader class).
     *
     * Format is remembered in this classes cache and won't be parsed again for
     * some time.
     *
     * @param \DateTimeInterface $dateTime PHP object representing particular point in time
     * @param string $format Format string
     * @param Locale $locale A locale used for finding literals array
     * @return string Formatted date / time. Unimplemented subformats in format string will be silently ignored
     * @api
     * @see \Neos\Flow\I18n\Cldr\Reader\DatesReader
     */
    public function formatDateTimeWithCustomPattern(\DateTimeInterface $dateTime, $format, Locale $locale)
    {
        return $this->doFormattingWithParsedFormat($dateTime, $this->datesReader->parseCustomFormat($format), $this->datesReader->getLocalizedLiteralsForLocale($locale));
    }

    /**
     * Formats date with format string for date defined in CLDR for particular
     * locale.
     *
     * @param \DateTimeInterface $date PHP object representing particular point in time
     * @param Locale $locale
     * @param string $formatLength One of DatesReader FORMAT_LENGTH constants
     * @return string Formatted date
     * @api
     */
    public function formatDate(\DateTimeInterface $date, Locale $locale, $formatLength = DatesReader::FORMAT_LENGTH_DEFAULT)
    {
        DatesReader::validateFormatLength($formatLength);
        return $this->doFormattingWithParsedFormat($date, $this->datesReader->parseFormatFromCldr($locale, DatesReader::FORMAT_TYPE_DATE, $formatLength), $this->datesReader->getLocalizedLiteralsForLocale($locale));
    }

    /**
     * Formats time with format string for time defined in CLDR for particular
     * locale.
     *
     * @param \DateTimeInterface $time PHP object representing particular point in time
     * @param Locale $locale
     * @param string $formatLength One of DatesReader FORMAT_LENGTH constants
     * @return string Formatted time
     * @api
     */
    public function formatTime(\DateTimeInterface $time, Locale $locale, $formatLength = DatesReader::FORMAT_LENGTH_DEFAULT)
    {
        DatesReader::validateFormatLength($formatLength);
        return $this->doFormattingWithParsedFormat($time, $this->datesReader->parseFormatFromCldr($locale, DatesReader::FORMAT_TYPE_TIME, $formatLength), $this->datesReader->getLocalizedLiteralsForLocale($locale));
    }

    /**
     * Formats dateTime with format string for date and time defined in CLDR for
     * particular locale.
     *
     * First date and time are formatted separately, and then dateTime format
     * from CLDR is used to place date and time in correct order.
     *
     * @param \DateTimeInterface $dateTime PHP object representing particular point in time
     * @param Locale $locale
     * @param string $formatLength One of DatesReader FORMAT_LENGTH constants
     * @return string Formatted date and time
     * @api
     */
    public function formatDateTime(\DateTimeInterface $dateTime, Locale $locale, $formatLength = DatesReader::FORMAT_LENGTH_DEFAULT)
    {
        DatesReader::validateFormatLength($formatLength);
        return $this->doFormattingWithParsedFormat($dateTime, $this->datesReader->parseFormatFromCldr($locale, DatesReader::FORMAT_TYPE_DATETIME, $formatLength), $this->datesReader->getLocalizedLiteralsForLocale($locale));
    }

    /**
     * Formats provided dateTime object.
     *
     * Format rules defined in $parsedFormat array are used. Localizable literals
     * are replaced with elements from $localizedLiterals array.
     *
     * @param \DateTimeInterface $dateTime PHP object representing particular point in time
     * @param array $parsedFormat An array describing format (as in $parsedFormats property)
     * @param array $localizedLiterals An array with literals to use (as in $localizedLiterals property)
     * @return string Formatted date / time
     */
    protected function doFormattingWithParsedFormat(\DateTimeInterface $dateTime, array $parsedFormat, array $localizedLiterals)
    {
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
     * are simplified. Please see the documentation for DatesReader for details.
     *
     * Cases in the code are ordered in such way that probably mostly used are
     * on the top (but they are also grouped by similarity).
     *
     * @param \DateTimeInterface $dateTime PHP object representing particular point in time
     * @param string $subformat One element of format string (e.g., 'yyyy', 'mm', etc)
     * @param array $localizedLiterals Array of date / time literals from CLDR
     * @return string Formatted part of date / time
     * @throws InvalidArgumentException When $subformat use symbol that is not recognized
     * @see \Neos\Flow\I18n\Cldr\Reader\DatesReader
     */
    protected function doFormattingForSubpattern(\DateTimeInterface $dateTime, $subformat, array $localizedLiterals)
    {
        $formatLengthOfSubformat = strlen($subformat);

        switch ($subformat[0]) {
            case 'h':
                return $this->padString($dateTime->format('g'), $formatLengthOfSubformat);
            case 'H':
                return $this->padString($dateTime->format('G'), $formatLengthOfSubformat);
            case 'K':
                $hour = (int)($dateTime->format('g'));
                if ($hour === 12) {
                    $hour = 0;
                }
                return $this->padString($hour, $formatLengthOfSubformat);
            case 'k':
                $hour = (int)($dateTime->format('G'));
                if ($hour === 0) {
                    $hour = 24;
                }
                return $this->padString($hour, $formatLengthOfSubformat);
            case 'a':
                return $localizedLiterals['dayPeriods']['format']['wide'][$dateTime->format('a')];
            case 'm':
                return $this->padString((int)($dateTime->format('i')), $formatLengthOfSubformat);
            case 's':
                return $this->padString((int)($dateTime->format('s')), $formatLengthOfSubformat);
            case 'S':
                return (string)round($dateTime->format('u'), $formatLengthOfSubformat);
            case 'd':
                return $this->padString($dateTime->format('j'), $formatLengthOfSubformat);
            case 'D':
                return $this->padString((int)($dateTime->format('z') + 1), $formatLengthOfSubformat);
            case 'F':
                return (int)(($dateTime->format('j') + 6) / 7);
            case 'M':
            case 'L':
                $month = (int)$dateTime->format('n');
                $formatType = ($subformat[0] === 'L') ? 'stand-alone' : 'format';
                if ($formatLengthOfSubformat <= 2) {
                    return $this->padString($month, $formatLengthOfSubformat);
                } elseif ($formatLengthOfSubformat === 3) {
                    return $localizedLiterals['months'][$formatType]['abbreviated'][$month];
                } elseif ($formatLengthOfSubformat === 4) {
                    return $localizedLiterals['months'][$formatType]['wide'][$month];
                } else {
                    return $localizedLiterals['months'][$formatType]['narrow'][$month];
                }
                // no break
            case 'y':
                $year = (int)$dateTime->format('Y');
                if ($formatLengthOfSubformat === 2) {
                    $year %= 100;
                }
                return $this->padString($year, $formatLengthOfSubformat);
            case 'E':
                $day = strtolower($dateTime->format('D'));
                if ($formatLengthOfSubformat <= 3) {
                    return $localizedLiterals['days']['format']['abbreviated'][$day];
                } elseif ($formatLengthOfSubformat === 4) {
                    return $localizedLiterals['days']['format']['wide'][$day];
                } else {
                    return $localizedLiterals['days']['format']['narrow'][$day];
                }
                // no break
            case 'w':
                return $this->padString($dateTime->format('W'), $formatLengthOfSubformat);
            case 'W':
                return (string)((((int)$dateTime->format('W') - 1) % 4) + 1);
            case 'Q':
            case 'q':
                $quarter = (int)($dateTime->format('n') / 3.1) + 1;
                $formatType = ($subformat[0] === 'q') ? 'stand-alone' : 'format';
                if ($formatLengthOfSubformat <= 2) {
                    return $this->padString($quarter, $formatLengthOfSubformat);
                } elseif ($formatLengthOfSubformat === 3) {
                    return $localizedLiterals['quarters'][$formatType]['abbreviated'][$quarter];
                } else {
                    return $localizedLiterals['quarters'][$formatType]['wide'][$quarter];
                }
                // no break
            case 'G':
                $era = (int)($dateTime->format('Y') > 0);
                if ($formatLengthOfSubformat <= 3) {
                    return $localizedLiterals['eras']['eraAbbr'][$era];
                } elseif ($formatLengthOfSubformat === 4) {
                    return $localizedLiterals['eras']['eraNames'][$era];
                } else {
                    return $localizedLiterals['eras']['eraNarrow'][$era];
                }
                // no break
            case 'v':
            case 'z':
                if ($formatLengthOfSubformat <= 3) {
                    return $dateTime->format('T');
                } else {
                    return $dateTime->format('e');
                }
                // no break
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
                throw new InvalidArgumentException('Unexpected format symbol, "' . $subformat[0] . '" detected for date / time formatting.', 1276106678);
        }
    }

    /**
     * Pads given string to the specified length with zeros.
     *
     * @param string $string
     * @param int $formatLength
     * @return string Padded string (can be unchanged if $formatLength is lower than length of string)
     */
    protected function padString($string, $formatLength)
    {
        return str_pad($string, $formatLength, '0', \STR_PAD_LEFT);
    }
}
