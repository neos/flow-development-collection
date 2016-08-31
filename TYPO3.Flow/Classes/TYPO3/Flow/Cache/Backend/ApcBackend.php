<?php
namespace TYPO3\Flow\Cache\Backend;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * A caching backend which stores cache entries by using APC.
 *
 * This backend uses the following types of keys:
 *
 * - entry_xxx
 *   the actual cache entry with the data to be stored
 * - tag_xxx
 *   xxx is tag name, value is array of associated identifiers identifier. This
 *   is "forward" tag index. It is mainly used for obtaining content by tag
 *   (get identifier by tag -> get content by identifier)
 * - ident_xxx
 *   xxx is identifier, value is array of associated tags. This is "reverse" tag
 *   index. It provides quick access for all tags associated with this identifier
 *   and used when removing the identifier
 * - tagIndex
 *   Value is a List of all tags (array)
 *
 * Each key is prepended with a prefix. By default prefix consists from two parts
 * separated by underscore character and ends in yet another underscore character:
 *
 * - "Flow"
 * - MD5 of path to Flow and the current context (Production, Development, ...)
 *
 * This prefix makes sure that keys from the different installations do not
 * conflict.
 *
 * @api
 */
class ApcBackend extends AbstractBackend implements TaggableBackendInterface, IterableBackendInterface
{
    /**
     * A prefix to seperate stored data from other data possible stored in the APC
     * @var string
     */
    protected $identifierPrefix;

    /**
     * @var \ApcIterator
     */
    protected $cacheEntriesIterator;

    /**
     * Constructs this backend
     *
     * @param \TYPO3\Flow\Core\ApplicationContext $context Flow's application context
     * @param array $options Configuration options - unused here
     * @throws \TYPO3\Flow\Cache\Exception
     */
    public function __construct(\TYPO3\Flow\Core\ApplicationContext $context, array $options = array())
    {
        if (!extension_loaded('apc')) {
            throw new \TYPO3\Flow\Cache\Exception('The PHP extension "apc" must be installed and loaded in order to use the APC backend.', 1232985414);
        }
        parent::__construct($context, $options);
    }

    /**
     * Initializes the identifier prefix when setting the cache.
     *
     * @param \TYPO3\Flow\Cache\Frontend\FrontendInterface $cache
     * @return void
     */
    public function setCache(\TYPO3\Flow\Cache\Frontend\FrontendInterface $cache)
    {
        parent::setCache($cache);

        $pathHash = substr(md5(FLOW_PATH_ROOT . $this->context . $cache->getIdentifier()), 0, 12);
        $this->identifierPrefix = 'Flow_' . $pathHash . '_';
    }

    /**
     * Returns the internally used, prefixed entry identifier for the given public
     * entry identifier.
     *
     * While Flow applications will mostly refer to the simple entry identifier, it
     * may be necessary to know the actual identifier used by the cache backend
     * in order to share cache entries with other applications. This method allows
     * for retrieving it.
     *
     * @param string $entryIdentifier The short entry identifier, for example "NumberOfPostedArticles"
     * @return string The prefixed identifier, for example "Flow694a5c7a43a4_NumberOfPostedArticles"
     * @api
     */
    public function getPrefixedIdentifier($entryIdentifier)
    {
        return $this->identifierPrefix . 'entry_' . $entryIdentifier;
    }

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
     * @return void
     * @throws \TYPO3\Flow\Cache\Exception if no cache frontend has been set.
     * @throws \InvalidArgumentException if the identifier is not valid
     * @throws \TYPO3\Flow\Cache\Exception\InvalidDataException if $data is not a string
     * @api
     */
    public function set($entryIdentifier, $data, array $tags = array(), $lifetime = null)
    {
        if (!$this->cache instanceof \TYPO3\Flow\Cache\Frontend\FrontendInterface) {
            throw new \TYPO3\Flow\Cache\Exception('No cache frontend has been set yet via setCache().', 1232986818);
        }
        if (!is_string($data)) {
            throw new \TYPO3\Flow\Cache\Exception\InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1232986825);
        }

        $tags[] = '%APCBE%' . $this->cacheIdentifier;
        $expiration = $lifetime !== null ? $lifetime : $this->defaultLifetime;

