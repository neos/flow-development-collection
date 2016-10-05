<?php
namespace TYPO3\Flow\Cache\Frontend;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cache\Backend\IterableBackendInterface;
use TYPO3\Flow\Cache\Exception\NotSupportedByBackendException;

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
     * @throws \InvalidArgumentException
     * @api
     */
    public function getByTag($tag)
    {
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
     * @param integer $chunkSize Determines the number of entries fetched by the backend at once (not supported yet, for future use)
     * @return \TYPO3\Flow\Cache\Frontend\CacheEntryIterator
     * @throws NotSupportedByBackendException
     */
    public function getIterator($chunkSize = null)
    {
        if (!$this->backend instanceof IterableBackendInterface) {
            throw new NotSupportedByBackendException('The cache backend (%s) configured for cach "%s" does cannot be used as an iterator. Please choose a different cache backend or adjust the code using this cache.', 1371463860);
        }
        return new CacheEntryIterator($this, $this->backend, $chunkSize);
    }
}
