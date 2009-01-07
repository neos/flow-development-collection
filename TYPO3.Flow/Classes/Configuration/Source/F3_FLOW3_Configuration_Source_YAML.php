<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Configuration\Source;

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
 * @package FLOW3
 * @subpackage Configuration
 */

/**
 * Configuration source based on YAML files
 *
 * @package FLOW3
 * @subpackage Configuration
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class YAML implements \F3\FLOW3\Configuration\SourceInterface {

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
			$configuration = \F3\YAML\YAML::loadFile($pathAndFilename . '.yaml');
		} else {
			$configuration = array();
		}
		return $configuration;
	}
}
?>