<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property\TypeConverter;

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
 * Converter which transforms an array to a Doctrine ArrayCollection.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope singleton
 * @todo Implement functionality for converting collection properties.
 */
class ArrayCollectionConverter extends \F3\FLOW3\Property\TypeConverter\AbstractTypeConverter {

	protected $sourceTypes = array('string', 'array');
	protected $targetType = 'Doctrine\Common\Collections\ArrayCollection';
	protected $priority = 1;

	public function convertFrom($source, $targetType, array $subProperties = array(), \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		return new \Doctrine\Common\Collections\ArrayCollection($subProperties);
	}

	public function getProperties($source) {
		if (is_array($source)) {
			return $source;
		}
		return array();
	}

	/**
	 * This method is never called, as getProperties() returns an empty array.
	 *
	 * @param string $targetType
	 * @param string $propertyName
	 * @param \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return array<string>
	 * @api
	 */
	public function getTypeOfProperty($targetType, $propertyName, \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration) {
		$subPropertyType = rtrim(substr($targetType, strpos($targetType, '<')+1), '>');
		return $subPropertyType;
	}
}
?>