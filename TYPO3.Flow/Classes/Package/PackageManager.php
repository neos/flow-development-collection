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

use TYPO3\FLOW3\Package\MetaData\XmlWriter as PackageMetaDataWriter;
use TYPO3\FLOW3\Package\Package;
use TYPO3\FLOW3\Package\PackageInterface;
use TYPO3\FLOW3\Utility\Files;

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The default TYPO3 Package Manager
 *
 * @api
 * @FLOW3\Scope("singleton")
 */
class PackageManager implements \TYPO3\FLOW3\Package\PackageManagerInterface {

	/**
	 * @var \TYPO3\FLOW3\Core\ClassLoader
	 */
	protected $classLoader;

	/**
	 * @var \TYPO3\FLOW3\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * Array of available packages, indexed by package key
	 * @var array
	 */
	protected $packages = array();

	/**
	 * A translation table between lower cased and upper camel cased package keys
	 * @var array
	 */
	protected $packageKeys = array();

	/**
	 * List of active packages as package key => package object
	 * @var array
	 */
	protected $activePackages = array();

	/**
	 * Absolute path leading to the various package directories
	 * @var string
	 */
	protected $packagesBasePath;

	/**
	 * @var string
	 */
	protected $packageStatesPathAndFilename;

	/**
	 * Package states configuration as stored in the PackageStates.php file
	 * @var array
	 */
	protected $packageStatesConfiguration = array();

	/**
	 * @var string
	 */
	protected $packageClassTemplateUri = 'resource://TYPO3.FLOW3/Private/Package/Package.php.tmpl';

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param \TYPO3\FLOW3\Core\ClassLoader $classLoader
	 * @return void
	 */
	public function injectClassLoader(\TYPO3\FLOW3\Core\ClassLoader $classLoader) {
		$this->classLoader = $classLoader;
	}

	/**
	 * Sets the URI specifying the file acting as a template for the Package class files of newly created packages.
	 *
	 * @param string $packageClassTemplateUri Full path and filename or other valid URI pointing to the template file
	 * @return void
	 */
	public function setPackageClassTemplateUri($packageClassTemplateUri) {
		$this->packageClassTemplateUri = $packageClassTemplateUri;
	}

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Initializes the package manager
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap The current bootstrap
	 * @param string $packagesBasePath Absolute path of the Packages directory
	 * @param string $packageStatesPathAndFilename
	 * @return void
	 */
	public function initialize(\TYPO3\FLOW3\Core\Bootstrap $bootstrap, $packagesBasePath = FLOW3_PATH_PACKAGES, $packageStatesPathAndFilename = '') {
		$this->bootstrap = $bootstrap;
		$this->packagesBasePath = $packagesBasePath;
		$this->packageStatesPathAndFilename = ($packageStatesPathAndFilename === '') ? FLOW3_PATH_CONFIGURATION . 'PackageStates.php' : $packageStatesPathAndFilename;

		$this->loadPackageStates();

		foreach ($this->packages as $packageKey => $package) {
			if ($package->isProtected() || (isset($this->packageStatesConfiguration['packages'][$packageKey]['state']) && $this->packageStatesConfiguration['packages'][$packageKey]['state'] === 'active')) {
				$this->activePackages[$packageKey] = $package;
			}
		}

		$this->classLoader->setPackages($this->activePackages);

		foreach ($this->activePackages as $package) {
			$package->boot($bootstrap);
		}

	}

	/**
	 * Returns TRUE if a package is available (the package's files exist in the packages directory)
	 * or FALSE if it's not. If a package is available it doesn't mean neccessarily that it's active!
	 *
	 * @param string $packageKey The key of the package to check
	 * @return boolean TRUE if the package is available, otherwise FALSE
	 * @api
	 */
	public function isPackageAvailable($packageKey) {
		return (isset($this->packages[$packageKey]));
	}

	/**
	 * Returns TRUE if a package is activated or FALSE if it's not.
	 *
	 * @param string $packageKey The key of the package to check
	 * @return boolean TRUE if package is active, otherwise FALSE
	 * @api
	 */
	public function isPackageActive($packageKey) {
		return (isset($this->activePackages[$packageKey]));
	}

