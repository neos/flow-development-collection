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
 * Component Object Cache Interface
 * 
 * @package		FLOW3
 * @subpackage	Component
 * @version 	$Id:T3_FLOW3_Component_ObjectCacheInterface.php 201 2007-03-30 11:18:30Z robert $
 * @author		Robert Lemke <robert@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface T3_FLOW3_Component_ObjectCacheInterface {
	
	/**
	 * Returns a component object from the cache. If an instance of the required
	 * component does not exist yet, an exception is thrown.
	 *
	 * @param  string		$componentName: Name of the component to return an object of
	 * @return object		The component object
	 */
	public function getComponentObject($componentName);
	
	/**
	 * Put a component object into the cache.
	 *
	 * @param  string		$componentName: Name of the component the object is made for
	 * @param  object		$componentObject: The component object to store in the cache
	 * @return void
	 */
	public function putComponentObject($componentName, $componentObject);
	
	/**
	 * Remove a component object from the cache.
	 *
	 * @param  string		$componentName: Name of the component to remove the object for
	 * @return void
	 */
	public function removeComponentObject($componentName);
	
	/**
	 * Checks if an object of the given component already exists in the object cache.
	 *
	 * @param  string		$componentName: Name of the component to check for an object
	 * @return boolean		TRUE if an object exists, otherwise FALSE
	 */
	public function componentObjectExists($componentName);
}

?>