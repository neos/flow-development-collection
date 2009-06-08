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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class YAMLSource implements \F3\FLOW3\Configuration\Source\WritableSourceInterface {

	/**
	 * Loads the specified configuration file and returns its content as an
	 * array. If the file does not exist or could not be loaded, an empty
	 * array is returned
	 *
	 * @param string $pathAndFilename Full path and file name of the file to load, excluding the file extension (ie. ".yaml")
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function load($pathAndFilename) {
		if (file_exists($pathAndFilename . '.yaml')) {
			try {
				$configuration = \F3\YAML\YAML::loadFile($pathAndFilename . '.yaml');
			} catch (\F3\FLOW3\Error\Exception $exception) {
				throw new \F3\FLOW3\Configuration\Exception\ParseError('A parse error occurred while parsing file "' . $pathAndFilename . '.yaml". Error message: ' . $exception->getMessage(), 1232014321);
			}
		} else {
			$configuration = array();
		}
		return $configuration;
	}

	/**
	 * Save the specified configuration array to the given file in YAML format.
	 *
	 * @param string $pathAndFilename Full path and file name of the file to write to, excluding the dot and file extension (i.e. ".yaml")
	 * @param array $configuration The configuration to save
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @internal
	 */
	public function save($pathAndFilename, $configuration) {
		$header = '';
		if (file_exists($pathAndFilename . '.yaml')) {
			$header = $this->getHeaderFromFile($pathAndFilename . '.yaml');
		}
		$yaml = \F3\YAML\YAML::dump($configuration);
		file_put_contents($pathAndFilename, $header . PHP_EOL . $yaml);
	}

	/**
	 * Read the header part from the given file. That means, every line
	 * until the first non comment or space is found at the beginning.
	 * 
	 * @param string $pathAndFilename
	 * @return string The header of the given YAML file
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function getHeaderFromFile($pathAndFilename) {
		$header = '';
		$line = '';
		$fileHandle = fopen($pathAndFilename, 'r');
		while ($line = fgets($fileHandle)) {
			if (preg_match('/^#|\s*/', $line)) {
				$header .= $line;
			} else {
				break;
			}
		}
		fclose($fileHandle);
		return $header;
	}
}
?>