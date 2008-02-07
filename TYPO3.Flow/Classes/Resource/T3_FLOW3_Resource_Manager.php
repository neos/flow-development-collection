<?php
declare(encoding = 'utf-8');

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

require_once(dirname(__FILE__) . '/T3_FLOW3_Resource_ClassLoader.php');

/**
 * The Resource Manager
 * 
 * @package		FLOW3
 * @subpackage	Resource
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Resource_Manager {

	/**
	 * @var T3_FLOW3_Resource_ClassLoader Instance of the class loader
	 */
	protected $classLoader;
	
	/**
	 * Constructs the resource manager
	 *
	 * @return void
	 */
	public function __construct() {
		$this->classLoader = new T3_FLOW3_Resource_ClassLoader(TYPO3_PATH_PACKAGES);
		spl_autoload_register(array($this->classLoader, 'loadClass'));
	}
	
	/**
	 * Explicitly registers a file path and name which holds the implementation of
	 * the given class.
	 * 
	 * @param  string		$className: Name of the class to register
	 * @param  string		$classFilePathAndName: Absolute path and file name of the file holding the class implementation
	 * @return void
	 * @throws InvalidArgumentException if $className is not a valid string
	 * @throws T3_FLOW3_Resource_Exception_FileDoesNotExist if the specified file does not exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerClassFile($className, $classFilePathAndName) {
		if (!is_string($className)) throw new InvalidArgumentException('Class name must be a valid string.', 1187009929);
		if (!file_exists($classFilePathAndName)) throw new T3_FLOW3_Resource_Exception_FileDoesNotExist('The specified class file does not exist.', 1187009987);		
		$this->classLoader->setSpecialClassNameAndPath($className, $classFilePathAndName);
	}	
}