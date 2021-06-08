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
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Exception;
use Neos\Cache\Frontend\FrontendInterface;

/**
 * A caching backend which stores cache entries by using APCu.
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
class ApcuBackend extends IndependentAbstractBackend implements TaggableBackendInterface, IterableBackendInterface, PhpCapableBackendInterface
{
    use RequireOnceFromValueTrait;

    /**
     * A prefix to seperate stored data from other data possible stored in the APCu
     * @var string
     */
    protected $identifierPrefix;

    /**
     * @var \APCUIterator
     */
    protected $cacheEntriesIterator;

    /**
     * {@inheritdoc}
     */
    public function __construct(EnvironmentConfiguration $environmentConfiguration, array $options)
    {
        if (!extension_loaded('apcu')) {
            throw new Exception('The PHP extension "apcu" must be installed and loaded in order to use the APCu backend.', 1519241835065);
        }
        parent::__construct($environmentConfiguration, $options);
    }

    /**
     * Initializes the identifier prefix when setting the cache.
     *
     * @param FrontendInterface $cache
     * @return void
     */
    public function setCache(FrontendInterface $cache): void
    {
        parent::setCache($cache);

        $pathHash = substr(md5($this->environmentConfiguration->getApplicationIdentifier() . $cache->getIdentifier()), 0, 12);
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
    public function getPrefixedIdentifier(string $entryIdentifier): string
    {
        return $this->identifierPrefix . 'entry_' . $entryIdentifier;
    }

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @return void
     * @throws Exception if no cache frontend has been set.
     * @throws \InvalidArgumentException if the identifier is not valid
     * @api
     */
    public function set(string $entryIdentifier, string $data, array $tags = [], int $lifetime = null): void
    {
        if (!$this->cache instanceof FrontendInterface) {
            throw new Exception('No cache frontend has been set yet via setCache().', 1232986818);
        }

        $tags[] = '%APCUBE%' . $this->cacheIdentifier;
        $expiration = $lifetime !== null ? $lifetime : $this->defaultLifetime;

        $success = apcu_store($this->identifierPrefix . 'entry_' . $entryIdentifier, $data, $expiration);
        if ($success === true) {
            $this->removeIdentifierFromAllTags($entryIdentifier);
            $this->addIdentifierToTags($entryIdentifier, $tags);
        } else {
            throw new Exception('Could not set value.', 1232986877);
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
        $success = false;
        $value = apcu_fetch($this->identifierPrefix . 'entry_' . $entryIdentifier, $success);
        return ($success ? $value : $success);
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
        $success = false;
        apcu_fetch($this->identifierPrefix . 'entry_' . $entryIdentifier, $success);
        return $success;
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return boolean true if (at least) an entry could be removed or false if no entry was found
     * @api
     */
    public function remove(string $entryIdentifier): bool
    {
        $this->removeIdentifierFromAllTags($entryIdentifier);
        return apcu_delete($this->identifierPrefix . 'entry_' . $entryIdentifier);
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
        $success = false;
        $identifiers = apcu_fetch($this->identifierPrefix . 'tag_' . $tag, $success);
        if ($success === false) {
            return [];
        }
        return (array)$identifiers;
    }

    /**
     * Finds all tags for the given identifier. This function uses reverse tag
     * index to search for tags.
     *
     * @param string $identifier Identifier to find tags by
     * @return string[] Array with tags
     */
    protected function findTagsByIdentifier(string $identifier): array
    {
        $success = false;
        $tags = apcu_fetch($this->identifierPrefix . 'ident_' . $identifier, $success);
        return ($success ? (array)$tags : []);
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     * @throws Exception
     * @api
     */
    public function flush(): void
    {
        if (!$this->cache instanceof FrontendInterface) {
            throw new Exception('Yet no cache frontend has been set via setCache().', 1232986971);
        }
        $this->flushByTag('%APCUBE%' . $this->cacheIdentifier);
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
     * Associates the identifier with the given tags
     *
     * @param string $entryIdentifier
     * @param array $tags
     * @return void
     */
    protected function addIdentifierToTags(string $entryIdentifier, array $tags)
    {
        foreach ($tags as $tag) {
            // Update tag-to-identifier index
            $identifiers = $this->findIdentifiersByTag($tag);
            if (array_search($entryIdentifier, $identifiers) === false) {
                $identifiers[] = $entryIdentifier;
                apcu_store($this->identifierPrefix . 'tag_' . $tag, $identifiers);
            }

            // Update identifier-to-tag index
            $existingTags = $this->findTagsByIdentifier($entryIdentifier);
            if (array_search($entryIdentifier, $existingTags) === false) {
                apcu_store($this->identifierPrefix . 'ident_' . $entryIdentifier, array_merge($existingTags, $tags));
            }
        }
    }

    /**
     * Removes association of the identifier with the given tags
     *
     * @param string $entryIdentifier
     * @return void
     */
    protected function removeIdentifierFromAllTags(string $entryIdentifier)
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
            if (($key = array_search($entryIdentifier, $identifiers)) === false) {
                continue;
            }
            unset($identifiers[$key]);
            if (count($identifiers)) {
                apcu_store($this->identifierPrefix . 'tag_' . $tag, $identifiers);
            } else {
                apcu_delete($this->identifierPrefix . 'tag_' . $tag);
            }
        }
        // Clear reverse tag index for this identifier
        apcu_delete($this->identifierPrefix . 'ident_' . $entryIdentifier);
    }

    /**
     * Does nothing, as APCu does GC itself
     *
     * @return void
     * @api
     */
    public function collectGarbage(): void
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
    public function key(): string
    {
        if ($this->cacheEntriesIterator === null) {
            $this->rewind();
        }
        return substr((string)$this->cacheEntriesIterator->key(), strlen($this->identifierPrefix . 'entry_'));
    }

    /**
     * Checks if the current position of the cache entry iterator is valid
     *
     * @return boolean true if the current position is valid, otherwise false
     * @api
     */
    public function valid(): bool
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
            $this->cacheEntriesIterator = new \APCUIterator('/^' . $this->identifierPrefix . 'entry_.*/');
        } else {
            $this->cacheEntriesIterator->rewind();
        }
    }
}
