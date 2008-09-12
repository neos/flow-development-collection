<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Cache;

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
 * @version $Id:F3::FLOW3::AOP::Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Manager {

	/**
	 * @const Cache Entry depends on the PHP code of the packages
	 */
	const TAG_PACKAGES_CODE = '%PACKAGES_CODE';

	/**
	 * @var array Registered Caches
	 */
	protected $caches = array();

	/**
	 * Registers a cache so it can be retrieved at a later point.
	 *
	 * @param F3::FLOW3::Cache::AbstractCache $cache The cache to be registered
	 * @return void
	 * @throws F3::FLOW3::Cache::DuplicateIdentifier if a cache with the given identifier has already been registered.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerCache(F3::FLOW3::Cache::AbstractCache $cache) {
		$identifier = $cache->getIdentifier();
		if (key_exists($identifier, $this->caches)) throw new F3::FLOW3::Cache::Exception::DuplicateIdentifier('A cache with identifier "' . $identifier . '" has already been registered.', 1203698223);
		$this->caches[$identifier] = $cache;
	}

	/**
	 * Returns the cache specified by $identifier
	 *
	 * @param string $identifier Identifies which cache to return
	 * @return F3::FLOW3::Cache::AbstractCache The specified cache
	 * @throws F3::FLOW3::Cache::Exception::NoSuchCache
	 */
	public function getCache($identifier) {
		if (!key_exists($identifier, $this->caches)) throw new F3::FLOW3::Cache::Exception::NoSuchCache('A cache with identifier "' . $identifier . '" does not exist.', 1203699034);
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

	/**
	 * Flushes all registered caches
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushCaches() {
		foreach ($this->caches as $cache) {
			$cache->flush();
		}
	}

	/**
	 * Flushes entries tagged by the specified tag of all registered
	 * caches.
	 *
	 * @param string $tag Tag to search for
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushCachesByTag($tag) {
		foreach ($this->caches as $cache) {
			$cache->flushByTag($tag);
		}
	}
}
?>