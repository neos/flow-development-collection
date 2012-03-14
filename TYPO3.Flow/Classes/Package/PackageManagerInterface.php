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

/**
 * Interface for the TYPO3 Package Manager
 *
 * @api
 */
interface PackageManagerInterface {

	/**
	 * Initializes the package manager.
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	public function initialize(\TYPO3\FLOW3\Core\Bootstrap $bootstrap);

	/**
	 * Returns TRUE if a package is available (the package's files exist in the packages directory)
	 * or FALSE if it's not. If a package is available it doesn't mean neccessarily that it's active!
	 *
	 * @param string $packageKey The key of the package to check
	 * @return boolean TRUE if the package is available, otherwise FALSE
	 * @api
	 */
	public function isPackageAvailable($packageKey);

	/**
	 * Returns TRUE if a package is activated or FALSE if it's not.
	 *
	 * @param string $packageKey The key of the package to check
	 * @return boolean TRUE if package is active, otherwise FALSE
	 * @api
	 */
	public function isPackageActive($packageKey);

	/**
	 * Returns a \TYPO3\FLOW3\Package\PackageInterface object for the specified package.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @param string $packageKey
	 * @return \TYPO3\FLOW3\Package\PackageInterface
	 * @api
	 */
	public function getPackage($packageKey);

	/**
	 * Returns an array of \TYPO3\FLOW3\Package\PackageInterface objects of all available packages.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @return array Array of \TYPO3\FLOW3\Package\PackageInterface
	 * @api
	 */
	public function getAvailablePackages();

	/**
	 * Returns an array of \TYPO3\FLOW3\PackageInterface objects of all active packages.
	 * A package is active, if it is available and has been activated in the package
	 * manager settings.
	 *
	 * @return array Array of \TYPO3\FLOW3\Package\PackageInterface
	 * @api
	 */
	public function getActivePackages();

	/**
	 * Returns the upper camel cased version of the given package key or FALSE
	 * if no such package is available.
	 *
	 * @param string $unknownCasedPackageKey The package key to convert
	 * @return mixed The upper camel cased package key or FALSE if no such package exists
	 * @api
	 */
	public function getCaseSensitivePackageKey($unknownCasedPackageKey);

	/**
	 * Check the conformance of the given package key
	 *
	 * @param string $packageKey The package key to validate
	 * @api
	 */
	public function isPackageKeyValid($packageKey);

	/**
	 * Create a new package, given the package key
	 *
	 * @param string $packageKey The package key to use for the new package
	 * @param \TYPO3\FLOW3\Package\MetaData $packageMetaData Package metadata
	 * @return \TYPO3\FLOW3\Package\Package The newly created package
	 * @api
	 */
	public function createPackage($packageKey, \TYPO3\FLOW3\Package\MetaData $packageMetaData = null);

	/**
	 * Deactivates a packe if it is in the list of active packages
	 *
	 * @param string $packageKey The package to deactivate
	 * @return void
	 * @api
	 */
	public function deactivatePackage($packageKey);

	/**
	 * Activates a package
	 *
	 * @param string $packageKey The package to activate
	 * @return void
	 * @api
	 */
	public function activatePackage($packageKey);

	/**
	 * Freezes a package
	 *
	 * @param string $packageKey The package to freeze
	 * @return void
	 */
	public function freezePackage($packageKey);

	/**
	 * Tells if a package is frozen
	 *
	 * @param string $packageKey The package to check
	 * @return boolean
	 */
	public function isPackageFrozen($packageKey);

	/**
	 * Unfreezes a package
	 *
	 * @param string $packageKey The package to unfreeze
	 * @return void
	 */
	public function unfreezePackage($packageKey);

	/**
	 * Removes a package from registry and deletes it from filesystem
	 *
	 * @param string $packageKey package to delete
	 * @return void
	 * @api
	 */
	public function deletePackage($packageKey);

}
?>