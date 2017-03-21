<?php
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

use Neos\Cache\Backend\IterableBackendInterface;
use Neos\Cache\Backend\TaggableBackendInterface;
use Neos\Cache\Exception\NotSupportedByBackendException;

/**
 * A cache frontend for any kinds of PHP variables
 *
 * @api
 */
class VariableFrontend extends AbstractFrontend
{
    /**
     * If the extension "igbinary" is installed, use it for increased performance.
     * Caching the result of extension_loaded() here is faster than calling extension_loaded() multiple times.
     *
     * @var boolean
     */
    protected $useIgBinary = false;

    /**
     * Initializes this cache frontend
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->useIgBinary = extension_loaded('igbinary');
        parent::initializeObject();
    }

    /**
     * Saves the value of a PHP variable in the cache. Note that the variable
     * will be serialized if necessary.
     *
     * @param string $entryIdentifier An identifier used for this cache entry
     * @param mixed $variable The variable to cache
     * @param array $tags Tags to associate with this cache entry
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @return void
     * @throws \InvalidArgumentException
     * @api
     */
    public function set($entryIdentifier, $variable, array $tags = [], $lifetime = null)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058264);
        }
        foreach ($tags as $tag) {
            if (!$this->isValidTag($tag)) {
                throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233058269);
            }
        }
        if ($this->useIgBinary === true) {
            $this->backend->set($entryIdentifier, igbinary_serialize($variable), $tags, $lifetime);
        } else {
            $this->backend->set($entryIdentifier, serialize($variable), $tags, $lifetime);
        }
    }

    /**
     * Finds and returns a variable value from the cache.
     *
     * @param string $entryIdentifier Identifier of the cache entry to fetch
     * @return mixed The value
     * @throws \InvalidArgumentException
     * @api
     */
    public function get($entryIdentifier)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058294);
        }

        $rawResult = $this->backend->get($entryIdentifier);
        if ($rawResult === false) {
            return false;
        } else {
            return ($this->useIgBinary === true) ? igbinary_unserialize($rawResult) : unserialize($rawResult);
        }
    }

    /**
     * Finds and returns all cache entries which are tagged by the specified tag.
     *
     * @param string $tag The tag to search for
     * @return array An array with the identifier (key) and content (value) of all matching entries. An empty array if no entries matched
     * @throws NotSupportedByBackendException
     * @throws \InvalidArgumentException
     * @api
     */
    public function getByTag($tag)
    {
        if (!$this->backend instanceof TaggableBackendInterface) {
            throw new NotSupportedByBackendException('The backend must implement TaggableBackendInterface. Please choose a different cache backend or adjust the code using this cache.', 1483487409);
        }
        if (!$this->isValidTag($tag)) {
            throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233058312);
        }

        $entries = [];
        $identifiers = $this->backend->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $rawResult = $this->backend->get($identifier);
            if ($rawResult !== false) {
                $entries[$identifier] = ($this->useIgBinary === true) ? igbinary_unserialize($rawResult) : unserialize($rawResult);
            }
        }
        return $entries;
    }

    /**
     * Returns an iterator over the entries of this cache
     *
     * @return \Neos\Cache\Frontend\CacheEntryIterator
     * @throws NotSupportedByBackendException
     */
    public function getIterator()
    {
        if (!$this->backend instanceof IterableBackendInterface) {
            throw new NotSupportedByBackendException('The cache backend (%s) configured for cache "%s" cannot be used as an iterator. Please choose a different cache backend or adjust the code using this cache.', 1371463860);
        }
        return new CacheEntryIterator($this, $this->backend);
    }
}
