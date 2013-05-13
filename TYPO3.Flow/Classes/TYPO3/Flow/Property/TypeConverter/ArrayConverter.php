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
 * Converter which transforms strings and arrays to arrays.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ArrayConverter extends AbstractTypeConverter {

	/**
	 * @var string
	 */
	const CONFIGURATION_STRING_DELIMITER = 'stringDelimiter';

	/**
	 * @var string
	 */
	const DEFAULT_STRING_DELIMITER = ',';

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('array', 'string');

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
	 * If it is a string it will be exploded by the configured string delimiter.
	 *
	 * @param string|array $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return array
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if (is_string($source)) {
			if ($source === '') {
				return array();
			} else {
				return explode($this->getConfiguredStringDelimiter($configuration), $source);
			}
		}

		return $source;
	}

	/**
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @throws \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	protected function getConfiguredStringDelimiter(\TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($configuration === NULL) {
			return self::DEFAULT_STRING_DELIMITER;
		}
		$stringDelimiter = $configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\ArrayConverter', self::CONFIGURATION_STRING_DELIMITER);
		if ($stringDelimiter === NULL) {
			return self::DEFAULT_STRING_DELIMITER;
		} elseif (!is_string($stringDelimiter)) {
			throw new \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException('CONFIGURATION_STRING_DELIMITER must be of type string, "' . (is_object($stringDelimiter) ? get_class($stringDelimiter) : gettype($stringDelimiter)) . '" given', 1368433339);
		}
		return $stringDelimiter;
	}

}
?>