	/**
	 * Returns a PackageInterface object for the specified package.
	 * A package is available, if the package directory contains valid MetaData information.
	 *
	 * @param string $packageKey
	 * @return \TYPO3\FLOW3\Package The requested package object
	 * @throws \TYPO3\FLOW3\Package\Exception\UnknownPackageException if the specified package is not known
	 * @api
	 */
	public function getPackage($packageKey) {
		if (!$this->isPackageAvailable($packageKey)) {
			throw new \TYPO3\FLOW3\Package\Exception\UnknownPackageException('Package "' . $packageKey . '" is not available. Please check if the package exists and that the package key is correct (package keys are case sensitive).', 1166546734);
		}
		return $this->packages[$packageKey];
	}

	/**
	 * Returns an array of \TYPO3\FLOW3\Package objects of all available packages.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @return array Array of \TYPO3\FLOW3\Package
	 * @api
	 */
	public function getAvailablePackages() {
		return $this->packages;
	}

	/**
	 * Returns an array of \TYPO3\FLOW3\Package objects of all active packages.
	 * A package is active, if it is available and has been activated in the package
	 * manager settings.
	 *
	 * @return array Array of \TYPO3\FLOW3\Package
	 * @api
	 */
	public function getActivePackages() {
		return $this->activePackages;
	}

	/**
	 * Returns an array of \TYPO3\FLOW3\Package objects of all frozen packages.
	 * A frozen package is not considered by file montoring and provides some
	 * precompiled reflection data in order to improve performance.
	 *
	 * @return array Array of \TYPO3\FLOW3\Package
	 */
	public function getFrozenPackages() {
		$frozenPackages = array();
		foreach ($this->packages as $packageKey => $package) {
			if (isset($this->packageStatesConfiguration['packages'][$packageKey]['frozen']) &&
					$this->packageStatesConfiguration['packages'][$packageKey]['frozen'] === TRUE) {
				$frozenPackages[$packageKey] = $package;
			}

		}
		return $frozenPackages;
	}

	/**
	 * Returns the upper camel cased version of the given package key or FALSE
	 * if no such package is available.
	 *
	 * @param string $unknownCasedPackageKey The package key to convert
	 * @return mixed The upper camel cased package key or FALSE if no such package exists
	 * @api
	 */
	public function getCaseSensitivePackageKey($unknownCasedPackageKey) {
		$lowerCasedPackageKey = strtolower($unknownCasedPackageKey);
		return (isset($this->packageKeys[$lowerCasedPackageKey])) ? $this->packageKeys[$lowerCasedPackageKey] : FALSE;
	}

	/**
	 * Check the conformance of the given package key
	 *
	 * @param string $packageKey The package key to validate
	 * @return boolean If the package key is valid, returns TRUE otherwise FALSE
	 * @api
	 */
	public function isPackageKeyValid($packageKey) {
		return preg_match(PackageInterface::PATTERN_MATCH_PACKAGEKEY, $packageKey) === 1;
	}

