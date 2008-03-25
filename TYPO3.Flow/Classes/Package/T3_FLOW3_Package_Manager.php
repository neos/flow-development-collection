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
 * @package FLOW3
 * @subpackage Package
 * @version $Id:T3_FLOW3_Package_Manager.php 203 2007-03-30 13:17:37Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

/**
 * The default TYPO3 Package Manager
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id:T3_FLOW3_Package_Manager.php 203 2007-03-30 13:17:37Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Package_Manager implements T3_FLOW3_Package_ManagerInterface {

	/**
	 * @var T3_FLOW3_Component_ManagerInterface Holds an instance of the component manager
	 */
	protected $componentManager;

	/**
	 * @var array Array of available packages, indexed by package key
	 */
	protected $packages = array();

	/**
	 * @var array List of active packages - not used yet!
	 */
	protected $arrayOfActivePackages = array();

	/**
	 * @var array Array of packages whose classes must not be registered as components automatically
	 */
	protected $componentRegistrationPackageBlacklist = array();

	/**
	 * @var array Array of class names which must not be registered as components automatically. Class names may also be regular expressions.
	 */
	protected $componentRegistrationClassBlacklist = array(
		'T3_FLOW3_AOP_.*',
		'T3_FLOW3_Component.*',
		'T3_FLOW3_Package.*',
		'T3_FLOW3_Reflection.*',
	);

	/**
	 * @var T3_FLOW3_Package_ComponentsConfigurationSourceInterface	$packageComponentsConfigurationSourceObjects: An array of component configuration source objects which deliver the components configuration for a package
	 */
	protected $packageComponentsConfigurationSourceObjects = array();

	/**
	 * Constructor
	 *
	 * @param  T3_FLOW3_Component_ManagerInterface	$componentManager: An instance of the component manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(T3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
		$this->registerFLOW3Components();
		$this->packageComponentsConfigurationSourceObjects = array (
			$this->componentManager->getComponent('T3_FLOW3_Package_IniFileComponentsConfigurationSource'),
			$this->componentManager->getComponent('T3_FLOW3_Package_PHPFileComponentsConfigurationSource')
		);
	}

	/**
	 * Initializes the package manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		$this->packages = $this->scanAvailablePackages();
	}

	/**
	 * Returns TRUE if a package is available (the package's files exist in the pcakages directory)
	 * or FALSE if it's not. If a package is available it doesn't mean neccessarily that it's active!
	 *
	 * @param string $packageKey: The key of the package to check
	 * @return boolean TRUE if the package is available, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPackageAvailable($packageKey) {
		if (!is_string($packageKey)) throw new InvalidArgumentException('The package key must be of type string, ' . gettype($packageKey) . ' given.', 1200402593);
		return (key_exists($packageKey, $this->packages));
	}

	/**
	 * Returns a T3_FLOW3_Package_PackageInterface object for the specified package.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @param string $packageKey
	 * @return T3_FLOW3_Package The requested package object
	 * @throws T3_FLOW3_Package_Exception_UnknownPackage if the specified package is not known
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPackage($packageKey) {
		if (!$this->isPackageAvailable($packageKey)) throw new T3_FLOW3_Package_Exception_UnknownPackage('Package "' . $packageKey . '" is not available.', 1166546734);
		return $this->packages[$packageKey];
	}

	/**
	 * Returns an array of T3_FLOW3_Package_Meta objects of all available packages.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @return array Array of T3_FLOW3_Package_Meta
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAvailablePackages() {
		return $this->packages;
	}

	/**
	 * Returns an array of T3_FLOW3_Package_Meta objects of all active packages.
	 * A package is active, if it is available and has been activated in the package
	 * manager settings.
	 *
	 * @return array Array of T3_FLOW3_Package_Meta
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Implement activation / deactivation of packages
	 */
	public function getActivePackages() {
		return $this->packages;
	}

	/**
	 * Returns the absolute path to the root directory of a package. Only
	 * works for package which have a proper meta.xml file - which they
	 * should.
	 *
	 * @param string $packageKey: Name of the package to return the path of
	 * @return string Absolute path to the package's root directory
	 * @throws T3_FLOW3_Package_Exception_UnknownPackage if the specified package is not known
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPackagePath($packageKey) {
		if (!$this->isPackageAvailable($packageKey)) throw new T3_FLOW3_Package_Exception_UnknownPackage('Package "' . $packageKey . '" is not available.', 1166543253);
		return $this->packages[$packageKey]->getPackagePath();
	}

	/**
	 * Returns the absolute path to the "Classes" directory of a package.
	 *
	 * @param string $packageKey: Name of the package to return the "Classes" path of
	 * @return string Absolute path to the package's "Classes" directory, with trailing directory separator
	 * @throws T3_FLOW3_Package_Exception_UnknownPackage if the specified package is not known
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPackageClassesPath($packageKey) {
		if (!$this->isPackageAvailable($packageKey)) throw new T3_FLOW3_Package_Exception_UnknownPackage('Package "' . $packageKey . '" is not available.', 1167574237);
		return $this->packages[$packageKey]->getClassesPath();
	}

	/**
	 * Registers certain classes of the Package Manager as components, so they can
	 * be used for dependency injection elsewhere.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function registerFLOW3Components() {
		$this->componentManager->registerComponent('T3_FLOW3_Package_Package', 'T3_FLOW3_Package_Package');
		$this->componentManager->registerComponent('T3_FLOW3_Package_IniFileComponentsConfigurationSource');
		$this->componentManager->registerComponent('T3_FLOW3_Package_PHPFileComponentsConfigurationSource');

		$componentConfigurations = $this->componentManager->getComponentConfigurations();
		$componentConfigurations['T3_FLOW3_Package_Package']->setScope('prototype');
		$this->componentManager->setComponentConfigurations($componentConfigurations);
	}

	/**
	 * Scans all directories in the Packages/ directory for available packages.
	 * For each package a T3_FLOW3_Package_ object is created and returned as
	 * an array.
	 *
	 * @return array An array of T3_FLOW3_Package_Package objects for all available packages
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function scanAvailablePackages() {
		$availablePackagesArr = array();
		$packagesDirectoryIterator = new DirectoryIterator(FLOW3_PATH_PACKAGES);
		while ($packagesDirectoryIterator->valid()) {
			$filename = $packagesDirectoryIterator->getFilename();
			if ($filename{0} != '.') {
				$availablePackagesArr[$filename] = new T3_FLOW3_Package_Package($filename, ($packagesDirectoryIterator->getPathName() . '/'), $this->packageComponentsConfigurationSourceObjects);
			}
			$packagesDirectoryIterator->next();
		}
		return $availablePackagesArr;
	}
}

?>