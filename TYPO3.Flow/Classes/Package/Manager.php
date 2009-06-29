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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */

/**
 * The default TYPO3 Package Manager
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Manager implements \F3\FLOW3\Package\ManagerInterface {

	/**
	 * @var \F3\FLOW3\Package\MetaData\WriterInterface
	 */
	protected $packageMetaDataWriter;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Configuration\Manager
	 */
	protected $configurationManager;

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
	 * Keys of active packages - not used yet!
	 * @var array
	 */
	protected $activePackages = array();

	/**
	 * Array of package keys that are protected and that must not be removed.
	 * These packages will also be loaded even when not activated.
	 * @var array
	 */
	protected $protectedPackages = array('FLOW3', 'PHP6', 'YAML');

	/**
	 * Injects a Package MetaData Writer
	 *
	 * @param \F3\FLOW3\Package\MetaData\WriterInterface $packageMetaDataWriter A package meta data writer instance to write package metadata
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @internal
	 */
	public function injectPackageMetaDataWriter(\F3\FLOW3\Package\MetaData\WriterInterface $packageMetaDataWriter) {
		$this->packageMetaDataWriter = $packageMetaDataWriter;
	}

	/**
	 * Injects the Object Factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the Configuration Manager
	 *
	 * @param \F3\FLOW3\Configuration\Manager $configurationManager
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @internal
	 */
	public function injectConfigurationManager(\F3\FLOW3\Configuration\Manager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Initializes the package manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function initialize() {
		$this->scanAvailablePackages();
		$packageStatesConfiguration = $this->configurationManager->getPackageStatesConfiguration();
		foreach ($this->packages as $packageKey => $package) {
			if (in_array($packageKey, $this->protectedPackages) || (isset($packageStatesConfiguration[$packageKey]['state']) && $packageStatesConfiguration[$packageKey]['state'] == 'active')) {
				$this->activePackages[$packageKey] = $package;
			}
		}
	}

	/**
	 * Returns TRUE if a package is available (the package's files exist in the packages directory)
	 * or FALSE if it's not. If a package is available it doesn't mean neccessarily that it's active!
	 *
	 * @param string $packageKey: The key of the package to check
	 * @return boolean TRUE if the package is available, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPackageAvailable($packageKey) {
		if (!is_string($packageKey)) throw new \InvalidArgumentException('The package key must be of type string, ' . gettype($packageKey) . ' given.', 1200402593);
		return (isset($this->packages[$packageKey]));
	}

	/**
	 * Returns TRUE if a package is activated or FALSE if it's not.
	 *
	 * @param string $packageKey: The key of the package to check
	 * @return boolean TRUE if package is active, otherwise FALSE
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function isPackageActive($packageKey) {
		if (!is_string($packageKey)) throw new InvalidArgumentException('The package key must be of type string, ' . gettype($packageKey) . ' given.', 1200402593);
		return (isset($this->activePackages[$packageKey]));
	}

	/**
	 * Returns a \F3\FLOW3\Package\PackageInterface object for the specified package.
	 * A package is available, if the package directory contains valid MetaData information.
	 *
	 * @param string $packageKey
	 * @return \F3\FLOW3\Package The requested package object
	 * @throws \F3\FLOW3\Package\Exception\UnknownPackage if the specified package is not known
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPackage($packageKey) {
		if (!$this->isPackageAvailable($packageKey)) throw new \F3\FLOW3\Package\Exception\UnknownPackage('Package "' . $packageKey . '" is not available. Pleas note that package keys are case sensitive.', 1166546734);
		return $this->packages[$packageKey];
	}

	/**
	 * Returns an array of \F3\FLOW3\Package objects of all available packages.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @return array Array of \F3\FLOW3\Package
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAvailablePackages() {
		return $this->packages;
	}

	/**
	 * Returns an array of \F3\FLOW3\Package objects of all active packages.
	 * A package is active, if it is available and has been activated in the package
	 * manager settings.
	 *
	 * @return array Array of \F3\FLOW3\Package
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getActivePackages() {
		return $this->activePackages;
	}

	/**
	 * Returns the upper camel cased version of the given package key or FALSE
	 * if no such package is available.
	 *
	 * @param string $lowerCasedPackageKey The package key to convert
	 * @return mixed The upper camel cased package key or FALSE if no such package exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCaseSensitivePackageKey($unknownCasedPackageKey) {
		$lowerCasedPackageKey = strtolower($unknownCasedPackageKey);
		return (isset($this->packageKeys[$lowerCasedPackageKey])) ? $this->packageKeys[$lowerCasedPackageKey] : FALSE;
	}

	/**
	 * Returns the absolute path to the root directory of a package. Only
	 * works for package which have a proper meta.xml file - which they
	 * should.
	 *
	 * @param string $packageKey: Name of the package to return the path of
	 * @return string Absolute path to the package's root directory
	 * @throws \F3\FLOW3\Package\Exception\UnknownPackage if the specified package is not known
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPackagePath($packageKey) {
		if (!$this->isPackageAvailable($packageKey)) throw new \F3\FLOW3\Package\Exception\UnknownPackage('Package "' . $packageKey . '" is not available.', 1166543253);
		return $this->packages[$packageKey]->getPackagePath();
	}

	/**
	 * Returns the absolute path to the "Classes" directory of a package.
	 *
	 * @param string $packageKey: Name of the package to return the "Classes" path of
	 * @return string Absolute path to the package's "Classes" directory, with trailing directory separator
	 * @throws \F3\FLOW3\Package\Exception\UnknownPackage if the specified package is not known
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPackageClassesPath($packageKey) {
		if (!$this->isPackageAvailable($packageKey)) throw new \F3\FLOW3\Package\Exception\UnknownPackage('Package "' . $packageKey . '" is not available.', 1167574237);
		return $this->packages[$packageKey]->getClassesPath();
	}

	/**
	 * Check the conformance of the given package key
	 *
	 * @param string $packageKey The package key to validate
	 * @return boolean If the package key is valid, returns TRUE otherwise FALSE
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function isPackageKeyValid($packageKey) {
		return preg_match(\F3\FLOW3\Package\Package::PATTERN_MATCH_PACKAGEKEY, $packageKey) === 1;
	}

	/**
	 * Check if the given package key is protected. Protected package keys
	 * cannot be removed (e.g. FLOW3 can't remove FLOW3).
	 *
	 * @param string $packageKey The package key to check for protection
	 * @return boolean If the package key is protected
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @internal
	 */
	public function isPackageKeyProtected($packageKey) {
		return in_array($packageKey, $this->protectedPackages);
	}

	/**
	 * Create a package, given the package key
	 *
	 * @param string $packageKey The package key of the new package
	 * @param \F3\FLOW3\Package\MetaData $packageMetaData If specified, this package meta object is used for writing the Package.xml file
	 * @param string $packagesPath If specified, the package will be created in this path, otherwise getLocalPackagesPath() is used
	 * @return \F3\FLOW3\Package\Package The newly created package
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackage($packageKey, \F3\FLOW3\Package\MetaData $packageMetaData = NULL, $packagesPath = '') {
		if (!$this->isPackageKeyValid($packageKey)) throw new \F3\FLOW3\Package\Exception\InvalidPackageKey('The package key "' . $packageKey . '" is invalid', 1220722210);
		if ($this->isPackageAvailable($packageKey)) throw new \F3\FLOW3\Package\Exception\PackageKeyAlreadyExists('The package key "' . $packageKey . '" already exists', 1220722873);

		if ($packageMetaData === NULL) {
			$packageMetaData = $this->objectFactory->create('F3\FLOW3\Package\MetaData', $packageKey);
		}

		if ($packagesPath === '') {
			$packagesPath = $this->getLocalPackagesPath();
		}
		if ($packagesPath === '') throw new \F3\FLOW3\Package\Exception\InvalidPackagePath('The path "Packages/Local" does not exist.', 1243932738);

		$packagePath = $packagesPath . $packageKey . '/';
		\F3\FLOW3\Utility\Files::createDirectoryRecursively($packagePath);

		foreach (
			array(
				\F3\FLOW3\Package\Package::DIRECTORY_METADATA,
				\F3\FLOW3\Package\Package::DIRECTORY_CLASSES,
				\F3\FLOW3\Package\Package::DIRECTORY_CONFIGURATION,
				\F3\FLOW3\Package\Package::DIRECTORY_DOCUMENTATION,
				\F3\FLOW3\Package\Package::DIRECTORY_RESOURCES,
				\F3\FLOW3\Package\Package::DIRECTORY_TESTS
			) as $path) {
			\F3\FLOW3\Utility\Files::createDirectoryRecursively($packagePath . $path);
		}

		$package = $this->objectFactory->create('F3\FLOW3\Package\Package', $packageKey, $packagePath);
		$result = $this->packageMetaDataWriter->writePackageMetaData($package, $packageMetaData);
		if ($result === FALSE) throw new \F3\FLOW3\Package\Exception('Error while writing the package meta data information at "' . $packagePath . '"', 1232625240);

		$this->packages[$packageKey] = $package;
		foreach (array_keys($this->packages) as $upperCamelCasedPackageKey) {
			$this->packageKeys[strtolower($upperCamelCasedPackageKey)] = $upperCamelCasedPackageKey;
		}
		return $package;
	}

	/**
	 * Get the path of the local packages. Will be used to calculate the path
	 * of a new package in createPackage(...). Returns an empty string if no
	 * local folder exists.
	 *
	 * @return string The path of the local packages
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getLocalPackagesPath() {
		if (realpath(FLOW3_PATH_PUBLIC . '../Packages/Local/') !== FALSE) {
			return \F3\FLOW3\Utility\Files::getUnixStylePath(realpath(FLOW3_PATH_PUBLIC . '../Packages/Local/') . '/');
		} else {
			return '';
		}
	}

	/**
	 * Deactivates a package if it is in the list of active packages
	 *
	 * @param string $packageKey The package to deactivate
	 * @throws \F3\FLOW3\Package\Exception\InvalidPackageState If the specified package is not active
	 * @author Thomas Hempel <thomas@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function deactivatePackage($packageKey) {
		if ($this->isPackageActive($packageKey)) {
			unset($this->activePackages[$packageKey]);
			$packageStatesConfiguration = $this->configurationManager->getPackageStatesConfiguration();
			$packageStatesConfiguration[$packageKey]['state'] = 'inactive';
			$this->configurationManager->updatePackageStatesConfiguration($packageStatesConfiguration);
		} else {
			throw new \F3\FLOW3\Package\Exception\InvalidPackageState('Package "' . $packageKey . '" is not active.', 1166543253);
		}
	}

	/**
	 * Activates a package
	 *
	 * @param string $packageKey The package to activate
	 * @throws \F3\FLOW3\Package\Exception\InvalidPackageState If the specified package is already active
	 * @author Thomas Hempel <thomas@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function activatePackage($packageKey) {
		if (!$this->isPackageActive($packageKey)) {
			$package = $this->getPackage($packageKey);
			$this->activePackages[$packageKey] = $package;
			$packageStatesConfiguration = $this->configurationManager->getPackageStatesConfiguration();
			$packageStatesConfiguration[$packageKey]['state'] = 'active';
			$this->configurationManager->updatePackageStatesConfiguration($packageStatesConfiguration);
		} else {
			throw new \F3\FLOW3\Package\Exception\InvalidPackageState('Package "' . $packageKey . '" is already active.', 1244620776);
		}
	}

	/**
	 * Removes a package from registry and deletes it from filesystem
	 *
	 * @param string $packageKey package to remove
	 * @throws \F3\FLOW3\Package\Exception\UnknownPackage if the specified package is not known
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function deletePackage($packageKey) {
		if ($this->isPackageKeyProtected($packageKey)) throw new \F3\FLOW3\Package\Exception\ProtectedPackageKey('The package "' . $packageKey . '" is protected and can not be removed.', 1220722120);
		if (!$this->isPackageAvailable($packageKey)) throw new \F3\FLOW3\Package\Exception\UnknownPackage('Package "' . $packageKey . '" is not available and can not be removed though.', 1166543253);
		if ($this->isPackageActive($packageKey)) {
			$this->deactivatePackage($packageKey);
		}

		$packagePath = $this->getPackagePath($packageKey);
		\F3\FLOW3\Utility\Files::removeDirectoryRecursively($packagePath);

		unset($this->packages[$packageKey]);
		unset($this->packageKeys[strtolower($packageKey)]);
	}

	/**
	 * Scans all directories in the packages directories for available packages.
	 * For each package a \F3\FLOW3\Package\ object is created and returned as
	 * an array.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function scanAvailablePackages() {
		$availablePackages = array('FLOW3' => $this->objectFactory->create('F3\FLOW3\Package\Package', 'FLOW3', FLOW3_PATH_FLOW3));

		$localPackagesParentPath = \F3\FLOW3\Utility\Files::getUnixStylePath(realpath(FLOW3_PATH_PUBLIC . '../Packages/'));
		$globalPackagesPath = \F3\FLOW3\Utility\Files::getUnixStylePath(realpath(FLOW3_PATH_FLOW3 . '../'));

		$pathsToScan = array($globalPackagesPath);
		$localPackagesParentDirectoryIterator = new \DirectoryIterator($localPackagesParentPath);
		while ($localPackagesParentDirectoryIterator->valid()) {
			$filename = $localPackagesParentDirectoryIterator->getFilename();
			$path = \F3\FLOW3\Utility\Files::getUnixStylePath(realpath($localPackagesParentDirectoryIterator->getPathName()));

			if ($filename[0] != '.' && $path !== $globalPackagesPath) {
				$pathsToScan[] = $path;
			}
			$localPackagesParentDirectoryIterator->next();
		}
		foreach ($pathsToScan as $packagesPath) {
			$packagesDirectoryIterator = new \DirectoryIterator($packagesPath);
			while ($packagesDirectoryIterator->valid()) {
				$filename = $packagesDirectoryIterator->getFilename();
				if ($filename[0] != '.' && $filename != 'FLOW3') {
					$packagePath = \F3\FLOW3\Utility\Files::getUnixStylePath($packagesDirectoryIterator->getPathName()) . '/';
					$availablePackages[$filename] = $this->objectFactory->create('F3\FLOW3\Package\Package', $filename, $packagePath);
				}
				$packagesDirectoryIterator->next();
			}
		}

		$this->packages = $availablePackages;
		foreach (array_keys($this->packages) as $upperCamelCasedPackageKey) {
			$this->packageKeys[strtolower($upperCamelCasedPackageKey)] = $upperCamelCasedPackageKey;
		}
	}
}

?>