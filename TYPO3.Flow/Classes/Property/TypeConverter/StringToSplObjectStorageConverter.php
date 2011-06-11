<?php
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
 * Converter which transforms a string to an SplObjectStorage, by simply creating an empty SplObjectStorage.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope singleton
 */
class StringToSplObjectStorageConverter extends \F3\FLOW3\Property\TypeConverter\AbstractTypeConverter {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('string');

	/**
	 * @var string
	 */
	protected $targetType = 'SplObjectStorage';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * Returns TRUE if the $source is an empty string.
	 *
	 * @param mixed $source the source data
	 * @param string $targetType the type to convert to.
	 * @return boolean TRUE if $source is an empty string, FALSE otherwise.
	 * @api
	 */
	public function canConvert($source, $targetType) {
		return $source === '';
	}

	/**
	 * Actually convert from $source to $targetType, taking into account the fully
	 * built $subProperties and $configuration.
	 *
	 * @param string $source
	 * @param string $targetType
	 * @param array $subProperties
	 * @param \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \SplObjectStorage
	 * @api
	 */
	public function convertFrom($source, $targetType, array $subProperties = array(), \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		return new \SplObjectStorage();
	}
}
?>