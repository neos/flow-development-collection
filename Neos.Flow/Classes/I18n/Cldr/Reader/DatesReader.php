<?php
namespace Neos\Flow\I18n\Cldr\Reader;

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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\I18n\Cldr\CldrModel;
use Neos\Flow\I18n\Cldr\CldrRepository;
use Neos\Flow\I18n\Locale;

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
class DatesReader
{
    /**
     * Constant for date-only format
     *
     * @var string
     */
    const FORMAT_TYPE_DATE = 'date';

    /**
     * Constant for time-only format
     *
     * @var string
     */
    const FORMAT_TYPE_TIME = 'time';

    /**
     * Constant for date and time format
     *
     * @var string
     */
    const FORMAT_TYPE_DATETIME = 'dateTime';

    /**
     * Constant for default length
     *
     * @var string
     */
    const FORMAT_LENGTH_DEFAULT = 'default';

    /**
     * Constant for full length
     *
     * @var string
     */
    const FORMAT_LENGTH_FULL = 'full';

    /**
     * Constant for long length
     *
     * @var string
     */
    const FORMAT_LENGTH_LONG = 'long';

    /**
     * Constant for medium length
     *
     * @var string
     */
    const FORMAT_LENGTH_MEDIUM = 'medium';

    /**
     * Constant for short length
     *
     * @var string
     */
    const FORMAT_LENGTH_SHORT = 'short';

    /**
     * @var CldrRepository
     */
    protected $cldrRepository;

    /**
     * @var VariableFrontend
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
    protected static $maxLengthOfSubformats = [
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
    ];

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
     * @param CldrRepository $repository
     * @return void
     */
    public function injectCldrRepository(CldrRepository $repository)
    {
        $this->cldrRepository = $repository;
    }

