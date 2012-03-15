<?php
namespace TYPO3\FLOW3\Package;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Package\MetaData\XmlReader as PackageMetaDataReader;

/**
 * A Package
 *
 * @api
 */
class Package implements PackageInterface {

	/**
	 * Unique key of this package. Example for the FLOW3 package: "TYPO3.FLOW3"
	 * @var string
	 */
	protected $packageKey;

	/**
	 * Full path to this package's main directory
	 * @var string
	 */
	protected $packagePath;

	/**
	 * If this package is protected and therefore cannot be deactivated or deleted
	 * @var boolean
	 * @api
	 */
	protected $protected = FALSE;

	/**
	 * Meta information about this package
	 * @var \TYPO3\FLOW3\Package\MetaData
	 */
	protected $packageMetaData;

	/**
	 * Names and relative paths (to this package directory) of files containing classes
	 * @var array
	 */
	protected $classFiles;

	/**
	 * If enabled, the files in the Classes directory are registered and Reflection, Dependency Injection, AOP etc. are supported.
	 * Disable this flag if you don't need object management for your package and want to save some memory.
	 * @var boolean
	 * @api
	 */
	protected $objectManagementEnabled = TRUE;

	/**
	 * Constructor
	 *
	 * @param string $packageKey Key of this package
	 * @param string $packagePath Absolute path to the package's main directory
	 * @throws \TYPO3\FLOW3\Package\Exception\InvalidPackageKeyException if an invalid package key was passed
	 * @throws \TYPO3\FLOW3\Package\Exception\InvalidPackagePathException if an invalid package path was passed
	 */
	public function __construct($packageKey, $packagePath) {
		if (preg_match(self::PATTERN_MATCH_PACKAGEKEY, $packageKey) !== 1) throw new \TYPO3\FLOW3\Package\Exception\InvalidPackageKeyException('"' . $packageKey . '" is not a valid package key.', 1217959510);
		if (!(is_dir($packagePath) || (\TYPO3\FLOW3\Utility\Files::is_link($packagePath) && is_dir(realpath(rtrim($packagePath, '/')))))) throw new \TYPO3\FLOW3\Package\Exception\InvalidPackagePathException('Package path does not exist or is no directory.', 1166631889);
		if (substr($packagePath, -1, 1) != '/') throw new \TYPO3\FLOW3\Package\Exception\InvalidPackagePathException('Package path has no trailing forward slash.', 1166633720);

		$this->packageKey = $packageKey;
		$this->packagePath = $packagePath;
	}

	/**
	 * Invokes custom PHP code directly after the package manager has been initialized.
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\TYPO3\FLOW3\Core\Bootstrap $bootstrap) {
	}

	/**
	 * Returns the package meta data object of this package.
	 *
	 * @return \TYPO3\FLOW3\Package\MetaData
	 */
	public function getPackageMetaData() {
		if ($this->packageMetaData === NULL) {
			$this->packageMetaData = PackageMetaDataReader::readPackageMetaData($this);
		}
		return $this->packageMetaData;
	}

	/**
	 * Returns the array of filenames of the class files
	 *
	 * @return array An array of class names (key) and their filename, including the relative path to the package's directory
	 */
	public function getClassFiles() {
		if (!is_array($this->classFiles)) {
			$this->classFiles = $this->buildArrayOfClassFiles($this->packagePath . self::DIRECTORY_CLASSES);
		}
		return $this->classFiles;
	}

	/**
	 * Returns the array of filenames of class files provided by functional tests contained in this package
	 *
	 * @return array An array of class names (key) and their filename, including the relative path to the package's directory
	 */
	public function getFunctionalTestsClassFiles() {
		return $this->buildArrayOfClassFiles($this->packagePath . self::DIRECTORY_TESTS_FUNCTIONAL, 'Tests\\Functional\\');
	}

	/**
	 * Returns the package key of this package.
	 *
	 * @return string
	 * @api
	 */
	public function getPackageKey() {
		return $this->packageKey;
	}

	/**
	/**
	 * Returns the PHP namespace of classes in this package.
	 *
	 * @return string
	 * @api
	 */
	public function getPackageNamespace() {
		return str_replace('.', '\\', $this->packageKey);
	}

	/**
	 * Tells if this package is protected and therefore cannot be deactivated or deleted
	 *
	 * @return boolean
	 * @api
	 */
	public function isProtected() {
		return $this->protected;
	}

	/**
	 * Tells if files in the Classes directory should be registered and object management enabled for this package.
	 *
	 * @return boolean
	 */
	public function isObjectManagementEnabled() {
		return $this->objectManagementEnabled;
	}

	/**
	 * Sets the protection flag of the package
	 *
	 * @param boolean $protected TRUE if the package should be protected, otherwise FALSE
	 * @return void
	 * @api
	 */
	public function setProtected($protected) {
		$this->protected = (boolean)$protected;
	}

