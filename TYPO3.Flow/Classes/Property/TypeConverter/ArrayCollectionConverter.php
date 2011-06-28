<?php
namespace TYPO3\FLOW3\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Converter which transforms simple types to a Doctrine ArrayCollection.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope singleton
 * @todo Implement functionality for converting collection properties.
 */
class ArrayCollectionConverter extends \TYPO3\FLOW3\Property\TypeConverter\AbstractTypeConverter {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('string', 'array');

	/**
	 * @var string
	 */
	protected $targetType = 'Doctrine\Common\Collections\ArrayCollection';

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
	 * @param \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		return new \Doctrine\Common\Collections\ArrayCollection($convertedChildProperties);
	}

	/**
	 * Returns the source, if it is an array, otherwise an empty array.
	 *
	 * @return array
	 * @api
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
		if (is_array($source)) {
			return $source;
		}
		return array();
	}

	/**
	 * Return the type of a given sub-property inside the $targetType
	 *
	 * @param string $targetType
	 * @param string $propertyName
	 * @param \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @api
	 */
	public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration) {
		$parsedTargetType = \TYPO3\FLOW3\Utility\TypeHandling::parseType($targetType);
		return $parsedTargetType['elementType'];
	}
}
?>