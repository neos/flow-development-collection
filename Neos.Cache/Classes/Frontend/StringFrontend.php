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

use Neos\Cache\Backend\TaggableBackendInterface;
use Neos\Cache\Exception\InvalidDataException;
use Neos\Cache\Exception\NotSupportedByBackendException;

/**
 * A cache frontend for strings. Nothing else.
 *
 * @api
 */
class StringFrontend extends AbstractFrontend
{
    /**
     * Saves the value of a PHP variable in the cache.
     *
     * @param string $entryIdentifier An identifier used for this cache entry
     * @param string $string The variable to cache
     * @param array $tags Tags to associate with this cache entry
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @return void
     * @throws InvalidDataException
     * @throws \InvalidArgumentException
     * @api
     */
    public function set($entryIdentifier, $string, array $tags = [], $lifetime = null)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233057566);
        }
        if (!is_string($string)) {
            throw new InvalidDataException('Given data is of type "' . gettype($string) . '", but a string is expected for string cache.', 1222808333);
        }
        foreach ($tags as $tag) {
            if (!$this->isValidTag($tag)) {
                throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057512);
            }
        }

        $this->backend->set($entryIdentifier, $string, $tags, $lifetime);
    }

    /**
     * Finds and returns a variable value from the cache.
     *
     * @param string $entryIdentifier Identifier of the cache entry to fetch
     * @return string The value
     * @throws \InvalidArgumentException
     * @api
     */
    public function get($entryIdentifier)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233057752);
        }

        return $this->backend->get($entryIdentifier);
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
            throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057772);
        }

        $entries = [];
        $identifiers = $this->backend->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $entries[$identifier] = $this->backend->get($identifier);
        }
        return $entries;
    }
}
