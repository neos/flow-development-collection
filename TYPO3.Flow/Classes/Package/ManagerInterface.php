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
 * Interface for the TYPO3 Package Manager
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id$
 * @author Robert Lemke <robert@typo3.org>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface ManagerInterface {

	/**
	 * Initializes the package manager. Initialization includes:
	 *
	 *   - building the package registry
	 *
	 * @return void
	 */
	public function initialize();

	/**
	 * Returns TRUE if a package is available (the package's files exist in the packages directory)
	 * or FALSE if it's not. If a package is available it doesn't mean neccessarily that it's active!
	 *
	 * @param string $packageKey: The key of the package to check
	 * @return boolean TRUE if the package is available, otherwise FALSE
	 */
	public function isPackageAvailable($packageKey);

	/**
	 * Returns a \F3\FLOW3\Package\PackageInterface object for the specified package.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @param string $packageKey
	 * @return array Array of \F3\FLOW3\Package\PackageInterface
	 */
	public function getPackage($packageKey);

	/**
	 * Returns an array of \F3\FLOW3\Package\PackageInterface objects of all available packages.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @return array Array of \F3\FLOW3\Package\PackageInterface
	 */
	public function getAvailablePackages();

	/**
	 * Returns an array of \F3\FLOW3\PackageInterface objects of all active packages.
	 * A package is active, if it is available and has been activated in the package
	 * manager settings.
	 *
	 * @return array Array of \F3\FLOW3\Package\PackageInterface
	 */
	public function getActivePackages();

	/**
	 * Returns the upper camel cased version of the given package key or FALSE
	 * if no such package is available.
	 *
	 * @param string $lowerCasedPackageKey The package key to convert
	 * @return mixed The upper camel cased package key or FALSE if no such package exists
	 */
	public function getCaseSensitivePackageKey($unknownCasedPackageKey);

	/**
	 * Returns the absolute path to the root directory of a package.
	 *
	 * @param string $packageKey Name of the package to return the path of
	 * @return string Absolute path to the package's root directory, with trailing directory separator
	 */
	public function getPackagePath($packageKey);

	/**
	 * Returns the absolute path to the "Classes" directory of a package.
	 *
	 * @param string $packageKey Name of the package to return the "Classes" path of
	 * @return string Absolute path to the package's "Classes" directory, with trailing directory separator
	 */
	public function getPackageClassesPath($packageKey);

	/**
	 * Check the conformance of the given package key
	 *
	 * @param string $packageKey The package key to validate
	 */
	public function isPackageKeyValid($packageKey);

	/**
	 * Create a new package, given the package key
	 *
	 * @param string $packageKey The package key to use for the new package
	 * @param \F3\FLOW3\Package\MetaData $packageMetaData Package metadata
	 * @return \F3\FLOW3\Package\Package The newly created package
	 */
	public function createPackage($packageKey, \F3\FLOW3\Package\MetaData $packageMetaData = null);

	/**
	 * Deactivates a packe if it is in the list of active packages
	 *
	 * @param string $packageKey The package to deactivate
	 * @return void
	 */
	public function deactivatePackage($packageKey);

	/**
	 * Activates a package
	 *
	 * @param string $packageKey The package to activate
	 * @return void
	 */
	public function activatePackage($packageKey);

	/**
	 * Removes a package from registry and deletes it from filesystem
	 *
	 * @param string $packageKey package to delete
	 * @return void
	 */
	public function deletePackage($packageKey);

}
?>