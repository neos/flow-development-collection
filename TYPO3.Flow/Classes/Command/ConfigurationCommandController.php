<?php
namespace TYPO3\FLOW3\Command;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Configuration command controller for the TYPO3.FLOW3 package
 *
 * @FLOW3\Scope("singleton")
 */
class ConfigurationCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * Show the active configuration settings
	 *
	 * The command shows the configuration of the current context as it is used by FLOW3 itself.
	 * You can specify the configuration type and path if you want to show parts of the configuration.
	 *
	 * ./flow3 configuration:show --type Settings --path TYPO3.FLOW3.persistence
	 *
	 * @param string $type Configuration type to show
	 * @param string $path path to subconfiguration separated by "." like "TYPO3.FLOW3"
	 * @return void
	 */
	public function showCommand($type = NULL, $path = NULL) {
		$availableConfigurationTypes = $this->configurationManager->getAvailableConfigurationTypes();
		if (in_array($type, $availableConfigurationTypes)){
			$configuration = $this->configurationManager->getConfiguration($type);
			if ($path !== NULL){
				$configuration = \TYPO3\FLOW3\Utility\Arrays::getValueByPath($configuration, $path);
			}
			if ($configuration === NULL){
				$this->outputLine('<b>Configuration "' . $type . ($path ? ': ' . $path : '') . '" was empty!</b>');
			} else {
				$yaml = \Symfony\Component\Yaml\Yaml::dump($configuration, 99);
				$this->outputLine('<b>Configuration "' . $type . ($path ? ': ' . $path : '') . '":</b>');
				$this->outputLine();
				$this->output($yaml . chr(10));
			}
		} else {
			if ($type !== NULL){
				$this->outputLine('<b>Configuration type "' . $type . '" was not found!</b>');
			}
			$this->outputLine('<b>Available configuration types:</b>');
			foreach ($availableConfigurationTypes as $availableConfigurationType){
				$this->outputLine('  ' . $availableConfigurationType);
			}
			$this->outputLine();
			$this->outputLine('Hint: <b>./flow3 configuration:show --type <configurationType></b>');
			$this->outputLine('      shows the configuration of the specified type.');
		}
	}
}

?>