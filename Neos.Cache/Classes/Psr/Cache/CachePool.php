<?php
namespace Neos\Cache\Psr\Cache;

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
use Neos\Cache\Psr\InvalidArgumentException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * An implementation of the CacheItemPoolInterface from the PSR-6 specification to be used with our provided backends.
 * @see CacheFactory
 */
class CachePool implements CacheItemPoolInterface
{
    /**
     * Pattern an entry identifier must match.
     */
    const PATTERN_ENTRYIDENTIFIER = '/^[a-zA-Z0-9_%\-&]{1,250}$/';

    /**
     * @var BackendInterface
     */
    protected $backend;

    /**
     * An identifier for this cache, useful if you use several different caches.
     *
     * @var string
     */
    protected $identifier;

    /**
     * A list of items still to be persisted.
     *
     * @var array
     */
    protected $deferredItems = [];

    /**
     * Constructs the cache
     *
     * @param string $identifier A identifier which describes this cache
     * @param BackendInterface $backend Backend to be used for this cache
     * @throws \InvalidArgumentException if the identifier doesn't match PATTERN_ENTRYIDENTIFIER
     */
    public function __construct(string $identifier, BackendInterface $backend)
    {
        if (preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier) !== 1) {
            throw new \InvalidArgumentException('"' . $identifier . '" is not a valid cache identifier.', 1203584729);
        }
        $this->identifier = $identifier;
        $this->backend = $backend;
    }

    /**
     * Returns a Cache Item representing the specified key.
     *
     * @param string $key
     * @return CacheItemInterface
     * @throws InvalidArgumentException
     */
    public function getItem($key)
    {
        if (!$this->isValidEntryIdentifier($key)) {
            throw new InvalidArgumentException('"' . $key . '" is not a valid cache entry identifier.', 1514738649629);
        }

        $rawResult = $this->backend->get($key);
        if ($rawResult === false) {
            return new CacheItem($key, false);
        }

        $value = unserialize($rawResult);
        return new CacheItem($key, true, $value);
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     * @return array
     * @throws InvalidArgumentException
     */
    public function getItems(array $keys = [])
    {
        return array_map(function ($key) {
            return $this->getItem($key);
        }, $keys);
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * @param string $key
     * @return bool
     * @throws InvalidArgumentException
     */
    public function hasItem($key)
    {
        if (!$this->isValidEntryIdentifier($key)) {
            throw new InvalidArgumentException('"' . $key . '" is not a valid cache entry identifier.', 1514738924982);
        }

        return $this->backend->has($key);
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     */
    public function clear()
    {
        $this->backend->flush();
        return true;
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     * @return bool
     * @throws InvalidArgumentException
     */
    public function deleteItem($key)
    {
        if (!$this->isValidEntryIdentifier($key)) {
            throw new InvalidArgumentException('"' . $key . '" is not a valid cache entry identifier.', 1514741469583);
        }

        return $this->backend->remove($key);
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     * @return bool
     * @throws InvalidArgumentException
     */
    public function deleteItems(array $keys)
    {
        $deleted = true;
        foreach ($keys as $key) {
            $deleted = $this->deleteItem($key) ? $deleted : false;
        }

        return $deleted;
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     * @return bool
     */
    public function save(CacheItemInterface $item)
    {
        $lifetime = null;
        $expiresAt = null;
        if ($item instanceof CacheItem) {
            $expiresAt = $item->getExpirationDate();
        }

        if ($expiresAt instanceof \DateTimeInterface) {
            $lifetime = $expiresAt->getTimestamp() - (new \DateTime())->getTimestamp();
        }

        $this->backend->set($item->getKey(), serialize($item->get()), [], $lifetime);
        return true;
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     * @return bool
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferredItems[] = $item;
        return true;
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     */
    public function commit()
    {
        foreach ($this->deferredItems as $item) {
            $this->save($item);
        }

        $this->deferredItems = [];

        return true;
    }

    /**
     * Checks the validity of an entry identifier. Returns true if it's valid.
     *
     * @param string $identifier An identifier to be checked for validity
     * @return boolean
     * @api
     */
    public function isValidEntryIdentifier($identifier): bool
    {
        return preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier) === 1;
    }
}
