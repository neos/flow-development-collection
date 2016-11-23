<?php
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

use Neos\Cache\Frontend\FrontendInterface;

/**
 * A contract for a Cache Backend
 *
 * @api
 */
interface BackendInterface
{
    /**
     * Sets a reference to the cache frontend which uses this backend
     *
     * @param FrontendInterface $cache The frontend for this backend
     * @return void
     * @api
     */
    public function setCache(FrontendInterface $cache);

    /**
     * Returns the internally used, prefixed entry identifier for the given public
     * entry identifier.
     *
     * While Flow applications will mostly refer to the simple entry identifier, it
     * may be necessary to know the actual identifier used by the cache backend
     * in order to share cache entries with other applications. This method allows
     * for retrieving it.
     *
     * @param string $entryIdentifier
     * @return string
     */
    public function getPrefixedIdentifier($entryIdentifier);

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry. If the backend does not support tags, this option can be ignored.
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @return void
     * @throws \Neos\Cache\Exception if no cache frontend has been set.
     * @throws \InvalidArgumentException if the identifier is not valid
     * @throws \Neos\Cache\Exception\InvalidDataException if $data is not a string
     * @api
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null);

    /**
     * Loads data from the cache.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
     * @api
     */
    public function get($entryIdentifier);

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return boolean TRUE if such an entry exists, FALSE if not
     * @api
     */
    public function has($entryIdentifier);

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
     * @api
     */
    public function remove($entryIdentifier);

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     * @api
     */
    public function flush();

    /**
     * Does garbage collection
     *
     * @return void
     * @api
     */
    public function collectGarbage();
}