	/**
	 * Create a package, given the package key
	 *
	 * @param string $packageKey The package key of the new package
	 * @param \TYPO3\FLOW3\Package\MetaData $packageMetaData If specified, this package meta object is used for writing the Package.xml file, otherwise a rudimentary Package.xml file is created
	 * @param string $packagesPath If specified, the package will be created in this path, otherwise the default "Application" directory is used
	 * @return \TYPO3\FLOW3\Package\Package The newly created package
	 * @throws \TYPO3\FLOW3\Package\Exception
	 * @throws \TYPO3\FLOW3\Package\Exception\PackageKeyAlreadyExistsException
	 * @throws \TYPO3\FLOW3\Package\Exception\InvalidPackageKeyException
	 * @api
	 */
	public function createPackage($packageKey, \TYPO3\FLOW3\Package\MetaData $packageMetaData = NULL, $packagesPath = '') {
		if (!$this->isPackageKeyValid($packageKey)) throw new \TYPO3\FLOW3\Package\Exception\InvalidPackageKeyException('The package key "' . $packageKey . '" is invalid', 1220722210);
		if ($this->isPackageAvailable($packageKey)) throw new \TYPO3\FLOW3\Package\Exception\PackageKeyAlreadyExistsException('The package key "' . $packageKey . '" already exists', 1220722873);

		if ($packageMetaData === NULL) {
			$packageMetaData = new \TYPO3\FLOW3\Package\MetaData($packageKey);
		}

		if ($packagesPath === '') {
			$packagesPath = Files::getUnixStylePath(Files::concatenatePaths(array($this->packagesBasePath, 'Application')));
		}

		$packagePath = Files::concatenatePaths(array($packagesPath, $packageKey)) . '/';
		Files::createDirectoryRecursively($packagePath);

		foreach (
			array(
				PackageInterface::DIRECTORY_METADATA,
				PackageInterface::DIRECTORY_CLASSES,
				PackageInterface::DIRECTORY_CONFIGURATION,
				PackageInterface::DIRECTORY_DOCUMENTATION,
				PackageInterface::DIRECTORY_RESOURCES,
				PackageInterface::DIRECTORY_TESTS_UNIT,
				PackageInterface::DIRECTORY_TESTS_FUNCTIONAL,
			) as $path) {
			Files::createDirectoryRecursively(Files::concatenatePaths(array($packagePath, $path)));
		}

		$package = new Package($packageKey, $packagePath);
		$result = PackageMetaDataWriter::writePackageMetaData($package, $packageMetaData);
		if ($result === FALSE) throw new \TYPO3\FLOW3\Package\Exception('Error while writing the package meta data information at "' . $packagePath . '"', 1232625240);

		$packageNamespace = str_replace('.', '\\', $packageKey);
		$packagePhpSource = str_replace('{packageKey}', $packageKey, Files::getFileContents($this->packageClassTemplateUri));
		$packagePhpSource = str_replace('{packageNamespace}', $packageNamespace, $packagePhpSource);
		file_put_contents($package->getClassesPath() . 'Package.php', $packagePhpSource);

		$this->packages[$packageKey] = $package;
		foreach (array_keys($this->packages) as $upperCamelCasedPackageKey) {
			$this->packageKeys[strtolower($upperCamelCasedPackageKey)] = $upperCamelCasedPackageKey;
		}

		$this->activatePackage($packageKey);

		return $package;
	}

	/**
	 * Import a package from a remote location
	 *
	 * Imports the specified package from a remote git repository. The imported package will not be activated automatically.
	 * Currently only packages located at forge.typo3.org are supported. Note that the git binary must be available.
	 *
	 * @param string $packageKey The package key of the package to import.
	 * @return \TYPO3\FLOW3\Package\Package The imported package
	 * @throws \TYPO3\FLOW3\Package\Exception\PackageKeyAlreadyExistsException
	 * @throws \TYPO3\FLOW3\Package\Exception\PackageRepositoryException
	 */
	public function importPackage($packageKey) {
		if ($this->isPackageAvailable($packageKey)) {
			throw new \TYPO3\FLOW3\Package\Exception\PackageKeyAlreadyExistsException('The package already exists.', 1315223754);
		}

		exec($this->settings['package']['git']['gitBinary'] . ' --version', $output, $result);
		if ($result !== 0) {
			throw new \TYPO3\FLOW3\Package\Exception\PackageRepositoryException('Could not execute the git command line tool. Make sure to configure the right path in TYPO3:FLOW3:package:git:gitBinary.', 1315223755);
		}
		unset($output);

		$packagesPath = Files::getUnixStylePath(Files::concatenatePaths(array($this->packagesBasePath, 'Application')));
		$packagePath = Files::concatenatePaths(array($packagesPath, $packageKey)) . '/';
		Files::createDirectoryRecursively($packagePath);

		$gitCommand = ' clone --recursive git://git.typo3.org/FLOW3/Packages/' . $packageKey . '.git ' . $packagePath;
		exec($this->settings['package']['git']['gitBinary'] . $gitCommand, $output, $result);
		if ($result !== 0) {
			throw new \TYPO3\FLOW3\Package\Exception\PackageRepositoryException('Could not clone the remote package.' . PHP_EOL . 'git ' . $gitCommand, 1315223852);
		}

		$package = new Package($packageKey, $packagePath);

		$this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'inactive';
		$this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = $package->getPackagePath();
		$this->savePackageStates();

		return $package;
	}

