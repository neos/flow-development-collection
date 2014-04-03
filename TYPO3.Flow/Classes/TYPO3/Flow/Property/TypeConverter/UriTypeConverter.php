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

/**
 * A type converter for converting URI strings to Http Uri objects
 *
 * @Flow\Scope("singleton")
 */
class UriTypeConverter extends \TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('string');

	/**
	 * @var string
	 */
	protected $targetType = 'TYPO3\Flow\Http\Uri';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * Converts the given string to a Uri object.
	 *
	 * @param string $source The URI to be converted
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \TYPO3\Flow\Http\Uri|\TYPO3\Flow\Error\Error if the input format is not supported or could not be converted for other reasons
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		try {
			return new \TYPO3\Flow\Http\Uri($source);
		} catch (\InvalidArgumentException $exception) {
			return new \TYPO3\Flow\Error\Error('The given URI "%s" could not be converted', 1351594881, array($source));
		}
	}
}
