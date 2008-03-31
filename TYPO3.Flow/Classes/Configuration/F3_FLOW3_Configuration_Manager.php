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
 * @package FLOW3
 * @subpackage Configuration
 */

require_once(FLOW3_PATH_FLOW3 . 'Configuration/F3_FLOW3_Configuration_Container.php');
require_once(FLOW3_PATH_FLOW3 . 'Configuration/F3_FLOW3_Configuration_SourceInterface.php');
require_once(FLOW3_PATH_FLOW3 . 'Configuration/Source/F3_FLOW3_Configuration_Source_PHP.php');

/**
 * A general purpose configuration manager
 *
 * @package FLOW3
 * @subpackage Configuration
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Configuration_Manager{

	const CONFIGURATION_TYPE_FLOW3 = 'FLOW3';
	const CONFIGURATION_TYPE_PACKAGES = 'Packages';
	const CONFIGURATION_TYPE_COMPONENTS = 'Components';
	const CONFIGURATION_TYPE_SETTINGS = 'Settings';

	/**
	 * @var string The application context of the configuration to manage
	 */
	var $context;

	/**
	 * Constructs the configuration manager
	 *
	 * @param string $context The application context to fetch configuration for.
	 */
	public function __construct($context) {
		$this->context = $context;
	}

	/**
	 * Loads and returns the specified configuration. The actual configuration will be
	 * merged from different sources in a defined order.
	 *
	 * @param string $packageKey Key of the package the configuration is for
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @return F3_FLOW3_Configuration_Container The configuration
	 * @throws F3_FLOW3_Configuration_Exception_InvalidConfigurationType on invalid configuration types
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfiguration($packageKey, $configurationType) {
		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_FLOW3 :
				if ($packageKey != 'FLOW3')	throw new F3_FLOW3_Configuration_Exception_InvalidConfigurationType('Configuration type "' . $configurationType . ' is only allowed for package "FLOW3".', 1206031880);
			case self::CONFIGURATION_TYPE_PACKAGES :
			case self::CONFIGURATION_TYPE_COMPONENTS :
			case self::CONFIGURATION_TYPE_SETTINGS :
			break;
			default: throw new F3_FLOW3_Configuration_Exception_InvalidConfigurationType('Invalid configuration type "' . $configurationType . '"', 1206031879);
		}

		$configuration = F3_FLOW3_Configuration_Source_PHP::load(FLOW3_PATH_PACKAGES . $packageKey . '/Configuration/' . $configurationType . '.php');
		if (file_exists(FLOW3_PATH_CONFIGURATION . $configurationType . '.php')) {
			$additionalConfiguration = F3_FLOW3_Configuration_Source_PHP::load(FLOW3_PATH_CONFIGURATION . $configurationType . '.php');
			$configuration->mergeWith($additionalConfiguration);
		}
		if (file_exists(FLOW3_PATH_CONFIGURATION . $this->context . '/' . $configurationType . '.php')) {
			$additionalConfiguration = F3_FLOW3_Configuration_Source_PHP::load(FLOW3_PATH_CONFIGURATION . $this->context . '/' . $configurationType . '.php');
			$configuration->mergeWith($additionalConfiguration);
		}
		return $configuration;
	}

}
?>