	/**
	 * Returns the full path to this package's main directory
	 *
	 * @return string Path to this package's main directory
	 * @api
	 */
	public function getPackagePath() {
		return $this->packagePath;
	}

	/**
	 * Returns the full path to this package's Classes directory
	 *
	 * @return string Path to this package's Classes directory
	 * @api
	 */
	public function getClassesPath() {
		return $this->packagePath . self::DIRECTORY_CLASSES;
	}

	/**
	 * Returns the full path to this package's functional tests directory
	 *
	 * @return string Path to this package's functional tests directory
	 * @api
	 */
	public function getFunctionalTestsPath() {
		return $this->packagePath . self::DIRECTORY_TESTS_FUNCTIONAL;
	}

	/**
	 * Returns the full path to this package's Resources directory
	 *
	 * @return string Path to this package's Resources directory
	 * @api
	 */
	public function getResourcesPath() {
		return $this->packagePath . self::DIRECTORY_RESOURCES;
	}

	/**
	 * Returns the full path to this package's Configuration directory
	 *
	 * @return string Path to this package's Configuration directory
	 * @api
	 */
	public function getConfigurationPath() {
		return $this->packagePath . self::DIRECTORY_CONFIGURATION;
	}

	/**
	 * Returns the full path to the package's meta data directory
	 *
	 * @return string Full path to the package's meta data directory
	 * @api
	 */
	public function getMetaPath() {
		return $this->packagePath . self::DIRECTORY_METADATA;
	}

	/**
	 * Returns the full path to the package's documentation directory
	 *
	 * @return string Full path to the package's documentation directory
	 * @api
	 */
	public function getDocumentationPath() {
		return $this->packagePath . self::DIRECTORY_DOCUMENTATION;
	}

	/**
	 * Returns the available documentations for this package
	 *
	 * @return array Array of \TYPO3\FLOW3\Package\Documentation
	 * @api
	 */
	public function getPackageDocumentations() {
		$documentations = array();
		$documentationPath = $this->getDocumentationPath();
		if (is_dir($documentationPath)) {
			$documentationsDirectoryIterator = new \DirectoryIterator($documentationPath);
			$documentationsDirectoryIterator->rewind();
			while ($documentationsDirectoryIterator->valid()) {
				$filename = $documentationsDirectoryIterator->getFilename();
				if ($filename[0] != '.' && $documentationsDirectoryIterator->isDir()) {
					$filename = $documentationsDirectoryIterator->getFilename();
					$documentation = new \TYPO3\FLOW3\Package\Documentation($this, $filename, $documentationPath . $filename . '/');
					$documentations[$filename] = $documentation;
				}
				$documentationsDirectoryIterator->next();
			}
		}
		return $documentations;
	}

	/**
	 * Builds and returns an array of class names => file names of all
	 * *.php files in the package's Classes directory and its sub-
	 * directories.
	 *
	 * @param string $classesPath Base path acting as the parent directory for potential class files
	 * @param string $extraNamespaceSegment A PHP class namespace segment which should be inserted like so: \TYPO3\PackageKey\{namespacePrefix\}PathSegment\PathSegment\Filename
	 * @param string $subDirectory Used internally
	 * @param integer $recursionLevel Used internally
	 * @return array
	 * @throws \TYPO3\FLOW3\Package\Exception if recursion into directories was too deep or another error occurred
	 */
	protected function buildArrayOfClassFiles($classesPath, $extraNamespaceSegment = '', $subDirectory = '', $recursionLevel = 0) {
		$packageNamespace = $this->getPackageNamespace();
		$classFiles = array();
		$currentPath = $classesPath . $subDirectory;
		$currentRelativePath = substr($currentPath, strlen($this->packagePath));

		if (!is_dir($currentPath)) return array();
		if ($recursionLevel > 100) throw new \TYPO3\FLOW3\Package\Exception('Recursion too deep.', 1166635495);

		try {
			$classesDirectoryIterator = new \DirectoryIterator($currentPath);
			while ($classesDirectoryIterator->valid()) {
				$filename = $classesDirectoryIterator->getFilename();
				if ($filename[0] != '.') {
					if (is_dir($currentPath . $filename)) {
						$classFiles = array_merge($classFiles, $this->buildArrayOfClassFiles($classesPath, $extraNamespaceSegment, $subDirectory . $filename . '/', ($recursionLevel+1)));
					} else {
						if (substr($filename, -4, 4) === '.php') {
							$className = (str_replace('/', '\\', ($packageNamespace . '/' . $extraNamespaceSegment . substr($currentPath, strlen($classesPath)) . substr($filename, 0, -4))));
							$classFiles[$className] = $currentRelativePath . $filename;
						}
					}
				}
				$classesDirectoryIterator->next();
			}

		} catch(\Exception $exception) {
			throw new \TYPO3\FLOW3\Package\Exception($exception->getMessage(), 1166633720);
		}
		return $classFiles;
	}
}

?>