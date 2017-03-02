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
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Utility\Arrays;
use Neos\Utility\MediaTypes;

/**
 * Converter which transforms strings to arrays using the configured strategy.
 * This TypeConverter is used by default to decode the content of a HTTP request and it currently supports json and xml
 * based media types as well as urlencoded content.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class MediaTypeConverter extends AbstractTypeConverter implements MediaTypeConverterInterface
{
    /**
     * @var string
     */
    protected $sourceTypes = ['string'];

    /**
     * @var string
     */
    protected $targetType = 'array';

    /**
     * This converter is not used automatically
     *
     * @var integer
     */
    protected $priority = -1;

    /**
     * Convert the given $source to $targetType depending on the MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE property mapping configuration
     *
     * @param string $source the raw request body
     * @param string $targetType must be "array"
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return array
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        $mediaType = null;
        if ($configuration !== null) {
            $mediaType = $configuration->getConfigurationValue(MediaTypeConverterInterface::class, MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE);
        }
        if ($mediaType === null) {
            $mediaType = MediaTypeConverterInterface::DEFAULT_MEDIA_TYPE;
        }
        return $this->convertMediaType($source, $mediaType);
    }

    /**
     * Converts the given request body according to the specified media type
     * Override this method in your custom TypeConverter to support additional media types
     *
     * @param string $requestBody the raw request body
     * @param string $mediaType the configured media type (for example "application/json")
     * @return array
     * @api
     */
    protected function convertMediaType($requestBody, $mediaType)
    {
        $mediaTypeParts = MediaTypes::parseMediaType($mediaType);
        if (!isset($mediaTypeParts['subtype']) || $mediaTypeParts['subtype'] === '') {
            return [];
        }
        $result = [];
        switch ($mediaTypeParts['subtype']) {
            case 'json':
            case 'x-json':
            case 'javascript':
            case 'x-javascript':
                $result = json_decode($requestBody, true);
                if ($result === null) {
                    return [];
                }
            break;
            case 'xml':
                $entityLoaderValue = libxml_disable_entity_loader(true);
                try {
                    $xmlElement = new \SimpleXMLElement(urldecode($requestBody), LIBXML_NOERROR);
                    libxml_disable_entity_loader($entityLoaderValue);
                } catch (\Exception $exception) {
                    libxml_disable_entity_loader($entityLoaderValue);
                    return [];
                }
                $result = Arrays::convertObjectToArray($xmlElement);
            break;
            case 'x-www-form-urlencoded':
            default:
                parse_str($requestBody, $result);
            break;
        }
        return $result;
    }
}
