<?php
namespace TYPO3\Flow\Property\TypeConverter;

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
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\MediaTypes;

/**
 * Converter which transforms strings to arrays using the configured strategy.
 * This TypeConverter is used by default to decode the content of a HTTP request and it currently supports json and xml
 * based media types as well as urlencoded content.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class MediaTypeConverter extends AbstractTypeConverter implements MediaTypeConverterInterface {

	/**
	 * @var string
	 */
	protected $sourceTypes = array('string');

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
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), PropertyMappingConfigurationInterface $configuration = NULL) {
		$mediaType = NULL;
		if ($configuration !== NULL) {
			$mediaType = $configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\MediaTypeConverterInterface', MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE);
		}
		if ($mediaType === NULL) {
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
	protected function convertMediaType($requestBody, $mediaType) {
		$mediaTypeParts = MediaTypes::parseMediaType($mediaType);
		if (!isset($mediaTypeParts['subtype']) || $mediaTypeParts['subtype'] === '') {
			return array();
		}
		$result = array();
		switch ($mediaTypeParts['subtype']) {
			case 'json':
			case 'x-json':
			case 'javascript':
			case 'x-javascript':
				$result = json_decode($requestBody, TRUE);
				if ($result === NULL) {
					return array();
				}
			break;
			case 'xml':
				try {
					$xmlElement = new \SimpleXMLElement(urldecode($requestBody), LIBXML_NOERROR);
				} catch (\Exception $e) {
					return array();
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