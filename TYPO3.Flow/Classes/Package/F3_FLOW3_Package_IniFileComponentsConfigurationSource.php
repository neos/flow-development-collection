<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Implementation of a components configuration source which parses Components.ini files
 *
 * @package    FLOW3
 * @subpackage Package
 * @version    $Id:F3_FLOW3_Package_IniFileComponentsConfigurationSource.php 203 2007-03-30 13:17:37Z robert $
 * @copyright  Copyright belongs to the respective authors
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Package_IniFileComponentsConfigurationSource implements F3_FLOW3_Package_ComponentsConfigurationSourceInterface {

	const FILENAME_COMPONENTSINI = 'Components.ini';

	/**
	 * @var F3_FLOW3_Package_PackageInterface Holds an interface of the package object requesting the components configuration
	 */
	protected $package;

	/**
	 * @var string The path and filename of the Components.ini file which is currently parsed.
	 */
	protected $componentsConfigurationPath = '';

	/**
	 * @var F3_FLOW3_AOP_Framework A reference to the AOP Framework
	 */
	protected $aopFramework;

	/**
	 * Constructor
	 *
	 * @param  F3_FLOW3_AOP_Framework $aopFramework: A reference to the AOP framework
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_AOP_Framework $aopFramework) {
		$this->aopFramework = $aopFramework;
	}

	/**
	 * Returns an array (indexed by component names) of component configuration
	 * objects for the classes of the given package from the Components.ini file
	 * if it exists. If it doesn't exist, an empty array will be returned.
	 *
	 * @param  F3_FLOW3_Package_PackageInterface $package: The package to return the components configuration for
	 * @param  array $parsedComponentConfigurations: An array of already existing component configurations (if any)
	 * @return array Array of component names and F3_FLOW3_Component_Configuration
	 * @throws F3_FLOW3_Package_Exception_InvalidComponentConfiguration if an error occured while reading the configuration file.
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo   Currently does not respect previously parsed configurations. That's no problem yet but might be one in the future.
	 */
	public function getComponentConfigurations(F3_FLOW3_Package_PackageInterface $package, $parsedComponentConfigurations) {
		$this->package = $package;
		$parsedComponentConfigurations = array();
		$componentsConfigurationPath = $package->getConfigurationPath() . self::FILENAME_COMPONENTSINI;
		$this->componentsConfigurationPath = $componentsConfigurationPath;

		if (is_file($componentsConfigurationPath)) {
			$unparsedConfigurationArray = parse_ini_file($componentsConfigurationPath, TRUE);
			if (!is_array($unparsedConfigurationArray)) throw new F3_FLOW3_Package_Exception_InvalidComponentConfiguration('Error while reading "' . self::FILENAME_COMPONENTSINI . '" file.', 1167574716);
			foreach ($unparsedConfigurationArray as $componentName => $componentConfigurationArray)  {
				if (is_array($componentConfigurationArray)) {
					$parsedComponentConfigurations[$componentName] = $this->transformConfigurationArrayToObject($componentName, $componentConfigurationArray);
				}
			}
		}
		return $parsedComponentConfigurations;
	}

	/**
	 * Transforms the configuration array read from the .conf file into a component
	 * configuration object
	 *
	 * @param  string $componentName: Name of the component the configuration is for
	 * @param  array $componentConfigurationArray: The unmodified configuration array as read from the .conf file
	 * @return F3_FLOW3_Component_Configuration The parsed component configuration as an object
	 * @throws F3_FLOW3_Package_Exception_InvalidComponentConfiguration if the configuration contained errors
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function transformConfigurationArrayToObject($componentName, $componentConfigurationArray) {
		$classFiles = $this->package->getClassFiles();
		$className = (isset($componentConfigurationArray['className']) ? $componentConfigurationArray['className'] : $componentName);
		$parsedComponentConfiguration = new F3_FLOW3_Component_Configuration($componentName, $className);
		$parsedComponentConfiguration->setConfigurationSourceHint($this->componentsConfigurationPath);

		foreach ($componentConfigurationArray as $optionName => $optionValue) {
			$optionValue = $this->parseOptionValue($optionValue);
			$optionNameParts = explode('.', $optionName);

			switch ($optionNameParts[0]) {
				case 'scope':
					$parsedComponentConfiguration->setScope($this->parseScope($optionValue));
				break;
				case 'properties':
					$propertyType = (isset($optionNameParts[2]) && $optionNameParts[2] == 'reference') ? F3_FLOW3_Component_ConfigurationProperty::PROPERTY_TYPES_REFERENCE : F3_FLOW3_Component_ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE;
					$property = new F3_FLOW3_Component_ConfigurationProperty($optionNameParts[1], $optionValue, $propertyType);
					$parsedComponentConfiguration->setProperty($property);
				break;
				case 'constructorArguments':
					if (intval($optionNameParts[1]) != $optionNameParts[1]) throw new F3_FLOW3_Package_Exception_InvalidComponentConfiguration('Index of constructor arguments must be of type integer', 1168006024);
					$constructorArgumentType = (isset($optionNameParts[2]) && $optionNameParts[2] == 'reference') ? F3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_REFERENCE : F3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE;
					$constructorArgument = new F3_FLOW3_Component_ConfigurationArgument(intval($optionNameParts[1]), $optionValue, $constructorArgumentType);
					$parsedComponentConfiguration->setConstructorArgument($constructorArgument);
				break;
				case 'className':
				case 'initializationMode':
				case 'lifecycleInitializationMethod':
				case 'lifecycleDestructionMethod':
					$methodName = 'set' . ucfirst($optionName);
					$parsedComponentConfiguration->$methodName(trim($optionValue));
				break;
				case 'AOPAddToProxyBlacklist':
					$this->aopFramework->addComponentNameToProxyBlacklist($componentName);
				break;
				case 'autoWiringMode':
					$methodName = 'set' . ucfirst($optionName);
					$parsedComponentConfiguration->$methodName($this->parseBoolean($optionValue));
				break;
				default:
					throw new F3_FLOW3_Package_Exception_InvalidComponentConfiguration('Invalid configuration option "' . $optionNameParts[0] . '" in file ' . $this->componentsConfigurationPath . '.', 1167574981);
			}
		}
		return $parsedComponentConfiguration;
	}

	/**
	 * Parses the value of the option "scope"
	 *
	 * @param  string $value: Value of the option
	 * @return integer The scope translated into a scope constant
	 * @throws F3_FLOW3_Package_Exception_InvalidComponentConfiguration if an invalid scope has been specified
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseScope($value) {
		if (!in_array($value, array('singleton', 'prototype', 'session'))) throw new F3_FLOW3_Package_Exception_InvalidComponentConfiguration('Invalid scope', 1167574991);
		return $value;
	}

	/**
	 * Parses a value which is expected to be boolean
	 *
	 * @param  string $value: Value of the option
	 * @return boolean The value as boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseBoolean($value) {
		switch (strtolower(trim($value))) {
			case 'on' :
			case 'true' :
			case '1' :
				$returnValue = TRUE;
			default :
				$returnValue = FALSE;
		}
		return $returnValue;
	}

	/**
	 * Guesses and converts the type of the option value. Currently it can only
	 * determine between string and numeric values.
	 *
	 * @param   string $optionValue: Value of the option
	 * @return  mixed The (possibly) type converted value
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function parseOptionValue($optionValue) {
		if (is_numeric($optionValue)) settype($optionValue, 'integer');
		return $optionValue;
	}
}
?>