<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object\Configuration;

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
 * Object Configuration Builder which can build object configuration objects
 * from a generic configuration container.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ConfigurationBuilder {

	/**
	 * Builds an object configuration object from a generic configuration container.
	 *
	 * @param string $objectName Name of the object
	 * @param array configurationArray The configuration array with options for the object configuration
	 * @param string configurationSourceHint A human readable hint on the original source of the configuration (for troubleshooting)
	 * @param \F3\FLOW3\Object\Configuration\Configuration existingObjectConfiguration If set, this object configuration object will be used instead of creating a fresh one
	 * @return \F3\FLOW3\Object\Configuration\Configuration The object configuration object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function buildFromConfigurationArray($objectName, array $configurationArray, $configurationSourceHint = '', $existingObjectConfiguration = NULL) {
		$className = (isset($configurationArray['className']) ? $configurationArray['className'] : $objectName);
		$objectConfiguration = ($existingObjectConfiguration instanceof \F3\FLOW3\Object\Configuration\Configuration) ? $objectConfiguration = $existingObjectConfiguration : new \F3\FLOW3\Object\Configuration\Configuration($objectName, $className);
		$objectConfiguration->setConfigurationSourceHint($configurationSourceHint);

		foreach ($configurationArray as $optionName => $optionValue) {
			switch ($optionName) {
				case 'scope':
					$objectConfiguration->setScope(self::parseScope($optionValue));
				break;
				case 'properties':
					if (is_array($optionValue)) {
						foreach ($optionValue as $propertyName => $propertyValue) {
							if (isset($propertyValue['value'])) {
								$property = new \F3\FLOW3\Object\Configuration\ConfigurationProperty($propertyName, $propertyValue['value'], \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE);
							} elseif (isset($propertyValue['object'])) {
								$property = self::parsePropertyOfTypeObject($propertyName, $propertyValue['object'], $configurationSourceHint);
							} elseif (isset($propertyValue['setting'])) {
								$property = new \F3\FLOW3\Object\Configuration\ConfigurationProperty($propertyName, $propertyValue['setting'], \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_SETTING);
							} else {
								throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Invalid configuration syntax. Expecting "value", "object" or "setting" as value for property "' . $propertyName . '", instead found "' . (is_array($propertyValue) ? implode(', ', array_keys($propertyValue)) : $propertyValue) . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1230563249);
							}
							$objectConfiguration->setProperty($property);
						}
					}
				break;
				case 'arguments':
					if (is_array($optionValue)) {
						foreach ($optionValue as $argumentName => $argumentValue) {
							if (isset($argumentValue['value'])) {
								$argument = new \F3\FLOW3\Object\Configuration\ConfigurationArgument($argumentName, $argumentValue['value'], \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
							} elseif (isset($argumentValue['object'])) {
								$argument = self::parseArgumentOfTypeObject($argumentName, $argumentValue['object'], $configurationSourceHint);
							} elseif (isset($argumentValue['setting'])) {
								$argument = new \F3\FLOW3\Object\Configuration\ConfigurationArgument($argumentName, $argumentValue['setting'], \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_SETTING);
							} else {
								throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Invalid configuration syntax. Expecting "value", "object" or "setting" as value for argument "' . $argumentName . '", instead found "' . (is_array($argumentValue) ? implode(', ', array_keys($argumentValue)) : $argumentValue) . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1230563250);
							}
							$objectConfiguration->setArgument($argument);
						}
					}
				break;
				case 'className':
				case 'factoryObjectName' :
				case 'factoryMethodName' :
				case 'lifecycleInitializationMethodName':
				case 'lifecycleShutdownMethodName':
					$methodName = 'set' . ucfirst($optionName);
					$objectConfiguration->$methodName(trim($optionValue));
				break;
				case 'autowiring':
					$methodName = 'set' . ucfirst($optionName);
					$objectConfiguration->$methodName(self::parseAutowiring($optionValue));
				break;
				default:
					throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Invalid configuration option "' . $optionName . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1167574981);
			}
		}
		return $objectConfiguration;
	}

	/**
	 * Parses the value of the option "scope"
	 *
	 * @param  string $value Value of the option
	 * @return integer The scope translated into a scope constant
	 * @throws \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException if an invalid scope has been specified
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function parseScope($value) {
		switch ($value) {
			case 'singleton':
				return \F3\FLOW3\Object\Configuration\Configuration::SCOPE_SINGLETON;
			case 'prototype':
				return \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE;
			case 'session':
				return \F3\FLOW3\Object\Configuration\Configuration::SCOPE_SESSION;
			default:
				throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Invalid scope', 1167574991);
		}
	}

	/**
	 * Parses the value of the option "autowiring"
	 *
	 * @param  string $value Value of the option
	 * @return boolean The autowiring option translated into a boolean
	 * @throws \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException if an invalid option has been specified
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function parseAutowiring($value) {
		switch ($value) {
			case 'on':
			case \F3\FLOW3\Object\Configuration\Configuration::AUTOWIRING_MODE_ON:
				return \F3\FLOW3\Object\Configuration\Configuration::AUTOWIRING_MODE_ON;
			case 'off':
			case \F3\FLOW3\Object\Configuration\Configuration::AUTOWIRING_MODE_OFF:
				return \F3\FLOW3\Object\Configuration\Configuration::AUTOWIRING_MODE_OFF;
			default:
				throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Invalid autowiring declaration', 1283866757);
		}
	}

	/**
	 * Parses the configuration for properties of type OBJECT
	 *
	 * @param string $propertyName Name of the property
	 * @param mixed $objectNameOrConfiguration Value of the "object" section of the property configuration - either a string or an array
	 * @param string configurationSourceHint A human readable hint on the original source of the configuration (for troubleshooting)
	 * @return \F3\FLOW3\Object\Configuration\ConfigurationProperty A configuration property of type object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function parsePropertyOfTypeObject($propertyName, $objectNameOrConfiguration, $configurationSourceHint) {
		if (is_array($objectNameOrConfiguration)) {
			if (isset($objectNameOrConfiguration['name'])) {
				$objectName = $objectNameOrConfiguration['name'];
				unset($objectNameOrConfiguration['name']);
			} else {
				$objectName = NULL;
			}
			$objectConfiguration = self::buildFromConfigurationArray($objectName, $objectNameOrConfiguration, $configurationSourceHint . ' / property "' . $propertyName .'"');
			$property = new \F3\FLOW3\Object\Configuration\ConfigurationProperty($propertyName, $objectConfiguration, \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT);
		} else {
			$property = new \F3\FLOW3\Object\Configuration\ConfigurationProperty($propertyName, $objectNameOrConfiguration, \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT);
		}
		return $property;
	}

	/**
	 * Parses the configuration for arguments of type OBJECT
	 *
	 * @param string $argumentName Name of the argument
	 * @param mixed $objectNameOrConfiguration Value of the "object" section of the argument configuration - either a string or an array
	 * @param string configurationSourceHint A human readable hint on the original source of the configuration (for troubleshooting)
	 * @return \F3\FLOW3\Object\Configuration\ConfigurationArgument A configuration argument of type object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function parseArgumentOfTypeObject($argumentName, $objectNameOrConfiguration, $configurationSourceHint) {
		if (is_array($objectNameOrConfiguration)) {
			$objectName = $objectNameOrConfiguration['name'];
			unset($objectNameOrConfiguration['name']);
			$objectConfiguration = self::buildFromConfigurationArray($objectName, $objectNameOrConfiguration, $configurationSourceHint . ' / argument "' . $argumentName .'"');
			$argument = new \F3\FLOW3\Object\Configuration\ConfigurationArgument($argumentName,  $objectConfiguration, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
		} else {
			$argument = new \F3\FLOW3\Object\Configuration\ConfigurationArgument($argumentName,  $objectNameOrConfiguration, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
		}
		return $argument;
	}

}
?>