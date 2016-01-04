<?php
namespace TYPO3\Flow\Property\TypeConverter;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Converter which transforms from string, integer and array into DateTime objects.
 *
 * For integers the default is to treat them as a unix timestamp. If a format to cerate from is given, this will be
 * used instead.
 *
 * If source is a string it is expected to be formatted according to DEFAULT_DATE_FORMAT. This default date format
 * can be overridden in the initialize*Action() method like this::
 *
 *  $this->arguments['<argumentName>']
 *    ->getPropertyMappingConfiguration()
 *    ->forProperty('<propertyName>') // this line can be skipped in order to specify the format for all properties
 *    ->setTypeConverterOption(\TYPO3\Flow\Property\TypeConverter\DateTimeConverter::class, \TYPO3\Flow\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT, '<dateFormat>');
 *
 * If the source is of type array, it is possible to override the format in the source::
 *
 *  array(
 *   'date' => '<dateString>',
 *   'dateFormat' => '<dateFormat>'
 *  );
 *
 * By using an array as source you can also override time and timezone of the created DateTime object::
 *
 *  array(
 *   'date' => '<dateString>',
 *   'hour' => '<hour>', // integer
 *   'minute' => '<minute>', // integer
 *   'seconds' => '<seconds>', // integer
 *   'timezone' => '<timezone>', // string, see http://www.php.net/manual/timezones.php
 *  );
 *
 * As an alternative to providing the date as string, you might supply day, month and year as array items each::
 *
 *  array(
 *   'day' => '<day>', // integer
 *   'month' => '<month>', // integer
 *   'year' => '<year>', // integer
 *  );
 *
 * @api
 * @Flow\Scope("singleton")
 */
class DateTimeConverter extends AbstractTypeConverter
{
    /**
     * @var string
     */
    const CONFIGURATION_DATE_FORMAT = 'dateFormat';

    /**
     * The default date format is "YYYY-MM-DDT##:##:##+##:##", for example "2005-08-15T15:52:01+00:00"
     * according to the W3C standard @see http://www.w3.org/TR/NOTE-datetime.html
     *
     * @var string
     */
    const DEFAULT_DATE_FORMAT = \DateTime::W3C;

    /**
     * @var array<string>
     */
    protected $sourceTypes = array('string', 'integer', 'array');

    /**
     * @var string
     */
    protected $targetType = 'DateTime';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * If conversion is possible.
     *
     * @param string $source
     * @param string $targetType
     * @return boolean
     */
    public function canConvertFrom($source, $targetType)
    {
        if (!is_callable(array($targetType, 'createFromFormat'))) {
            return false;
        }
        if (is_array($source)) {
            return true;
        }
        if (is_integer($source)) {
            return true;
        }
        return is_string($source);
    }

    /**
     * Converts $source to a \DateTime using the configured dateFormat
     *
     * @param string|integer|array $source the string to be converted to a \DateTime object
     * @param string $targetType must be "DateTime"
     * @param array $convertedChildProperties not used currently
     * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
     * @return \DateTime
     * @throws \TYPO3\Flow\Property\Exception\TypeConverterException
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        $dateFormat = $this->getDefaultDateFormat($configuration);
        if (is_string($source)) {
            $dateAsString = $source;
        } elseif (is_integer($source)) {
            $dateAsString = strval($source);
        } else {
            if (isset($source['date']) && is_string($source['date'])) {
                $dateAsString = $source['date'];
            } elseif (isset($source['date']) && is_integer($source['date'])) {
                $dateAsString = strval($source['date']);
            } elseif ($this->isDatePartKeysProvided($source)) {
                if ($source['day'] < 1 || $source['month'] < 1 || $source['year'] < 1) {
                    return new \TYPO3\Flow\Validation\Error('Could not convert the given date parts into a DateTime object because one or more parts were 0.', 1333032779);
                }
                $dateAsString = sprintf('%d-%d-%d', $source['year'], $source['month'], $source['day']);
            } else {
                throw new \TYPO3\Flow\Property\Exception\TypeConverterException('Could not convert the given source into a DateTime object because it was not an array with a valid date as a string', 1308003914);
            }
            if (isset($source['dateFormat']) && $source['dateFormat'] !== '') {
                $dateFormat = $source['dateFormat'];
            }
        }
        if ($dateAsString === '') {
            return null;
        }
        if (ctype_digit($dateAsString) && $configuration === null && (!is_array($source) || !isset($source['dateFormat']))) {
            $dateFormat = 'U';
        }
        if (is_array($source) && isset($source['timezone']) && strlen($source['timezone']) !== 0) {
            try {
                $timezone = new \DateTimeZone($source['timezone']);
            } catch (\Exception $exception) {
                throw new \TYPO3\Flow\Property\Exception\TypeConverterException('The specified timezone "' . $source['timezone'] . '" is invalid.', 1308240974);
            }
            $date = $targetType::createFromFormat($dateFormat, $dateAsString, $timezone);
        } else {
            $date = $targetType::createFromFormat($dateFormat, $dateAsString);
        }
        if ($date === false) {
            return new \TYPO3\Flow\Validation\Error('The date "%s" was not recognized (for format "%s").', 1307719788, array($dateAsString, $dateFormat));
        }
        if (is_array($source)) {
            $this->overrideTimeIfSpecified($date, $source);
        }
        return $date;
    }

    /**
     * Returns whether date information (day, month, year) are present as keys in $source.
     * @param $source
     * @return bool
     */
    protected function isDatePartKeysProvided(array $source)
    {
        return isset($source['day']) && ctype_digit($source['day'])
            && isset($source['month']) && ctype_digit($source['month'])
            && isset($source['year']) && ctype_digit($source['year']);
    }

    /**
     * Determines the default date format to use for the conversion.
     * If no format is specified in the mapping configuration DEFAULT_DATE_FORMAT is used.
     *
     * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
     */
    protected function getDefaultDateFormat(\TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            return self::DEFAULT_DATE_FORMAT;
        }
        $dateFormat = $configuration->getConfigurationValue(\TYPO3\Flow\Property\TypeConverter\DateTimeConverter::class, self::CONFIGURATION_DATE_FORMAT);
        if ($dateFormat === null) {
            return self::DEFAULT_DATE_FORMAT;
        } elseif ($dateFormat !== null && !is_string($dateFormat)) {
            throw new \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException('CONFIGURATION_DATE_FORMAT must be of type string, "' . (is_object($dateFormat) ? get_class($dateFormat) : gettype($dateFormat)) . '" given', 1307719569);
        }
        return $dateFormat;
    }

    /**
     * Overrides hour, minute & second of the given date with the values in the $source array
     *
     * @param \DateTime $date
     * @param array $source
     * @return void
     */
    protected function overrideTimeIfSpecified(\DateTime $date, array $source)
    {
        if (!isset($source['hour']) && !isset($source['minute']) && !isset($source['second'])) {
            return;
        }
        $hour = isset($source['hour']) ? (integer)$source['hour'] : 0;
        $minute = isset($source['minute']) ? (integer)$source['minute'] : 0;
        $second = isset($source['second']) ? (integer)$source['second'] : 0;
        $date->setTime($hour, $minute, $second);
    }
}