        $success = apc_store($this->identifierPrefix . 'entry_' . $entryIdentifier, $data, $expiration);
        if ($success === true) {
            $this->removeIdentifierFromAllTags($entryIdentifier);
            $this->addIdentifierToTags($entryIdentifier, $tags);
        } else {
            throw new \TYPO3\Flow\Cache\Exception('Could not set value.', 1232986877);
        }
    }

    /**
     * Loads data from the cache.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
     * @api
     */
    public function get($entryIdentifier)
    {
        $success = false;
        $value = apc_fetch($this->identifierPrefix . 'entry_' . $entryIdentifier, $success);
        return ($success ? $value : $success);
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return boolean TRUE if such an entry exists, FALSE if not
     * @api
     */
    public function has($entryIdentifier)
    {
        $success = false;
        apc_fetch($this->identifierPrefix . 'entry_' . $entryIdentifier, $success);
        return $success;
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
     * @api
     */
    public function remove($entryIdentifier)
    {
        $this->removeIdentifierFromAllTags($entryIdentifier);
        return apc_delete($this->identifierPrefix . 'entry_' . $entryIdentifier);
    }

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag.
     *
     * @param string $tag The tag to search for
     * @return array An array with identifiers of all matching entries. An empty array if no entries matched
     * @api
     */
    public function findIdentifiersByTag($tag)
    {
        $success = false;
        $identifiers = apc_fetch($this->identifierPrefix . 'tag_' . $tag, $success);
        if ($success === false) {
            return array();
        }
        return (array) $identifiers;
    }

    /**
     * Finds all tags for the given identifier. This function uses reverse tag
     * index to search for tags.
     *
     * @param string $identifier Identifier to find tags by
     * @return array Array with tags
     */
    protected function findTagsByIdentifier($identifier)
    {
        $success = false;
        $tags = apc_fetch($this->identifierPrefix . 'ident_' . $identifier, $success);
        return ($success ? (array)$tags : array());
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     * @throws \TYPO3\Flow\Cache\Exception
     * @api
     */
    public function flush()
    {
        if (!$this->cache instanceof \TYPO3\Flow\Cache\Frontend\FrontendInterface) {
            throw new \TYPO3\Flow\Cache\Exception('Yet no cache frontend has been set via setCache().', 1232986971);
        }
        $this->flushByTag('%APCBE%' . $this->cacheIdentifier);
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @return integer The number of entries which have been affected by this flush
     * @api
     */
    public function flushByTag($tag)
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $this->remove($identifier);
        }
        return count($identifiers);
    }

    /**
     * Associates the identifier with the given tags
     *
     * @param string $entryIdentifier
     * @param array $tags
     * @return void
     */
    protected function addIdentifierToTags($entryIdentifier, array $tags)
    {
        foreach ($tags as $tag) {
            // Update tag-to-identifier index
            $identifiers = $this->findIdentifiersByTag($tag);
            if (array_search($entryIdentifier, $identifiers) === false) {
                $identifiers[] = $entryIdentifier;
                apc_store($this->identifierPrefix . 'tag_' . $tag, $identifiers);
            }

            // Update identifier-to-tag index
            $existingTags = $this->findTagsByIdentifier($entryIdentifier);
            if (array_search($entryIdentifier, $existingTags) === false) {
                apc_store($this->identifierPrefix . 'ident_' . $entryIdentifier, array_merge($existingTags, $tags));
            }
        }
    }

    /**
     * Removes association of the identifier with the given tags
     *
     * @param string $entryIdentifier
     * @return void
     */
    protected function removeIdentifierFromAllTags($entryIdentifier)
    {
        // Get tags for this identifier
        $tags = $this->findTagsByIdentifier($entryIdentifier);
        // Deassociate tags with this identifier
        foreach ($tags as $tag) {
            $identifiers = $this->findIdentifiersByTag($tag);
            // Formally array_search() below should never return false due to
            // the behavior of findTagsByIdentifier(). But if reverse index is
            // corrupted, we still can get 'false' from array_search(). This is
            // not a problem because we are removing this identifier from
            // anywhere.
            if (($key = array_search($entryIdentifier, $identifiers)) !== false) {
                unset($identifiers[$key]);
                if (count($identifiers)) {
                    apc_store($this->identifierPrefix . 'tag_' . $tag, $identifiers);
                } else {
                    apc_delete($this->identifierPrefix . 'tag_' . $tag);
                }
            }
        }
        // Clear reverse tag index for this identifier
        apc_delete($this->identifierPrefix . 'ident_' . $entryIdentifier);
    }

    /**
     * Does nothing, as APC does GC itself
     *
     * @return void
     * @api
     */
    public function collectGarbage()
    {
    }

    /**
     * Returns the data of the current cache entry pointed to by the cache entry
     * iterator.
     *
     * @return mixed
     * @api
     */
    public function current()
    {
        if ($this->cacheEntriesIterator === null) {
            $this->rewind();
        }
        return $this->cacheEntriesIterator->current();
    }

    /**
     * Move forward to the next cache entry
     *
     * @return void
     * @api
     */
    public function next()
    {
        if ($this->cacheEntriesIterator === null) {
            $this->rewind();
        }
        $this->cacheEntriesIterator->next();
    }

    /**
     * Returns the identifier of the current cache entry pointed to by the cache
     * entry iterator.
     *
     * @return string
     * @api
     */
    public function key()
    {
        if ($this->cacheEntriesIterator === null) {
            $this->rewind();
        }
        return substr($this->cacheEntriesIterator->key(), strlen($this->identifierPrefix . 'entry_'));
    }

    /**
     * Checks if the current position of the cache entry iterator is valid
     *
     * @return boolean TRUE if the current position is valid, otherwise FALSE
     * @api
     */
    public function valid()
    {
        if ($this->cacheEntriesIterator === null) {
            $this->rewind();
        }
        return $this->cacheEntriesIterator->valid();
    }

    /**
     * Rewinds the cache entry iterator to the first element
     *
     * @return void
     * @api
     */
    public function rewind()
    {
        if ($this->cacheEntriesIterator === null) {
            $this->cacheEntriesIterator = new \APCIterator('user', '/^' . $this->identifierPrefix . 'entry_.*/');
        } else {
            $this->cacheEntriesIterator->rewind();
        }
    }
}