	/**
	 * Deactivates a package
	 *
	 * @param string $packageKey The package to deactivate
	 * @return void
	 * @throws \TYPO3\FLOW3\Package\Exception\ProtectedPackageKeyException if a package is protected and cannot be deactivated
	 * @api
	 */
	public function deactivatePackage($packageKey) {
		if (!$this->isPackageActive($packageKey)) {
			return;
		}

		$package = $this->getPackage($packageKey);
		if ($package->isProtected()) {
			throw new \TYPO3\FLOW3\Package\Exception\ProtectedPackageKeyException('The package "' . $packageKey . '" is protected and cannot be deactivated.', 1308662891);
		}

		unset($this->activePackages[$packageKey]);
		$this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'inactive';
		$this->savePackageStates();
	}

	/**
	 * Activates a package
	 *
	 * @param string $packageKey The package to activate
	 * @return void
	 * @api
	 */
	public function activatePackage($packageKey) {
		if ($this->isPackageActive($packageKey)) {
			return;
		}

		$package = $this->getPackage($packageKey);
		$this->activePackages[$packageKey] = $package;
		$this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'active';
		$this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = $package->getPackagePath();
		$this->savePackageStates();
	}

	/**
	 * Freezes a package
	 *
	 * @param string $packageKey The package to freeze
	 * @return void
	 * @throws \TYPO3\FLOW3\Package\Exception\UnknownPackageException
	 */
	public function freezePackage($packageKey) {
		if (!$this->isPackageActive($packageKey)) {
			throw new \TYPO3\FLOW3\Package\Exception\UnknownPackageException('Package "' . $packageKey . '" is not available or active.', 1331715956);
		}
		if ($this->isPackageFrozen($packageKey)) {
			return;
		}

		$this->bootstrap->getObjectManager()->get('TYPO3\FLOW3\Reflection\ReflectionService')->freezePackageReflection($packageKey);

		$this->packageStatesConfiguration['packages'][$packageKey]['frozen'] = TRUE;
		$this->savePackageStates();
	}

	/**
	 * Tells if a package is frozen
	 *
	 * @param string $packageKey The package to check
	 * @return boolean
	 */
	public function isPackageFrozen($packageKey) {
		return (
			isset($this->packageStatesConfiguration['packages'][$packageKey]['frozen']) &&
			$this->packageStatesConfiguration['packages'][$packageKey]['frozen'] === TRUE
		);
	}

	/**
	 * Unfreezes a package
	 *
	 * @param string $packageKey The package to unfreeze
	 * @return void
	 */
	public function unfreezePackage($packageKey) {
		if (!$this->isPackageFrozen($packageKey)) {
			return;
		}

		$this->bootstrap->getObjectManager()->get('TYPO3\FLOW3\Reflection\ReflectionService')->unfreezePackageReflection($packageKey);

		unset($this->packageStatesConfiguration['packages'][$packageKey]['frozen']);
		$this->savePackageStates();
	}

	/**
	 * Refreezes a package
	 *
	 * @param string $packageKey The package to refreeze
	 * @return void
	 */
	public function refreezePackage($packageKey) {
		if (!$this->isPackageFrozen($packageKey)) {
			return;
		}

		$this->bootstrap->getObjectManager()->get('TYPO3\FLOW3\Reflection\ReflectionService')->unfreezePackageReflection($packageKey);
	}

	/**
	 * Removes a package from registry and deletes it from filesystem
	 *
	 * @param string $packageKey package to remove
	 * @return void
	 * @throws \TYPO3\FLOW3\Package\Exception\UnknownPackageException if the specified package is not known
	 * @throws \TYPO3\FLOW3\Package\Exception\ProtectedPackageKeyException if a package is protected and cannot be deleted
	 * @throws \TYPO3\FLOW3\Package\Exception
	 * @api
	 */
	public function deletePackage($packageKey) {
		if (!$this->isPackageAvailable($packageKey)) {
			throw new \TYPO3\FLOW3\Package\Exception\UnknownPackageException('Package "' . $packageKey . '" is not available and cannot be removed.', 1166543253);
		}

		$package = $this->getPackage($packageKey);
		if ($package->isProtected()) {
			throw new \TYPO3\FLOW3\Package\Exception\ProtectedPackageKeyException('The package "' . $packageKey . '" is protected and cannot be removed.', 1220722120);
		}

		if ($this->isPackageActive($packageKey)) {
			$this->deactivatePackage($packageKey);
		}

		$packagePath = $package->getPackagePath();
		try {
			Files::removeDirectoryRecursively($packagePath);
		} catch (\TYPO3\FLOW3\Utility\Exception $exception) {
			throw new \TYPO3\FLOW3\Package\Exception('Please check file permissions. The directory "' . $packagePath . '" for package "' . $packageKey . '" could not be removed.', 1301491089, $exception);
		}

		unset($this->packages[$packageKey]);
		unset($this->packageKeys[strtolower($packageKey)]);
	}

