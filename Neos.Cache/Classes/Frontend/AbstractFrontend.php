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

use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Backend\TaggableBackendInterface;

/**
 * An abstract cache
 *
 * @api
 */
abstract class AbstractFrontend implements FrontendInterface
{
    /**
     * Identifies this cache
     * @var string
     */
    protected $identifier;

    /**
     * @var BackendInterface
     */
    protected $backend;

    /**
     * Constructs the cache
     *
     * @param string $identifier A identifier which describes this cache
     * @param BackendInterface $backend Backend to be used for this cache
     * @throws \InvalidArgumentException if the identifier doesn't match PATTERN_ENTRYIDENTIFIER
     */
    public function __construct($identifier, BackendInterface $backend)
    {
        if (preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier) !== 1) {
            throw new \InvalidArgumentException('"' . $identifier . '" is not a valid cache identifier.', 1203584729);
        }
        $this->identifier = $identifier;
        $this->backend = $backend;
    }

    /**
     * Initializes this frontend
     *
     * The backend is connected with this frontend in initializeObject(), not in __construct(), because the Cache Factory
     * needs an opportunity to register the cache before the backend's setCache() method is called. See
     * CacheFactory::create() for details. A backend (for example the SimpleFileBackend) may rely on the cache already
     * being registered at the CacheManager when its setCache() method is called.
     *
     * @return void
     */
    public function initializeObject()
    {
        // FIXME: This can be removed in next major, since the Backend gets the Frontend set in the cache factory now.
        $this->backend->setCache($this);
    }

    /**
     * Returns this cache's identifier
     *
     * @return string The identifier for this cache
     * @api
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Returns the backend used by this cache
     *
     * @return BackendInterface The backend used by this cache
     * @api
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return boolean TRUE if such an entry exists, FALSE if not
     * @throws \InvalidArgumentException
     * @api
     */
    public function has($entryIdentifier)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058486);
        }

        return $this->backend->has($entryIdentifier);
    }

    /**
     * Removes the given cache entry from the cache.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return boolean TRUE if such an entry exists, FALSE if not
     * @throws \InvalidArgumentException
     * @api
     */
    public function remove($entryIdentifier)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058495);
        }

        return $this->backend->remove($entryIdentifier);
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     * @api
     */
    public function flush()
    {
        $this->backend->flush();
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @return integer The number of entries which have been affected by this flush or NULL if the number is unknown
     * @throws \InvalidArgumentException
     * @api
     */
    public function flushByTag($tag)
    {
        if (!$this->isValidTag($tag)) {
            throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057359);
        }
        if ($this->backend instanceof TaggableBackendInterface) {
            return $this->backend->flushByTag($tag);
        }
    }

    /**
     * Does garbage collection
     *
     * @return void
     * @api
     */
    public function collectGarbage()
    {
        $this->backend->collectGarbage();
    }

    /**
     * Checks the validity of an entry identifier. Returns true if it's valid.
     *
     * @param string $identifier An identifier to be checked for validity
     * @return boolean
     * @api
     */
    public function isValidEntryIdentifier($identifier)
    {
        return preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier) === 1;
    }

    /**
     * Checks the validity of a tag. Returns true if it's valid.
     *
     * @param string $tag An identifier to be checked for validity
     * @return boolean
     * @api
     */
    public function isValidTag($tag)
    {
        return preg_match(self::PATTERN_TAG, $tag) === 1;
    }
}
