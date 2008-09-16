<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Component;

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
 * @subpackage Component
 * @version $Id:F3::FLOW3::Component::TransientObjectCache.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * A transient Component Object Cache which provides a transient memory-based
 * registry of component objects.
 *
 * @package FLOW3
 * @subpackage Component
 * @version $Id:F3::FLOW3::Component::TransientObjectCache.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TransientObjectCache implements F3::FLOW3::Component::ObjectCacheInterface {

	/**
	 * @var array Location where component objects are stored
	 */
	protected $componentObjects = array();

	/**
	 * Returns a component object from the cache. If an instance of the required
	 * component does not exist yet, an exception is thrown.
	 *
	 * @param string $componentName Name of the component to return an object of
	 * @return object The component object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getComponentObject($componentName) {
		if (!$this->componentObjectExists($componentName)) throw new RuntimeException('Component "' . $componentName . '" does not exist in the component object cache.', 1167917198);
		return $this->componentObjects[$componentName];
	}

	/**
	 * Put a component object into the cache.
	 *
	 * @param string $componentName Name of the component the object is made for
	 * @param object $componentObject The component object to store in the cache
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function putComponentObject($componentName, $componentObject) {
		if (!is_string($componentName) || strlen($componentName) == 0) throw new RuntimeException('No valid component name specified.', 1167919564);
		if (!is_object($componentObject)) throw new RuntimeException('$componentObject must be of type Object', 1167917199);
		$this->componentObjects[$componentName] = $componentObject;
	}

	/**
	 * Remove a component object from the cache.
	 *
	 * @param string componentName Name of the component to remove the object for
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeComponentObject($componentName) {
		if (!$this->componentObjectExists($componentName)) throw new RuntimeException('Component "' . $componentName . '" does not exist in the component object cache.', 1167917200);
		unset ($this->componentObjects[$componentName]);
	}

	/**
	 * Checks if an object of the given component already exists in the object cache.
	 *
	 * @param string $componentName Name of the component to check for an object
	 * @return boolean TRUE if an object exists, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function componentObjectExists($componentName) {
		return isset($this->componentObjects[$componentName]);
	}

}

?>