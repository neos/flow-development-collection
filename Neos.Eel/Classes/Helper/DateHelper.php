<?php

namespace Neos\Eel\Helper;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Cldr\Reader\DatesReader;
use Neos\Flow\I18n\Formatter\DatetimeFormatter;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Service as I18nService;

/**
 * Date helpers for Eel contexts
 */
class DateHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var DatetimeFormatter
     */
    protected $datetimeFormatter;

    /**
     * @Flow\Inject
     * @var I18nService
     */
    protected $localizationService;

    /**
     * Parse a date from string with a format to a DateTime object
     *
     * @param string $string
     * @param string $format
     * @return \DateTime
     */
    public function parse($string, $format)
    {
        return \DateTime::createFromFormat($format, $string);
    }

    /**
     * Format a date (or interval) to a string with a given format
     *
     * See formatting options as in PHP date()
     *
     * @param integer|string|\DateTime|\DateInterval $date
     * @param string $format
     * @return string
     */
    public function format($date, $format)
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format($format);
        } elseif ($date instanceof \DateInterval) {
            return $date->format($format);
        } elseif ($date === 'now') {
            return date($format);
        } else {
            $timestamp = (int)$date;
            return date($format, $timestamp);
        }
    }

    /**
     * Format a date to a string with a given cldr format
     *
     * @param integer|string|\DateTime $date
     * @param string $cldrFormat Format string in CLDR format (see http://cldr.unicode.org/translation/date-time)
     * @param null|string $locale String locale - example (de|en|ru_RU)
     * @return string
     */
    public function formatCldr($date, $cldrFormat, $locale = null)
    {
        if ($date === 'now') {
            $date = new \DateTime();
        } elseif (is_int($date)) {
            $timestamp = $date;
            $date = new \DateTime();
            $date->setTimestamp($timestamp);
        } elseif (!$date instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException('The given date "' . $date . '" was neither an integer, "now" or a \DateTimeInterface instance.');
        }
        if (empty($cldrFormat)) {
            throw new \InvalidArgumentException('CLDR date formatting parameter not passed.');
        }
        $useLocale = $this->getLocaleOrDefault($locale);
        return $this->datetimeFormatter->formatDateTimeWithCustomPattern($date, $cldrFormat, $useLocale);
    }

    /**
     * Format a date to a string with a given cldr formatLength
     *
     * @param \DateTimeInterface $dateTime
     * @param string $formatLength FormatLength in CLDR format ("Full", "Long", "Medium", "Short") (see https://cldr.unicode.org/translation/date-time/date-time-patterns#basic-date-formats)
     * @param null|string $locale String locale - example (de|en|en_US). If not provided the current locale of I18nService is used.
     * @return string
     */
    public function formatCldrDate(\DateTimeInterface $dateTime, string $formatLength = DatesReader::FORMAT_LENGTH_DEFAULT, ?string $locale = null): string
    {
        $locale = $this->getLocaleOrDefault($locale);
        return $this->datetimeFormatter->formatDate($dateTime, $locale, $formatLength);
    }

    /**
     * Format a time to a string with a given cldr formatLength
     *
     * @param \DateTimeInterface $dateTime
     * @param string $formatLength FormatLength in CLDR format ("Full", "Long", "Medium", "Short") (see https://cldr.unicode.org/translation/date-time/date-time-patterns#basic-time-formats)
     * @param null|string $locale String locale - example (de|en|en_US). If not provided the current locale of I18nService is used.
     * @return string
     */
    public function formatCldrTime(\DateTimeInterface $dateTime, string $formatLength = DatesReader::FORMAT_LENGTH_DEFAULT, ?string $locale = null): string
    {
        $locale = $this->getLocaleOrDefault($locale);
        return $this->datetimeFormatter->formatTime($dateTime, $locale, $formatLength);
    }

    /**
     * Format a datetime to a string with a given cldr formatLength
     *
     * @param \DateTimeInterface $dateTime
     * @param string $formatLength FormatLength in CLDR format ("Full", "Long", "Medium", "Short") (see https://cldr.unicode.org/translation/date-time/date-time-patterns#basic-date-formats and https://cldr.unicode.org/translation/date-time/date-time-patterns#basic-date-formats)
     * @param null|string $locale String locale - example (de|en|en_US). If not provided the current locale of I18nService is used.
     * @return string
     */
    public function formatCldrDateTime(\DateTimeInterface $dateTime, string $formatLength = DatesReader::FORMAT_LENGTH_DEFAULT, ?string $locale = null): string
    {
        $locale = $this->getLocaleOrDefault($locale);
        return $this->datetimeFormatter->formatDateTime($dateTime, $locale, $formatLength);
    }

    /**
     * Get a date object by given date or time format
     *
     * Examples::
     *
     *     Date.create('2018-12-04')
     *     Date.create('first day of next year')
     *
     * @param String $time A date/time string. For valid formats see http://php.net/manual/en/datetime.formats.php
     * @return \DateTime
     */
    public function create(string $time): \DateTime
    {
        return new \DateTime($time);
    }

    /**
     * Get the current date and time
     *
     * Examples::
     *
     *     Date.now().timestamp
     *
     * @return \DateTime
     */
    public function now()
    {
        return new \DateTime('now');
    }

    /**
     * Get the current date
     *
     * @return \DateTime
     */
    public function today()
    {
        return new \DateTime('today');
    }

    /**
     * Add an interval to a date and return a new DateTime object
     *
     * @param \DateTime $date
     * @param string|\DateInterval $interval
     * @return \DateTime
     */
    public function add($date, $interval)
    {
        if (!$interval instanceof \DateInterval) {
            $interval = new \DateInterval($interval);
        }
        $result = clone $date;
        return $result->add($interval);
    }

    /**
     * Subtract an interval from a date and return a new DateTime object
     *
     * @param \DateTime $date
     * @param string|\DateInterval $interval
     * @return \DateTime
     */
    public function subtract($date, $interval)
    {
        if (!$interval instanceof \DateInterval) {
            $interval = new \DateInterval($interval);
        }
        $result = clone $date;
        return $result->sub($interval);
    }

    /**
     * Get the difference between two dates as a \DateInterval object
     *
     * @param \DateTime $dateA
     * @param \DateTime $dateB
     * @return \DateInterval
     */
    public function diff($dateA, $dateB)
    {
        return $dateA->diff($dateB);
    }

    /**
     * Get the day of month of a date
     *
     * @param \DateTimeInterface $dateTime
     * @return integer The day of month of the given date
     */
    public function dayOfMonth(\DateTimeInterface $dateTime)
    {
        return (int)$dateTime->format('d');
    }

    /**
     * Get the month of a date
     *
     * @param \DateTimeInterface $dateTime
     * @return integer The month of the given date
     */
    public function month(\DateTimeInterface $dateTime)
    {
        return (int)$dateTime->format('m');
    }

    /**
     * Get the year of a date
     *
     * @param \DateTimeInterface $dateTime
     * @return integer The year of the given date
     */
    public function year(\DateTimeInterface $dateTime)
    {
        return (int)$dateTime->format('Y');
    }

    /**
     * Get the hour of a date (24 hour format)
     *
     * @param \DateTimeInterface $dateTime
     * @return integer The hour of the given date
     */
    public function hour(\DateTimeInterface $dateTime)
    {
        return (int)$dateTime->format('H');
    }

    /**
     * Get the minute of a date
     *
     * @param \DateTimeInterface $dateTime
     * @return integer The minute of the given date
     */
    public function minute(\DateTimeInterface $dateTime)
    {
        return (int)$dateTime->format('i');
    }

    /**
     * Get the second of a date
     *
     * @param \DateTimeInterface $dateTime
     * @return integer The second of the given date
     */
    public function second(\DateTimeInterface $dateTime)
    {
        return (int)$dateTime->format('s');
    }

    private function getLocaleOrDefault(?string $locale): Locale
    {
        if ($locale === null) {
            return $this->localizationService->getConfiguration()->getCurrentLocale();
        }
        return new Locale($locale);
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
