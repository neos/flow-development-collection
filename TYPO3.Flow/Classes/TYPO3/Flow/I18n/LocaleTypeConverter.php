<?php
namespace TYPO3\Flow\I18n;

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
use TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter;

/**
 * Converter which transforms strings to a Locale object.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class LocaleTypeConverter extends AbstractTypeConverter {

	/**
	 * @var string
	 */
	protected $sourceTypes = array('string');

	/**
	 * @var string
	 */
	protected $targetType = 'TYPO3\Flow\I18n\Locale';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * Convert the given $source (a locale identifier string) to $targetType (Locale)
	 *
	 * @param string $source the locale string
	 * @param Locale $targetType
	 * @param array $convertedChildProperties
	 * @param PropertyMappingConfigurationInterface $configuration
	 * @return Locale
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), PropertyMappingConfigurationInterface $configuration = NULL) {
		return new Locale($source);
	}
}
