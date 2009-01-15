<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package;

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
 * @subpackage Package
 * @version $Id$
 */

/**
 * The default TYPO3 Package implementation
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Package implements PackageInterface {

	const PATTERN_MATCH_PACKAGEKEY = '/^[A-Z][A-Za-z0-9]+$/';

	const DIRECTORY_CLASSES = 'Classes/';
	const DIRECTORY_CONFIGURATION = 'Configuration/';
	const DIRECTORY_DOCUMENTATION = 'Documentation/';
	const DIRECTORY_META = 'Meta/';
	const DIRECTORY_RESOURCES = 'Resources/';

	const FILENAME_PACKAGEINFO = 'Package.xml';
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
	 * @var \F3\FLOW3\Package\Meta Meta information about this package
	 */
	protected $packageMeta;

	/**
	 * @var array Names and relative paths (to this package directory) of files containing classes
	 */
	protected $classFiles;

	/**
	 * Constructor
	 *
	 * @param string $packageKey Key of this package
	 * @param string $packagePath Absolute path to the package's main directory
	 * @throws \F3\FLOW3\Package\Exception\InvalidPackageKey if an invalid package key was passed
	 * @throws \F3\FLOW3\Package\Exception\InvalidPackagePath if an invalid package path was passed
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($packageKey, $packagePath) {
		if (!preg_match(self::PATTERN_MATCH_PACKAGEKEY, $packageKey)) throw new \F3\FLOW3\Package\Exception\InvalidPackageKey('"' . $packageKey . '" is not a valid package key.', 1217959510);
		if (!@is_dir($packagePath)) throw new \F3\FLOW3\Package\Exception\InvalidPackagePath('Package path does not exist or is no directory.', 1166631889);
		if (substr($packagePath, -1, 1) != '/') throw new \F3\FLOW3\Package\Exception\InvalidPackagePath('Package path has no trailing forward slash.', 1166633720);

		$this->packageKey = $packageKey;
		$this->packagePath = $packagePath;
		$this->packageMeta = new \F3\FLOW3\Package\Meta($packagePath . self::DIRECTORY_META . self::FILENAME_PACKAGEINFO);
	}

	/**
	 * Returns the package meta object of this package.
	 *
	 * @return \F3\FLOW3\Package\Meta
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
	 * Builds and returns an array of class names => file names of all
	 * F3_*.php files in the package's Classes directory and its sub-
	 * directories.
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\Package\Exception if recursion into directories was too deep or another error occurred
	 */
	protected function buildArrayOfClassFiles($subDirectory='', $recursionLevel=0) {
		$classFiles = array();
		$currentPath = $this->packagePath . self::DIRECTORY_CLASSES . $subDirectory;

		if (!is_dir($currentPath)) return array();
		if ($recursionLevel > 100) throw new \F3\FLOW3\Package\Exception('Recursion too deep.', 1166635495);

		try {
			$classesDirectoryIterator = new \DirectoryIterator($currentPath);
			while ($classesDirectoryIterator->valid()) {
				$filename = $classesDirectoryIterator->getFilename();
				if ($filename{0} != '.') {
					if (is_dir($currentPath . $filename)) {
						$classFiles = array_merge($classFiles, $this->buildArrayOfClassFiles($subDirectory . $filename . '/', ($recursionLevel+1)));
					} else {
						if (substr($filename, 0, 3) == 'F3_' && substr($filename, -4, 4) == '.php') {
							$classFiles[str_replace('_', '\\', substr($filename, 0, -4))] = $subDirectory . $filename;
						}
					}
				}
				$classesDirectoryIterator->next();
			}

		} catch(\Exception $exception) {
			throw new \F3\FLOW3\Package\Exception($exception->getMessage(), 1166633720);
		}
		return $classFiles;
	}
}

?>