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
 * Interface for a TYPO3 Package class
 * 
 * @package		FLOW3
 * @subpackage	Package
 * @version 	$Id:T3_FLOW3_Package_PackageInterface.php 203 2007-03-30 13:17:37Z robert $
 * @author		Robert Lemke <robert@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface T3_FLOW3_Package_PackageInterface {

	/**
	 * Constructor
	 *
	 * @param	string	$packageKey: Key of this package
	 * @param	string	$packagePath: Absolute path to the package's main directory
	 * @param	array	$packageComponentsConfigurationSourceObjects: An array of component configuration source objects which deliver the components configuration for this package
	 */
	public function __construct($packageKey, $packagePath, $packageComponentsConfigurationSourceObjects);

	/**
	 * Returns the package meta object of this package.
	 *
	 * @return	T3_FLOW3_Package_Meta
	 */
	public function getPackageMeta();

	/**
	 * Returns the array of filenames of the class files
	 *
	 * @return	array		An array of class names (key) and their filename, including the relative path to the package's directory
	 */
	public function getClassFiles();
	
	/**
	 * Returns the package key of this package.
	 *
	 * @return	string
	 */
	public function getPackageKey();

	/**
	 * Returns the full path to this package's main directory
	 *
	 * @return	string		Path to this package's main directory
	 */
	public function getPackagePath();
	
	/**
	 * Returns the full path to this package's Classes directory
	 *
	 * @return	string		Path to this package's Classes directory
	 */
	public function getClassesPath();

	/**
	 * Returns the component configurations which were defined by this package.
	 * 
	 * @return  array		Array of component names and T3_FLOW3_Component_Configuration
	 */
	public function getComponentConfigurations();
	
}
?>