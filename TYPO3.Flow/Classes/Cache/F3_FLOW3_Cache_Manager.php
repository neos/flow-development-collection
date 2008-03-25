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
 * @version $Id$
 */

/**
 * The Cache Manager
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id:F3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Cache_Manager {

	/**
	 * @var array Registered Caches
	 */
	protected $caches = array();

	/**
	 * Registers a cache so it can be retrieved at a later point.
	 *
	 * @param F3_FLOW3_Cache_AbstractCache $cache The cache to be registered
	 * @return void
	 * @throws F3_FLOW3_Cache_DuplicateIdentifier if a cache with the given identifier has already been registered.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerCache(F3_FLOW3_Cache_AbstractCache $cache) {
		$identifier = $cache->getIdentifier();
		if (key_exists($identifier, $this->caches)) throw new F3_FLOW3_Cache_Exception_DuplicateIdentifier('A cache with identifier "' . $identifier . '" has already been registered.', 1203698223);
		$this->caches[$identifier] = $cache;
	}

	/**
	 * Returns the cache specified by $identifier
	 *
	 * @param string $identifier Identifies which cache to return
	 * @return F3_FLOW3_Cache_AbstractCache The specified cache
	 * @throws F3_FLOW3_Cache_Exception_NoSuchCache
	 */
	public function getCache($identifier) {
		if (!key_exists($identifier, $this->caches)) throw new F3_FLOW3_Cache_Exception_NoSuchCache('A cache with identifier "' . $identifier . '" does not exist.', 1203699034);
		return $this->caches[$identifier];
	}

	/**
	 * Checks if the specified cache has been registered.
	 *
	 * @param string $identifier The identifier of the cache
	 * @return boolean TRUE if a cache with the given identifier exists, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasCache($identifier) {
		return key_exists($identifier, $this->caches);
	}
}
?>