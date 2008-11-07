<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Configuration::Source;

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

/**
 * Configuration source based on YAML files
 *
 * @package FLOW3
 * @subpackage Configuration
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class YAML implements F3::FLOW3::Configuration::SourceInterface {

	/**
	 * Loads the specified configuration file and returns its content as an
	 * array. If the file does not exist or could not be loaded, an empty
	 * array is returned
	 *
	 * @param string $pathAndFilename Full path and file name of the file to load, excluding the file extension (ie. ".yaml")
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function load($pathAndFilename) {
		if (file_exists($pathAndFilename . '.yaml')) {
			$configuration = F3::YAML::YAML::loadFile($pathAndFilename . '.yaml');
		} else {
			$configuration = array();
		}
		return $configuration;
	}
}
?>