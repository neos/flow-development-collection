<?php
namespace TYPO3\Flow\Persistence\Doctrine;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Cache adapter to use Flow caches as Doctrine cache
 */
class CacheAdapter implements \Doctrine\Common\Cache\Cache
{
    /**
     * @var \TYPO3\Flow\Cache\Frontend\FrontendInterface
     */
    protected $cache;

    /**
     * Set the cache this adapter should use.
     *
     * @param \TYPO3\Flow\Cache\Frontend\FrontendInterface $cache
     * @return void
     */
    public function setCache(\TYPO3\Flow\Cache\Frontend\FrontendInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
     */
    public function fetch($id)
    {
        return $this->cache->get(md5($id));
    }

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     * @return boolean TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    public function contains($id)
    {
        return $this->cache->has(md5($id));
    }

    /**
     * Puts data into the cache.
     *
     * @param string $id The cache id.
     * @param mixed $data The cache entry/data.
     * @param int $lifeTime The cache lifetime. If != 0, sets a specific lifetime for this cache entry (0 => infinite lifeTime).
     * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    public function save($id, $data, $lifeTime = 0)
    {
        $this->cache->set(md5($id), $data, array(), $lifeTime);
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    public function delete($id)
    {
        return $this->cache->remove(md5($id));
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
    public function getStats()
    {
        return null;
    }
}
