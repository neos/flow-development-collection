<?php
declare(strict_types=1);

namespace Neos\Cache\Frontend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Contract for a Cache (frontend)
 *
 * @api
 */
interface FrontendInterface
{
    /**
     * Pattern an entry identifier must match.
     */
    const PATTERN_ENTRYIDENTIFIER = '/^[a-zA-Z0-9_%\-&]{1,250}$/';

    /**
     * Pattern a tag must match.
     */
    const PATTERN_TAG = '/^[a-zA-Z0-9_%\-&]{1,250}$/';

    /**
     * Returns this cache's identifier
     *
     * @return string The identifier for this cache
     * @api
     */
    public function getIdentifier();

    /**
     * Returns the backend used by this cache
     *
     * @return \Neos\Cache\Backend\BackendInterface The backend used by this cache
     * @api
     */
    public function getBackend();

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier Something which identifies the data - depends on concrete cache
     * @param mixed $data The data to cache - also depends on the concrete cache implementation
     * @param array $tags Tags to associate with this cache entry
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @return void
     * @api
     */
    public function set(string $entryIdentifier, $data, array $tags = [], int $lifetime = null);

    /**
     * Finds and returns data from the cache.
     *
     * @param string $entryIdentifier Something which identifies the cache entry - depends on concrete cache
     * @return mixed
     * @api
     */
    public function get(string $entryIdentifier);

    /**
     * Finds and returns all cache entries which are tagged by the specified tag.
     *
     * @param string $tag The tag to search for
     * @return array An array with the identifier (key) and content (value) of all matching entries. An empty array if no entries matched
     * @api
     */
    public function getByTag(string $tag): array;

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return boolean true if such an entry exists, false if not
     * @api
     */
    public function has(string $entryIdentifier): bool;

    /**
     * Removes the given cache entry from the cache.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return boolean true if such an entry exists, false if not
     */
    public function remove(string $entryIdentifier): bool;

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     */
    public function flush();

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @return integer The number of entries which have been affected by this flush or NULL if the number is unknown
     * @api
     */
    public function flushByTag(string $tag): int;

    /**
     * Does garbage collection
     *
     * @return void
     * @api
     */
    public function collectGarbage();

    /**
     * Checks the validity of an entry identifier. Returns true if it's valid.
     *
     * @param string $identifier An identifier to be checked for validity
     * @return boolean
     * @api
     */
    public function isValidEntryIdentifier(string $identifier): bool;

    /**
     * Checks the validity of a tag. Returns true if it's valid.
     *
     * @param string $tag A tag to be checked for validity
     * @return boolean
     * @api
     */
    public function isValidTag(string $tag): bool;
}
