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
 * @subpackage Cache
 * @version $Id: $
 */

/**
 * A cache for (possibly generated) PHP classes
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id:T3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class T3_FLOW3_Cache_ClassCache extends T3_FLOW3_Cache_AbstractCache {

	/**
	 * Saves code of a PHP class in the cache.
	 *
	 * @param string $className: Name of the class to cache
	 * @param array $tags: Tags to associate with this cache entry
	 * @return void
	 * @throws T3_FLOW3_Cache_Exception_InvalidClass if the class does not exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function save($className, $tags = array()) {
		if (!class_exists($className)) throw new T3_FLOW3_Cache_Exception_InvalidClass('The class "' . $className . '" does not exist.', 1203959737);
		$class = new ReflectionClass($className);
		$this->backend->save((string)$class);
	}

	/**
	 * Loads a PHP class from the cache.
	 *
	 * @param string $className: Name of the class to load
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws T3_FLOW3_Cache_Exception_ClassAlreadyLoaded if the class already exists
	 */
	public function load($className) {
		if (class_exists($className)) throw new T3_FLOW3_Cache_Exception_ClassAlreadyLoaded('The class "' . $className . '" already exists.', 1203959740);
		$classCode = $this->backend->load($className);
		eval($classCode);
	}
}
?>