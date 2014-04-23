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
use TYPO3\Flow\Utility\Arrays;

/**
 * Configuration source based on YAML files
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(FALSE)
 * @api
 */
class YamlSource {

	/**
	 * Will be set if the PHP YAML Extension is installed.
	 * Having this installed massively improves YAML parsing performance.
	 *
	 * @var boolean
	 * @see http://pecl.php.net/package/yaml
	 */
	protected $usePhpYamlExtension = FALSE;

	public function __construct() {
		if (extension_loaded('yaml')) {
			$this->usePhpYamlExtension = TRUE;
		}
	}

	/**
	 * Checks for the specified configuration file and returns TRUE if it exists.
	 *
	 * @param string $pathAndFilename Full path and filename of the file to load, excluding the file extension (ie. ".yaml")
	 * @param boolean $allowSplitSource If TRUE, the type will be used as a prefix when looking for configuration files
	 * @return boolean
	 */
	public function has($pathAndFilename, $allowSplitSource = FALSE) {
		if ($allowSplitSource === TRUE) {
			$pathsAndFileNames = glob($pathAndFilename . '.*.yaml');
			if ($pathsAndFileNames !== FALSE) {
				foreach ($pathsAndFileNames as $pathAndFilename) {
					if (file_exists($pathAndFilename)) {
						return TRUE;
					}
				}
			}
		}
		if (file_exists($pathAndFilename . '.yaml')) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Loads the specified configuration file and returns its content as an
	 * array. If the file does not exist or could not be loaded, an empty
	 * array is returned
	 *
	 * @param string $pathAndFilename Full path and filename of the file to load, excluding the file extension (ie. ".yaml")
	 * @param boolean $allowSplitSource If TRUE, the type will be used as a prefix when looking for configuration files
	 * @return array
	 * @throws \TYPO3\Flow\Configuration\Exception\ParseErrorException
	 */
	public function load($pathAndFilename, $allowSplitSource = FALSE) {
		$pathsAndFileNames = array($pathAndFilename . '.yaml');
		if ($allowSplitSource === TRUE) {
			$splitSourcePathsAndFileNames = glob($pathAndFilename . '.*.yaml');
			if ($splitSourcePathsAndFileNames !== FALSE) {
				sort($splitSourcePathsAndFileNames);
				$pathsAndFileNames = array_merge($pathsAndFileNames, $splitSourcePathsAndFileNames);
			}
		}
		$configuration = array();
		foreach ($pathsAndFileNames as $pathAndFilename) {
			if (file_exists($pathAndFilename)) {
				try {
					if ($this->usePhpYamlExtension) {
						$loadedConfiguration = @yaml_parse_file($pathAndFilename);
						if ($loadedConfiguration === FALSE) {
							throw new \TYPO3\Flow\Configuration\Exception\ParseErrorException('A parse error occurred while parsing file "' . $pathAndFilename . '".', 1391894094);
						}
					} else {
						$loadedConfiguration = \Symfony\Component\Yaml\Yaml::parse($pathAndFilename);
					}

					if (is_array($loadedConfiguration)) {
						$configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $loadedConfiguration);
					}
				} catch (\TYPO3\Flow\Error\Exception $exception) {
					throw new \TYPO3\Flow\Configuration\Exception\ParseErrorException('A parse error occurred while parsing file "' . $pathAndFilename . '". Error message: ' . $exception->getMessage(), 1232014321);
				}
			}
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
		$yaml = \Symfony\Component\Yaml\Yaml::dump($configuration, 99, 2);
		file_put_contents($pathAndFilename . '.yaml', $header . chr(10) . $yaml);
	}

	/**
	 * Read the header part from the given file. That means, every line
	 * until the first non comment line is found.
	 *
	 * @param string $pathAndFilename
	 * @return string The header of the given YAML file
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
