<?php
namespace TYPO3\Flow\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\Common\Cache\Cache;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\FrontendInterface;
use TYPO3\Flow\Security\Context;

/**
 * Cache adapter to use Flow caches as Doctrine cache
 */
class CacheAdapter implements Cache {

	/**
	 * @var FrontendInterface
	 */
	protected $cache;

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * Set the cache this adapter should use.
	 *
	 * @param FrontendInterface $cache
	 * @return void
	 */
	public function setCache(FrontendInterface $cache) {
		$this->cache = $cache;
	}

	/**
	 * @param string $id
	 * @return string
	 */
	protected function convertCacheIdentifier($id) {
		return md5($id . '|' . $this->securityContext->getContextHash());
	}

	/**
	 * Fetches an entry from the cache.
	 *
	 * @param string $id The id of the cache entry to fetch.
	 * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
	 */
	public function fetch($id) {
		return $this->cache->get($this->convertCacheIdentifier($id));
	}

	/**
	 * Tests if an entry exists in the cache.
	 *
	 * @param string $id The cache id of the entry to check for.
	 * @return boolean TRUE if a cache entry exists for the given cache id, FALSE otherwise.
	 */
	public function contains($id) {
		return $this->cache->has($this->convertCacheIdentifier($id));
	}

	/**
	 * Puts data into the cache.
	 *
	 * @param string $id The cache id.
	 * @param mixed $data The cache entry/data.
	 * @param int $lifeTime The cache lifetime. If != 0, sets a specific lifetime for this cache entry (0 => infinite lifeTime).
	 * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
	 */
	public function save($id, $data, $lifeTime = 0) {
		$this->cache->set($this->convertCacheIdentifier($id), $data, array(), $lifeTime);
	}

	/**
	 * Deletes a cache entry.
	 *
	 * @param string $id The cache id.
	 * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
	 */
	public function delete($id) {
		return $this->cache->remove($this->convertCacheIdentifier($id));
	}

	/**
	 * Retrieves cached information from the data store.
	 * The server's statistics array has the following values:
	 * - <b>hits</b>
	 * Number of keys that have been requested and found present.
	 * - <b>misses</b>
	 * Number of items that have been requested and not found.
	 * - <b>uptime</b>
	 * Time that the server is running.
	 * - <b>memory_usage</b>
	 * Memory used by this server to store items.
	 * - <b>memory_available</b>
	 * Memory allowed to use for storage.
	 *
	 * @return array|null An associative array with server's statistics if available, NULL otherwise.
	 */
	public function getStats() {
		return NULL;
	}
}
