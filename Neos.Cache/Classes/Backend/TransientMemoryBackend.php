<?php
declare(strict_types=1);

namespace Neos\Cache\Backend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\AbstractBackend as IndependentAbstractBackend;
use Neos\Cache\Exception;
use Neos\Cache\Frontend\FrontendInterface;

/**
 * A caching backend which stores cache entries during one script run.
 *
 * @api
 */
class TransientMemoryBackend extends IndependentAbstractBackend implements TaggableBackendInterface
{
    /**
     * @var array
     */
    protected $entries = [];

    /**
     * @var array
     */
    protected $tagsAndEntries = [];

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @return void
     * @throws Exception if no cache frontend has been set.
     * @api
     */
    public function set(string $entryIdentifier, string $data, array $tags = [], int $lifetime = null): void
    {
        if (!$this->cache instanceof FrontendInterface) {
            throw new Exception('No cache frontend has been set yet via setCache().', 1238244992);
        }


        $this->entries[$entryIdentifier] = $data;
        foreach ($tags as $tag) {
            $this->tagsAndEntries[$tag][$entryIdentifier] = true;
        }
    }

    /**
     * Loads data from the cache.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or false if the cache entry could not be loaded
     * @api
     */
    public function get(string $entryIdentifier)
    {
        return $this->entries[$entryIdentifier] ?? false;
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return boolean true if such an entry exists, false if not
     * @api
     */
    public function has(string $entryIdentifier): bool
    {
        return isset($this->entries[$entryIdentifier]);
    }

    /**
     * Removes all cache entries matching the specified identifier.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return boolean true if the entry could be removed or false if no entry was found
     * @api
     */
    public function remove(string $entryIdentifier): bool
    {
        if (!isset($this->entries[$entryIdentifier])) {
            return false;
        }
        unset($this->entries[$entryIdentifier]);
        foreach (array_keys($this->tagsAndEntries) as $tag) {
            if (isset($this->tagsAndEntries[$tag][$entryIdentifier])) {
                unset($this->tagsAndEntries[$tag][$entryIdentifier]);
            }
        }
        return true;
    }

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag.
     *
     * @param string $tag The tag to search for
     * @return string[] An array with identifiers of all matching entries. An empty array if no entries matched
     * @api
     */
    public function findIdentifiersByTag(string $tag): array
    {
        if (isset($this->tagsAndEntries[$tag])) {
            return array_keys($this->tagsAndEntries[$tag]);
        }
        return [];
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     * @api
     */
    public function flush(): void
    {
        $this->entries = [];
        $this->tagsAndEntries = [];
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @return integer The number of entries which have been affected by this flush
     * @api
     */
    public function flushByTag(string $tag): int
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $this->remove($identifier);
        }
        return count($identifiers);
    }

    /**
     * Does nothing
     *
     * @return void
     * @api
     */
    public function collectGarbage(): void
    {
    }
}
