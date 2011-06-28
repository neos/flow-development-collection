<?php
namespace TYPO3\FLOW3\Property;

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
 * This builder creates the default configuration for Property Mapping, if no configuration has been passed to the Property Mapper.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class PropertyMappingConfigurationBuilder {

	/**
	 * Builds the default property mapping configuration.
	 *
	 * @param string $type the implementation class name of the PropertyMappingConfiguration to instanciate; must be a subclass of TYPO3\FLOW3\Property\PropertyMappingConfiguration
	 * @return \TYPO3\FLOW3\Property\PropertyMappingConfiguration
	 */
	public function build($type = 'TYPO3\FLOW3\Property\PropertyMappingConfiguration') {
		$configuration = new $type();

		$configuration->setTypeConverterOptions('TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter', array(
			\TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE,
			\TYPO3\FLOW3\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => TRUE
		));

		return $configuration;
	}
}
?>