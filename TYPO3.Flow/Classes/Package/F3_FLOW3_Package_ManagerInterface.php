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
 * Interface for the TYPO3 Package Manager
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id:F3_FLOW3_Package_ManagerInterface.php 203 2007-03-30 13:17:37Z robert $
 * @author Robert Lemke <robert@typo3.org>
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_Package_ManagerInterface {

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
	 * Returns a F3_FLOW3_Package_PackageInterface object for the specified package.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @param string $packageKey
	 * @return array Array of F3_FLOW3_Package_PackageInterface
	 */
	public function getPackage($packageKey);

	/**
	 * Returns an array of F3_FLOW3_Package_PackageInterface objects of all available packages.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @return array Array of F3_FLOW3_Package_PackageInterface
	 */
	public function getAvailablePackages();

	/**
	 * Returns an array of F3_FLOW3_Package_Meta objects of all active packages.
	 * A package is active, if it is available and has been activated in the package
	 * manager settings.
	 *
	 * @return array Array of F3_FLOW3_Package_PackageInterface
	 */
	public function getActivePackages();

	/**
	 * Returns the absolute path to the root directory of a package.
	 *
	 * @param string $packageKey: Name of the package to return the path of
	 * @return string Absolute path to the package's root directory, with trailing directory separator
	 */
	public function getPackagePath($packageKey);

	/**
	 * Returns the absolute path to the "Classes" directory of a package.
	 *
	 * @param string $packageKey: Name of the package to return the "Classes" path of
	 * @return string Absolute path to the package's "Classes" directory, with trailing directory separator
	 */
	public function getPackageClassesPath($packageKey);

#	public function activatePackage($packageKey);
#	public function deactivatePackage($packageKey);
#	public function removePackage($packageKey);
#	public function downloadPackageFromRepository($packageKey, $version);

}
?>