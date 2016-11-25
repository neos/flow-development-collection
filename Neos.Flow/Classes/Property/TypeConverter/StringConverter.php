<?php
namespace Neos\Flow\Property\TypeConverter;

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
use Neos\Flow\Property\Exception\InvalidPropertyMappingConfigurationException;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;

/**
 * Converter which transforms simple types to a string.
 *
 * * If the source is a DateTime instance, it will be formatted as string. The format
 *   can be set via CONFIGURATION_DATE_FORMAT.
 * * If the source is an array, it will be converted to a CSV string or JSON, depending
 *   on CONFIGURATION_ARRAY_FORMAT.
 *
 * For array to CSV string, the delimiter can be set via CONFIGURATION_CSV_DELIMITER.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class StringConverter extends AbstractTypeConverter
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
     * @var string
     */
    const CONFIGURATION_ARRAY_FORMAT = 'arrayFormat';

    /**
     * @var string
     */
    const DEFAULT_ARRAY_FORMAT = self::ARRAY_FORMAT_CSV;

    /**
     * @var string
     */
    const ARRAY_FORMAT_CSV = 'csv';

    /**
     * @var string
     */
    const ARRAY_FORMAT_JSON = 'json';

    /**
     * @var string
     */
    const CONFIGURATION_CSV_DELIMITER = 'csvDelimiter';

    /**
     * @var string
     */
    const DEFAULT_CSV_DELIMITER = ',';

    /**
     * @var array<string>
     */
    protected $sourceTypes = ['string', 'integer', 'float', 'boolean', 'array', \DateTimeInterface::class];

    /**
     * @var string
     */
    protected $targetType = 'string';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws InvalidPropertyMappingConfigurationException
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($source instanceof \DateTimeInterface) {
            $dateFormat = $this->getDateFormat($configuration);

            return $source->format($dateFormat);
        }

        if (is_array($source)) {
            switch ($this->getArrayFormat($configuration)) {
                case self::ARRAY_FORMAT_CSV:
                    return implode($this->getCsvDelimiter($configuration), $source);
                case self::ARRAY_FORMAT_JSON:
                    return json_encode($source);
                default:
                    throw new InvalidPropertyMappingConfigurationException(sprintf('Invalid array export format "%s" given', $this->getArrayFormat($configuration)), 1404317220);
            }
        }

        return (string)$source;
    }

    /**
     * Determines the date format to use for the conversion.
     *
     * If no format is specified in the mapping configuration DEFAULT_DATE_FORMAT is used.
     *
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws InvalidPropertyMappingConfigurationException
     */
    protected function getDateFormat(PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            return self::DEFAULT_DATE_FORMAT;
        }

        $dateFormat = $configuration->getConfigurationValue(StringConverter::class, self::CONFIGURATION_DATE_FORMAT);
        if ($dateFormat === null) {
            return self::DEFAULT_DATE_FORMAT;
        } elseif ($dateFormat !== null && !is_string($dateFormat)) {
            throw new InvalidPropertyMappingConfigurationException('CONFIGURATION_DATE_FORMAT must be of type string, "' . (is_object($dateFormat) ? get_class($dateFormat) : gettype($dateFormat)) . '" given', 1404229004);
        }

        return $dateFormat;
    }

    /**
     * Determines the delimiter to use for the conversion from array to CSV format.
     *
     * If no delimiter is specified in the mapping configuration DEFAULT_CSV_DELIMITER is used.
     *
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws InvalidPropertyMappingConfigurationException
     */
    protected function getCsvDelimiter(PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            return self::DEFAULT_CSV_DELIMITER;
        }

        $csvDelimiter = $configuration->getConfigurationValue(StringConverter::class, self::CONFIGURATION_CSV_DELIMITER);
        if ($csvDelimiter === null) {
            return self::DEFAULT_CSV_DELIMITER;
        } elseif (!is_string($csvDelimiter)) {
            throw new InvalidPropertyMappingConfigurationException('CONFIGURATION_CSV_DELIMITER must be of type string, "' . (is_object($csvDelimiter) ? get_class($csvDelimiter) : gettype($csvDelimiter)) . '" given', 1404229000);
        }

        return $csvDelimiter;
    }

    /**
     * Determines the format to use for the conversion from array to string.
     *
     * If no format is specified in the mapping configuration DEFAULT_ARRAY_FORMAT is used.
     *
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws InvalidPropertyMappingConfigurationException
     */
    protected function getArrayFormat(PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            return self::DEFAULT_ARRAY_FORMAT;
        }

        $arrayFormat = $configuration->getConfigurationValue(StringConverter::class, self::CONFIGURATION_ARRAY_FORMAT);
        if ($arrayFormat === null) {
            return self::DEFAULT_ARRAY_FORMAT;
        } elseif (!is_string($arrayFormat)) {
            throw new InvalidPropertyMappingConfigurationException('CONFIGURATION_ARRAY_FORMAT must be of type string, "' . (is_object($arrayFormat) ? get_class($arrayFormat) : gettype($arrayFormat)) . '" given', 1404228995);
        }

        return $arrayFormat;
    }
}