	/**
	 * Loads the states of available packages from the PackageStates.php file.
	 * The result is stored in $this->packageStatesConfiguration.
	 *
	 * @return void
	 */
	protected function loadPackageStates() {
		$this->packageStatesConfiguration = file_exists($this->packageStatesPathAndFilename) ? include($this->packageStatesPathAndFilename) : array();
		if (!isset($this->packageStatesConfiguration['version']) || $this->packageStatesConfiguration['version'] < 2) {
			if (is_dir(FLOW3_PATH_PACKAGES . '.Shortcuts')) {
				Files::removeDirectoryRecursively(FLOW3_PATH_PACKAGES . '.Shortcuts');
			}
			$this->packageStatesConfiguration = array();
		}
		if ($this->packageStatesConfiguration === array() || $this->bootstrap->getContext() !== 'Production') {
			$this->scanAvailablePackages();
		} else {
			foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $stateConfiguration) {
				$this->packageKeys[strtolower($packageKey)] = $packageKey;
			}
		}
		$this->registerPackages();
	}

	/**
	 * Scans all directories in the packages directories for available packages.
	 * For each package a Package object is created and stored in $this->packages.
	 *
	 * @return void
	 * @throws \TYPO3\FLOW3\Package\Exception\DuplicatePackageException
	 */
	protected function scanAvailablePackages() {
		$previousPackageStatesConfiguration = $this->packageStatesConfiguration;

		if (isset($this->packageStatesConfiguration['packages'])) {
			foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $configuration) {
				if (!file_exists($configuration['packagePath'])) {
					unset($this->packageStatesConfiguration['packages'][$packageKey]);
				}
			}
		} else {
			$this->packageStatesConfiguration['packages'] = array();
		}

		$packagePaths = array();
		foreach (new \DirectoryIterator($this->packagesBasePath) as $parentFileInfo) {
			$parentFilename = $parentFileInfo->getFilename();
			if ($parentFilename[0] !== '.' && $parentFileInfo->isDir()) {
				$packagePaths = array_merge($packagePaths, $this->scanPackagesInPath($parentFileInfo->getPathName()));
			}
		}

			// These packages are frozen by default if no PackageStates.php exists.
			// At this point we can't make it configurable, because Configuration is
			// not yet initialized ...
		$defaultFrozenPackages = array(
			'TYPO3.FLOW3', 'TYPO3.Fluid', 'TYPO3.Party', 'TYPO3.Kickstart',
			'TYPO3.Welcome', 'Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM'
		);

		foreach ($packagePaths as $packagePath) {
			$relativePackagePath = substr($packagePath, strlen($this->packagesBasePath));
			$packageKey = str_replace('/', '.', substr($relativePackagePath, strpos($relativePackagePath, '/') + 1, -1));

			if (isset($this->packages[$packageKey])) {
				throw new \TYPO3\FLOW3\Package\Exception\DuplicatePackageException('Detected a duplicate package, remove either "' . $this->packages[$packageKey]->getPackagePath() . '" or "' . $packagePath . '".', 1253716811);
			}
			$this->packageKeys[strtolower($packageKey)] = $packageKey;
			if (!isset($this->packageStatesConfiguration['packages'][$packageKey])) {
				$this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'active';
				if (in_array($packageKey, $defaultFrozenPackages)) {
					$this->packageStatesConfiguration['packages'][$packageKey]['frozen'] = TRUE;
				}
			}
			$this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = $packagePath;

				// Change this to read the target from Composer or any other source
			$this->packageStatesConfiguration['packages'][$packageKey]['classesPath'] = Package::DIRECTORY_CLASSES;
		}

			// sort longer package keys first, to find specific matches before generic ones
		uksort($this->packageStatesConfiguration['packages'], function($a, $b) {
			if (strlen($a) === strlen($b)) {
				return strcmp($a, $b);
			}
			return (strlen($a) > strlen($b)) ? -1 : 1;
		});

		$this->packageStatesConfiguration['version'] = 2;

		if ($this->packageStatesConfiguration != $previousPackageStatesConfiguration) {
			$this->savePackageStates();
		}
	}

	/**
	 * Scans the all sub directories of the specified directory and collects the package keys of packages it finds.
	 *
	 * The return of the array is to make this method usable in array_merge.
	 *
	 * @param string $startPath
	 * @param array $collectedPackagePaths
	 * @return array
	 */
	protected function scanPackagesInPath($startPath, &$collectedPackagePaths = array()) {
		foreach (new \DirectoryIterator($startPath) as $fileInfo) {
			$filename = $fileInfo->getFilename();
			if ($filename[0] !== '.') {
				$packagePath = Files::getUnixStylePath($fileInfo->getPathName()) . '/';
				$packageMetaPathAndFilename = $packagePath . 'Meta/Package.xml';
				if (file_exists($packageMetaPathAndFilename)) {
					$collectedPackagePaths[] = $packagePath;
				} elseif ($fileInfo->isDir() && $filename[0] !== '.') {
					$this->scanPackagesInPath($packagePath, $collectedPackagePaths);
				}
			}
		}
		return $collectedPackagePaths;
	}

	/**
	 * Requires and registers all packages which were defined in packageStatesConfiguration
	 *
	 * @return void
	 * @throws \TYPO3\FLOW3\Package\Exception\CorruptPackageException
	 */
	protected function registerPackages() {
		foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $stateConfiguration) {
			$packageClassPathAndFilename = $stateConfiguration['packagePath'] . 'Classes/Package.php';
			if (!file_exists($packageClassPathAndFilename)) {
				$shortFilename = substr($stateConfiguration['packagePath'], strlen($this->packagesBasePath)) . 'Classes/Package.php';
				throw new \TYPO3\FLOW3\Package\Exception\CorruptPackageException(sprintf('Missing package class in package "%s". Please create a file "%s" and extend Package.', $packageKey, $shortFilename), 1300782486);
			}

			require_once($packageClassPathAndFilename);
			$packageClassName = str_replace('.', '\\', $packageKey) . '\Package';
			if (!class_exists($packageClassName)) {
				throw new \TYPO3\FLOW3\Package\Exception\CorruptPackageException(sprintf('The package "%s" does not contain a valid package class. Check if the file "%s" really contains a class called "%s".', $packageKey, $packageClassPathAndFilename, $packageClassName), 1327587091);
			}
			$this->packages[$packageKey] = new $packageClassName($packageKey, $stateConfiguration['packagePath'], $stateConfiguration['classesPath']);

			if (!$this->packages[$packageKey] instanceof PackageInterface) {
				throw new \TYPO3\FLOW3\Package\Exception\CorruptPackageException(sprintf('The package class %s in package "%s" does not implement PackageInterface.', $packageClassName, $packageKey), 1300782487);
			}

			if ($stateConfiguration['state'] === 'active') {
				$this->activePackages[$packageKey] = $this->packages[$packageKey];
			}
		}
	}

	/**
	 * Saves the current content of $this->packageStatesConfiguration to the
	 * PackageStates.php file.
	 *
	 * @return void
	 */
	protected function savePackageStates() {
		$fileDescription = "# PackageStates.php\n\n";
		$fileDescription .= "# This file is maintained by FLOW3's package management. Although you can edit it\n";
		$fileDescription .= "# manually, you should rather use the command line commands for maintaining packages.\n";
		$fileDescription .= "# You'll find detailed information about the typo3.flow3:package:* commands in their\n";
		$fileDescription .= "# respective help screens.\n\n";
		$fileDescription .= "# This file will be regenerated automatically if it doesn't exist. Deleting this file\n";
		$fileDescription .= "# should, however, never become necessary if you use the package commands.\n";

		$packageStatesCode = "<?php\n$fileDescription\nreturn " . var_export($this->packageStatesConfiguration, TRUE) . "\n ?>";
		file_put_contents($this->packageStatesPathAndFilename, $packageStatesCode);
	}

}

?>
