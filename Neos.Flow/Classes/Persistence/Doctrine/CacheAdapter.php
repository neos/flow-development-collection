<?php
namespace Neos\Flow\Persistence\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Cache\CacheProvider;
use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Flow\Security\Context;

/**
 * Cache adapter to use Flow caches as Doctrine cache
 */
class CacheAdapter extends CacheProvider
{
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
    public function setCache(FrontendInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $id
     * @return string
     */
    protected function convertCacheIdentifier($id)
    {
        return md5($id . '|' . $this->securityContext->getContextHash());
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     * @return mixed|false The cached data or FALSE, if no cache entry exists for the given id.
     */
    protected function doFetch($id);
    {
        return $this->cache->get($this->convertCacheIdentifier($id));
    }

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     *
     * @return bool TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    protected function doContains($id);
    {
        return $this->cache->has($this->convertCacheIdentifier($id));
    }

    /**
     * Puts data into the cache.
     *
     * @param string $id       The cache id.
     * @param string $data     The cache entry/data.
     * @param int    $lifeTime The lifetime. If != 0, sets a specific lifetime for this
     *                           cache entry (0 => infinite lifeTime).
     *
     * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $this->cache->set($this->convertCacheIdentifier($id), $data, [], $lifeTime);
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    protected function doDelete($id)
    {
        return $this->cache->remove($this->convertCacheIdentifier($id));
    }

    /**
     * Flushes all cache entries.
     *
     * @return bool TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    protected function doFlush()
    {
        $this->cache->flush();
        return true;
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
    protected function doGetStats()
    {
        return null;
    }
}
