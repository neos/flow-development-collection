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
use TYPO3\Flow\Error\Error;

/**
 * Converter which transforms to an integer.
 *
 * * If the source is an integer, it is returned unchanged.
 * * If the source a numeric string, it is cast to integer
 * * If the source is a DateTime instance, the UNIX timestamp is returned
 *
 * @api
 * @Flow\Scope("singleton")
 */
class IntegerConverter extends AbstractTypeConverter {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('integer', 'string', 'DateTime');

	/**
	 * @var string
	 */
	protected $targetType = 'integer';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * Actually convert from $source to $targetType, in fact a noop here.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return integer|\TYPO3\Flow\Error\Error
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($source instanceof \DateTime) {
			return $source->format('U');
		}

		if ($source === NULL || strlen($source) === 0) {
			return NULL;
		}

		if (!is_numeric($source)) {
			return new Error('"%s" is not numeric.', 1332933658, array($source));
		}
		return (integer)$source;
	}
}
