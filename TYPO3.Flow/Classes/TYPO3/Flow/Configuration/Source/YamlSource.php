<?php
namespace TYPO3\Flow\Configuration\Source;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Configuration source based on YAML files
 *
 * @Flow\Scope("singleton")
 * @api
 */
class YamlSource implements \TYPO3\Flow\Configuration\Source\SourceInterface {

	/**
	 * Loads the specified configuration file and returns its content as an
	 * array. If the file does not exist or could not be loaded, an empty
	 * array is returned
	 *
	 * @param string $pathAndFilename Full path and filename of the file to load, excluding the file extension (ie. ".yaml")
	 * @return array
	 * @throws \TYPO3\Flow\Configuration\Exception\ParseErrorException
	 */
	public function load($pathAndFilename) {
		if (file_exists($pathAndFilename . '.yaml')) {
			try {
				$configuration = \Symfony\Component\Yaml\Yaml::parse($pathAndFilename . '.yaml');
				if (!is_array($configuration)) {
					$configuration = array();
				}
			} catch (\TYPO3\Flow\Error\Exception $exception) {
				throw new \TYPO3\Flow\Configuration\Exception\ParseErrorException('A parse error occurred while parsing file "' . $pathAndFilename . '.yaml". Error message: ' . $exception->getMessage(), 1232014321);
			}
		} else {
			$configuration = array();
		}
		return $configuration;
	}

	/**
	 * Save the specified configuration array to the given file in YAML format.
	 *
	 * @param string $pathAndFilename Full path and filename of the file to write to, excluding the dot and file extension (i.e. ".yaml")
	 * @param array $configuration The configuration to save
	 * @return void
	 */
	public function save($pathAndFilename, array $configuration) {
		$header = '';
		if (file_exists($pathAndFilename . '.yaml')) {
			$header = $this->getHeaderFromFile($pathAndFilename . '.yaml');
		}
		$yaml = \Symfony\Component\Yaml\Yaml::dump($configuration, 99);
		file_put_contents($pathAndFilename . '.yaml', $header . chr(10) . $yaml);
	}

	/**
	 * Read the header part from the given file. That means, every line
	 * until the first non comment line is found.
	 *
	 * @param string $pathAndFilename
	 * @return string The header of the given YAML file
	 * @api
	 */
	protected function getHeaderFromFile($pathAndFilename) {
		$header = '';
		$fileHandle = fopen($pathAndFilename, 'r');
		while ($line = fgets($fileHandle)) {
			if (preg_match('/^#/', $line)) {
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