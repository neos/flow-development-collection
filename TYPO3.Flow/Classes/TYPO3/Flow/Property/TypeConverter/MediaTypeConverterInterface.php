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
use TYPO3\Flow\Property\TypeConverterInterface;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\MediaTypes;

/**
 * A marker interface for type converters that are used to decode the content of a HTTP request
 *
 * To extend the default media type conversion of HTTP requests, this interface can be implemented by a custom TypeConverter
 * and then set as default implementation via Objects.yaml:
 *
 * 'TYPO3\Flow\Property\TypeConverter\MediaTypeConverterInterface'.className: Some\Custom\TypeConverter
 *
 * @api
 */
interface MediaTypeConverterInterface extends TypeConverterInterface {

	/**
	 * Name of the configuration option that contains the expected media type. This is usually set by the ActionRequest and
	 * corresponds to the browser's Content-Type header
	 *
	 * @var string
	 */
	const CONFIGURATION_MEDIA_TYPE = 'mediaType';

	/**
	 * The default media type that should be used if no explicit media type was configured (see CONFIGURATION_MEDIA_TYPE)
	 *
	 * @var string
	 */
	const DEFAULT_MEDIA_TYPE = 'application/json';
}