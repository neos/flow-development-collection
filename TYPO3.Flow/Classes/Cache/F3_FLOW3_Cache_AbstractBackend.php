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
 * An abstract caching backend
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id:F3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class F3_FLOW3_Cache_AbstractBackend {

	/**
	 * @var F3_FLOW3_Cache_AbstractCache Reference to the cache which uses this backend
	 */
	protected $cache;

	/**
	 * @var string The current application context
	 */
	protected $context;

	/**
	 * Constructs this backend
	 *
	 * @param string $context: FLOW3's application context
	 */
	public function __construct($context) {
		$this->context = $context;
	}

	/**
	 * Sets a reference to the cache which uses this backend
	 *
	 * @param F3_FLOW3_Cache_AbstractCache $cache The frontend for this backend
	 * @return void
	 */
	public function setCache(F3_FLOW3_Cache_AbstractCache $cache) {
		$this->cache = $cache;
	}

	/**
	 * Saves data in the cache.
	 *
	 * @param string $data: The data to be stored
	 * @param string $entryIdentifier: An identifier for this specific cache entry
	 * @param array $tags: Tags to associate with this cache entry
	 * @param integer $lifetime: Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws F3_FLOW3_Cache_Exception if the directory does not exist or is not writable, or if no cache frontend has been set.
	 */
	abstract public function save($data, $entryIdentifier, $tags = array(), $lifetime = NULL);

	/**
	 * Loads data from the cache.
	 *
	 * @param string $entryIdentifier: An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 */
	abstract public function load($entryIdentifier);

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier: An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 */
	abstract public function has($entryIdentifier);

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string $entryIdentifier: Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 */
	abstract public function remove($entryIdentifier);

}
?>