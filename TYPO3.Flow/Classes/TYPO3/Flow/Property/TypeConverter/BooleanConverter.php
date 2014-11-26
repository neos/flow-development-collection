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
 * Converter which transforms simple types to a boolean.
 *
 * For boolean this is a no-op, integer and float are simply typecast to boolean.
 *
 * Strings are converted to TRUE unless they are empry or match one of 'off', 'n', 'no', 'false' (case-insensitive).
 *
 * @api
 * @Flow\Scope("singleton")
 */
class BooleanConverter extends AbstractTypeConverter {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('boolean', 'string', 'integer', 'float');

	/**
	 * @var string
	 */
	protected $targetType = 'boolean';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * Actually convert from $source to $targetType
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return boolean
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if (is_bool($source)) {
			return $source;
		}

		if (is_int($source) || is_float(($source))) {
			return (boolean)$source;
		}

		return (!empty($source) && !in_array(strtolower($source), array('off', 'n', 'no', 'false')));
	}
}
