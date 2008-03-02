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
 * A cache for any kinds of PHP variables
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id:T3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class T3_FLOW3_Cache_VariableCache extends T3_FLOW3_Cache_AbstractCache {

	/**
	 * Saves the value of a PHP variable in the cache. Note that the variable
	 * will be serialized if necessary.
	 *
	 * @param string $entryIdentifier: An identifier used for this cache entry
	 * @param mixed $variable: The variable to cache
	 * @param array $tags: Tags to associate with this cache entry
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function save($entryIdentifier, $variable, $tags = array()) {
		$this->backend->save($entryIdentifier, serialize($variable));
	}

	/**
	 * Loads a variable value from the cache.
	 *
	 * @param string $entryIdentifier: Identifier of the cache entry to fetch
	 * @return mixed The value
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws T3_FLOW3_Cache_Exception_ClassAlreadyLoaded if the class already exists
	 */
	public function load($entryIdentifier) {
		return unserialize($this->backend->load($entryIdentifier));
	}
}
?>