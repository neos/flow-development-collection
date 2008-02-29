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
 * The default TYPO3 Package implementation
 *
 * @package    FLOW3
 * @subpackage Package
 * @version    $Id:T3_FLOW3_Package_.php 203 2007-03-30 13:17:37Z robert $
 * @copyright  Copyright belongs to the respective authors
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Package_Package implements T3_FLOW3_Package_PackageInterface {

	const DIRECTORY_CLASSES = 'Classes/';
	const DIRECTORY_CONFIGURATION = 'Configuration/';
	const DIRECTORY_DOCUMENTATION = 'Documentation/';
	const DIRECTORY_META = 'Meta/';
	const DIRECTORY_RESOURCES = 'Resources/';

	const FILENAME_PACKAGEINFO = 'PackageInfo.xml';
	const FILENAME_PACKAGECONFIGURATION = 'Package.php';

	/**
	 * @var string Unique key of this package
	 */
	protected $packageKey;

	/**
	 * @var string Full path to this package's main directory
	 */
	protected $packagePath;

	/**
	 * @var T3_FLOW3_Package_Meta Meta information about this package
	 */
	protected $packageMeta;

	/**
	 * @var T3_FLOW3_Package_ComponentsConfigurationSourceInterface	$packageComponentsConfigurationSources: An array of component configuration source objects which deliver the components configuration for this package
	 */
	protected $packageComponentsConfigurationSources;

	/**
	 * @var array Names and relative paths (to this package directory) of files containing classes
	 */
	protected $classFiles;

	/**
	 * Constructor
	 *
	 * @param  string $packageKey: Key of this package
	 * @param  string $packagePath: Absolute path to the package's main directory
	 * @param  array $packageComponentsConfigurationSources: An array of component configuration source objects which deliver the components configuration for this package
	 * @throws T3_FLOW3_Package_Exception_InvalidPackagePath if an invalid package path was passed
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($packageKey, $packagePath, $packageComponentsConfigurationSources) {
		if (!@is_dir($packagePath)) throw new T3_FLOW3_Package_Exception_InvalidPackagePath('Package path does not exist or is no directory.', 1166631889);
		if (substr($packagePath, -1, 1) != '/') throw new T3_FLOW3_Package_Exception_InvalidPackagePath('Package path has no trailing forward slash.', 1166633720);

		$this->packageKey = $packageKey;
		$this->packagePath = $packagePath;
		$this->packageMeta = new T3_FLOW3_Package_Meta($packagePath . self::DIRECTORY_META . self::FILENAME_PACKAGEINFO);
		$this->packageComponentsConfigurationSources = $packageComponentsConfigurationSources;
		$this->includePackageConfiguration();
	}

	/**
	 * Returns the package meta object of this package.
	 *
	 * @return T3_FLOW3_Package_Meta
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPackageMeta() {
		return $this->packageMeta;
	}

	/**
	 * Returns the array of filenames of the class files
	 *
	 * @return array An array of class names (key) and their filename, including the relative path to the package's directory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassFiles() {
		if (!is_array($this->classFiles)) {
			$this->classFiles = $this->buildArrayOfClassFiles();
		}
		return $this->classFiles;
	}

	/**
	 * Returns the package key of this package.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPackageKey() {
		return $this->packageKey;
	}

	/**
	 * Returns the full path to this package's main directory
	 *
	 * @return string Path to this package's main directory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPackagePath() {
		return $this->packagePath;
	}

	/**
	 * Returns the full path to this package's Classes directory
	 *
	 * @return string Path to this package's Classes directory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassesPath() {
		return $this->packagePath . self::DIRECTORY_CLASSES;
	}

	/**
	 * Returns the full path to this package's Resources directory
	 *
	 * @return string Path to this package's Resources directory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getResourcesPath() {
		return $this->packagePath . self::DIRECTORY_RESOURCES;
	}

	/**
	 * Returns the full path to this package's Configuration directory
	 *
	 * @return string Path to this package's Configuration directory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfigurationPath() {
		return $this->packagePath . self::DIRECTORY_CONFIGURATION;
	}

	/**
	 * Returns the component configurations which were defined by this package.
	 * The configuration may be delivered by different sources. The order of
	 * the configuration sources determines which configuration survives as they
	 * are merged with the configuration of the previous source.
	 *
	 * @return array Array of component names and T3_FLOW3_Component_Configuration
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo   Merge configuration of different sources
	 */
	public function getComponentConfigurations() {
		$componentConfigurations = array();
		foreach ($this->packageComponentsConfigurationSources as $packageComponentsConfigurationSource) {
			$componentConfigurations = $packageComponentsConfigurationSource->getComponentConfigurations($this, $componentConfigurations);
		}
		return $componentConfigurations;
	}

	/**
	 * Includes the package configuration file (if any) with further steps to initialize
	 * the package (eg. registering an additional class loader).
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function includePackageConfiguration() {
		if (file_exists($this->packagePath . self::DIRECTORY_CONFIGURATION . self::FILENAME_PACKAGECONFIGURATION)) {
			include($this->packagePath . self::DIRECTORY_CONFIGURATION . self::FILENAME_PACKAGECONFIGURATION);
		}
	}

	/**
	 * Builds and returns an array of class names => file names of all
	 * T3_*.php files in the package's Classes directory and its sub-
	 * directories.
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws T3_FLOW3_Package_Exception if recursion into directories was too deep or another error occurred
	 */
	protected function buildArrayOfClassFiles($subDirectory='', $recursionLevel=0) {
		$classFiles = array();
		$currentPath = $this->packagePath . self::DIRECTORY_CLASSES . $subDirectory;

		if (!is_dir($currentPath)) return array();
		if ($recursionLevel > 100) throw new T3_FLOW3_Package_Exception('Recursion too deep.', 1166635495);

		try {
			$classesDirectoryIterator = new DirectoryIterator($currentPath);
			while ($classesDirectoryIterator->valid()) {
				$filename = $classesDirectoryIterator->getFilename();
				if ($filename{0} != '.') {
					if (is_dir($currentPath . $filename)) {
						$classFiles = array_merge($classFiles, $this->buildArrayOfClassFiles($subDirectory . $filename . '/', ($recursionLevel+1)));
					} else {
						if (T3_PHP6_Functions::substr($filename, 0, 3) == 'T3_' && T3_PHP6_Functions::substr($filename, -4, 4) == '.php') {
							$classFiles[T3_PHP6_Functions::substr($filename, 0, -4)] = $subDirectory . $filename;
						}
					}
				}
				$classesDirectoryIterator->next();
			}

		} catch(Exception $exception) {
			throw new T3_FLOW3_Package_Exception($exception->getMessage(), 1166633720);
		}
		return $classFiles;
	}

	/**
	 * Wake up function
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __wakeup() {
		$this->includePackageConfiguration();
	}
}

?>