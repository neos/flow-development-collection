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
use Neos\Flow\Property\Exception\InvalidSourceException;
use Neos\Flow\Property\Exception\TypeConverterException;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\ResourceManagement\PersistentResource;

/**
 * Converter which transforms various types to arrays.
 *
 * * If the source is an array, it is returned unchanged.
 * * If the source is a string, is is converted depending on CONFIGURATION_STRING_FORMAT,
 *   which can be STRING_FORMAT_CSV or STRING_FORMAT_JSON. For CSV the delimiter can be
 *   set via CONFIGURATION_STRING_DELIMITER.
 * * If the source is a PersistentResource object, it is converted to an array. The actual resource
 *   content is either embedded as base64-encoded data or saved to a file, depending on
 *   CONFIGURATION_RESOURCE_EXPORT_TYPE. For RESOURCE_EXPORT_TYPE_FILE the setting
 *   CONFIGURATION_RESOURCE_SAVE_PATH must be set as well.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ArrayConverter extends AbstractTypeConverter
{
    /**
     * @var string
     */
    const CONFIGURATION_STRING_DELIMITER = 'stringDelimiter';

    /**
     * @var string
     */
    const DEFAULT_STRING_DELIMITER = ',';

    /**
     * @var string
     */
    const CONFIGURATION_STRING_FORMAT = 'stringFormat';

    /**
     * @var string
     */
    const DEFAULT_STRING_FORMAT = self::STRING_FORMAT_CSV;

    /**
     * @var string
     */
    const STRING_FORMAT_CSV = 'csv';

    /**
     * @var string
     */
    const STRING_FORMAT_JSON = 'json';

    /**
     * @var string
     */
    const CONFIGURATION_RESOURCE_EXPORT_TYPE = 'resourceExportType';

    /**
     * @var string
     */
    const DEFAULT_RESOURCE_EXPORT_TYPE = self::RESOURCE_EXPORT_TYPE_BASE64;

    /**
     * @var string
     */
    const RESOURCE_EXPORT_TYPE_BASE64 = 'base64';

    /**
     * @var string
     */
    const RESOURCE_EXPORT_TYPE_FILE = 'file';

    /**
     * @var string
     */
    const CONFIGURATION_RESOURCE_SAVE_PATH = 'resourceSavePath';

    /**
     * @var array<string>
     */
    protected $sourceTypes = ['array', 'string', PersistentResource::class];

    /**
     * @var string
     */
    protected $targetType = 'array';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * Convert from $source to $targetType, a noop if the source is an array.
     *
     * If it is a string it will be converted according to the configured string format.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return array
     * @throws InvalidPropertyMappingConfigurationException
     * @throws InvalidSourceException
     * @throws TypeConverterException
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        if (is_array($source)) {
            return $source;
        }

        if (is_string($source)) {
            if ($source === '') {
                return [];
            } else {
                $stringFormat = $this->getStringFormat($configuration);
                switch ($stringFormat) {
                    case self::STRING_FORMAT_CSV:
                        return explode($this->getStringDelimiter($configuration), $source);
                    case self::STRING_FORMAT_JSON:
                        return json_decode($source, true);
                    default:
                        throw new InvalidPropertyMappingConfigurationException(sprintf('Conversion from string to array failed due to invalid string format setting "%s"', $stringFormat), 1404903208);
                }
            }
        }

        if ($source instanceof PersistentResource) {
            $exportType = $this->getResourceExportType($configuration);
            switch ($exportType) {
                case self::RESOURCE_EXPORT_TYPE_BASE64:
                    return [
                        'filename' => $source->getFilename(),
                        'data' => base64_encode(file_get_contents('resource://' . $source->getSha1()))
                    ];
                case self::RESOURCE_EXPORT_TYPE_FILE:
                    $sourceStream = $source->getStream();
                    if ($sourceStream === false) {
                        throw new InvalidSourceException(sprintf('Could not get stream of resource "%s" (%s). This might be caused by a broken resource object and can be fixed by running the "resource:clean" command.', $source->getFilename(), $source->getSha1()), 1435842312);
                    }
                    $targetStream = fopen($configuration->getConfigurationValue(ArrayConverter::class, self::CONFIGURATION_RESOURCE_SAVE_PATH) . '/' . $source->getSha1(), 'w');
                    stream_copy_to_stream($sourceStream, $targetStream);
                    fclose($targetStream);
                    fclose($sourceStream);
                    return [
                        'filename' => $source->getFilename(),
                        'hash' => $source->getSha1(),
                    ];
                default:
                    throw new InvalidPropertyMappingConfigurationException(sprintf('Conversion from PersistentResource to array failed due to invalid resource export type setting "%s"', $exportType), 1404903210);

            }
        }

        throw new TypeConverterException('Conversion to array failed for unknown reason', 1404903387);
    }

    /**
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws InvalidPropertyMappingConfigurationException
     */
    protected function getStringDelimiter(PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            return self::DEFAULT_STRING_DELIMITER;
        }

        $stringDelimiter = $configuration->getConfigurationValue(ArrayConverter::class, self::CONFIGURATION_STRING_DELIMITER);
        if ($stringDelimiter === null) {
            return self::DEFAULT_STRING_DELIMITER;
        } elseif (!is_string($stringDelimiter)) {
            throw new InvalidPropertyMappingConfigurationException(sprintf('CONFIGURATION_STRING_DELIMITER must be of type string, "%s" given', (is_object($stringDelimiter) ? get_class($stringDelimiter) : gettype($stringDelimiter))), 1368433339);
        }

        return $stringDelimiter;
    }

    /**
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws InvalidPropertyMappingConfigurationException
     */
    protected function getStringFormat(PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            return self::DEFAULT_STRING_FORMAT;
        }

        $stringFormat = $configuration->getConfigurationValue(ArrayConverter::class, self::CONFIGURATION_STRING_FORMAT);
        if ($stringFormat === null) {
            return self::DEFAULT_STRING_FORMAT;
        } elseif (!is_string($stringFormat)) {
            throw new InvalidPropertyMappingConfigurationException(sprintf('CONFIGURATION_STRING_FORMAT must be of type string, "%s" given', (is_object($stringFormat) ? get_class($stringFormat) : gettype($stringFormat))), 1404227443);
        }

        return $stringFormat;
    }

    /**
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws InvalidPropertyMappingConfigurationException
     */
    protected function getResourceExportType(PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            return self::DEFAULT_RESOURCE_EXPORT_TYPE;
        }

        $exportType = $configuration->getConfigurationValue(ArrayConverter::class, self::CONFIGURATION_RESOURCE_EXPORT_TYPE);
        if ($exportType === null) {
            return self::DEFAULT_RESOURCE_EXPORT_TYPE;
        } elseif (!is_string($exportType)) {
            throw new InvalidPropertyMappingConfigurationException(sprintf('RESOURCE_EXPORT_TYPE must be of type string, "%s" given', (is_object($exportType) ? get_class($exportType) : gettype($exportType))), 1404313373);
        }

        return $exportType;
    }
}
