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
 * Interface for a TYPO3 Package class
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id$
 * @author Robert Lemke <robert@typo3.org>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface PackageInterface {

	/**
	 * Returns the package meta object of this package.
	 *
	 * @return \F3\FLOW3\Package\Meta
	 */
	public function getPackageMeta();

	/**
	 * Returns the array of filenames of the class files
	 *
	 * @return array An array of class names (key) and their filename, including the relative path to the package's directory
	 */
	public function getClassFiles();

	/**
	 * Returns the package key of this package.
	 *
	 * @return string
	 */
	public function getPackageKey();

	/**
	 * Returns the full path to this package's main directory
	 *
	 * @return string Path to this package's main directory
	 */
	public function getPackagePath();

	/**
	 * Returns the full path to this package's Classes directory
	 *
	 * @return string Path to this package's Classes directory
	 */
	public function getClassesPath();

	/**
	 * Returns the full path to this package's Package.xml file
	 *
	 * @return string Path to this package's Package.xml file
	 */
	public function getPackageMetaPath();
}
?>