    /**
     * Injects the Flow_I18n_Cldr_Reader_DatesReader cache
     *
     * @param VariableFrontend $cache
     * @return void
     */
    public function injectCache(VariableFrontend $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Constructs the reader, loading parsed data from cache if available.
     *
     * @return void
     */
    public function initializeObject()
    {
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
    public function shutdownObject()
    {
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
     * @param Locale $locale
     * @param string $formatType A type of format (one of constant values)
     * @param string $formatLength A length of format (one of constant values)
     * @return array An array representing parsed format
     * @throws Exception\UnableToFindFormatException When there is no proper format string in CLDR
     * @todo make default format reading nicer
     */
    public function parseFormatFromCldr(Locale $locale, $formatType, $formatLength)
    {
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
                $realFormatLength = CldrModel::getAttributeValue($k, 'choice');
                if ($realFormatLength !== false) {
                    break;
                }
            }
        } else {
            $realFormatLength = $formatLength;
        }

        $format = $model->getElement('dates/calendars/calendar[@type="gregorian"]/' . $formatType . 'Formats/' . $formatType . 'FormatLength[@type="' . $realFormatLength . '"]/' . $formatType . 'Format/pattern');

        if (empty($format)) {
            throw new Exception\UnableToFindFormatException('Date / time format was not found. Please check whether CLDR repository is valid.', 1280218994);
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
    public function parseCustomFormat($format)
    {
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
     * @param Locale $locale
     * @return array An array with localized literals
     */
    public function getLocalizedLiteralsForLocale(Locale $locale)
    {
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
     * @throws Exception\InvalidFormatTypeException When value is unallowed
     */
    public static function validateFormatType($formatType)
    {
        if (!in_array($formatType, [self::FORMAT_TYPE_DATE, self::FORMAT_TYPE_TIME, self::FORMAT_TYPE_DATETIME])) {
            throw new Exception\InvalidFormatTypeException('Provided formatType, "' . $formatType . '", is not one of allowed values.', 1281442590);
        }
    }

    /**
     * Validates provided format length and throws exception if value is not
     * allowed.
     *
     * @param string $formatLength
     * @return void
     * @throws Exception\InvalidFormatLengthException When value is not allowed
     */
    public static function validateFormatLength($formatLength)
    {
        if (!in_array($formatLength, [self::FORMAT_LENGTH_DEFAULT, self::FORMAT_LENGTH_FULL, self::FORMAT_LENGTH_LONG, self::FORMAT_LENGTH_MEDIUM, self::FORMAT_LENGTH_SHORT])) {
            throw new Exception\InvalidFormatLengthException('Provided formatLength, "' . $formatLength . '", is not one of allowed values.', 1281442591);
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
     * @throws Exception\InvalidDateTimeFormatException When subformat is longer than maximal value defined in $maxLengthOfSubformats property
     * @see DatesReader::$parsedFormats
     */
    protected function parseFormat($format)
    {
        $parsedFormat = [];
        $formatLengthOfFormat = strlen($format);
        $duringCompletionOfLiteral = false;
        $literal = '';

        for ($i = 0; $i < $formatLengthOfFormat; ++$i) {
            $subformatSymbol = $format[$i];

            if ($subformatSymbol === '\'') {
                if ($i < $formatLengthOfFormat - 1 && $format[$i + 1] === '\'') {
                    // Two apostrophes means that one apostrophe is escaped
                    if ($duringCompletionOfLiteral) {
                        // We are already reading some literal, save it and continue
                        $parsedFormat[] = [$literal];
                        $literal = '';
                    }

                    $parsedFormat[] = ['\''];
                    ++$i;
                } elseif ($duringCompletionOfLiteral) {
                    $parsedFormat[] = [$literal];
                    $literal = '';
                    $duringCompletionOfLiteral = false;
                } else {
                    $duringCompletionOfLiteral = true;
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
                        throw new Exception\InvalidDateTimeFormatException('Date / time pattern is too long: ' . $subformat . ', specification allows up to ' . self::$maxLengthOfSubformats[$subformatSymbol] . ' chars.', 1276114248);
                    }
                } else {
                    $parsedFormat[] = [$subformat];
                }

                $i = $j - 1;
            }
        }

        if ($literal !== '') {
            $parsedFormat[] = [$literal];
        }

        return $parsedFormat;
    }

    /**
     * Parses one CLDR child of "dates" node and returns it's array representation.
     *
     * Many children of "dates" node have common structure, so one method can
     * be used to parse them all.
     *
     * @param CldrModel $model CldrModel to read data from
     * @param string $literalType One of: month, day, quarter, dayPeriod
     * @return array An array with localized literals for given type
     * @todo the two array checks should go away - but that needs clean input data
     */
    protected function parseLocalizedLiterals(CldrModel $model, $literalType)
    {
        $data = [];
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
     * @param CldrModel $model CldrModel to read data from
     * @return array An array with localized literals for "eras" node
     */
    protected function parseLocalizedEras(CldrModel $model)
    {
        $data = [];
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
     * @param Locale $locale Locale to use
     * @param string $formatLength A length of format (full, long, medium, short) or 'default' to use default one from CLDR
     * @return array Merged formats of date and time
     */
    protected function prepareDateAndTimeFormat($format, Locale $locale, $formatLength)
    {
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

        $parsedFormat = [];

        if ($positionOfFirstPlaceholder !== 0) {
            // Add everything before placeholder as literal
            $parsedFormat[] = [substr($format, 0, $positionOfFirstPlaceholder)];
        }

        $parsedFormat = array_merge($parsedFormat, $firstParsedFormat);

        if ($positionOfSecondPlaceholder - $positionOfFirstPlaceholder > 3) {
            // There is something between the placeholders
            $parsedFormat[] = [substr($format, $positionOfFirstPlaceholder + 3, $positionOfSecondPlaceholder - ($positionOfFirstPlaceholder + 3))];
        }

        $parsedFormat = array_merge($parsedFormat, $secondParsedFormat);

        if ($positionOfSecondPlaceholder !== strlen($format) - 1) {
            // Add everything before placeholder as literal
            $parsedFormat[] = [substr($format, $positionOfSecondPlaceholder + 3)];
        }

        return $parsedFormat;
    }
}
