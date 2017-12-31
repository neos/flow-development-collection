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

use Neos\Cache\Exception\PsrInvalidArgumentException;
use Neos\Cache\Psr\PsrCacheItem;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * A frontend that implements the CacheItemPoolInterface from the PSR-6 specification.
 */
class PsrFrontend extends VariableFrontend implements CacheItemPoolInterface
{
    /**
     * A list of items still to be persisted.
     *
     * @var array
     */
    protected $deferredItems = [];

    /**
     * Returns a Cache Item representing the specified key.
     *
     * @param string $key
     * @return CacheItemInterface
     * @throws PsrInvalidArgumentException
     */
    public function getItem($key)
    {
        if (!$this->isValidEntryIdentifier($key)) {
            throw new PsrInvalidArgumentException('"' . $key . '" is not a valid cache entry identifier.', 1514738649629);
        }

        $rawResult = $this->backend->get($key);
        if ($rawResult === false) {
            return new PsrCacheItem($key, false);
        }

        $value = ($this->useIgBinary === true) ? igbinary_unserialize($rawResult) : unserialize($rawResult);
        return new PsrCacheItem($key, true, $value);
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     * @return array
     * @throws PsrInvalidArgumentException
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
     * @throws PsrInvalidArgumentException
     */
    public function hasItem($key)
    {
        if (!$this->isValidEntryIdentifier($key)) {
            throw new PsrInvalidArgumentException('"' . $key . '" is not a valid cache entry identifier.', 1514738924982);
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
        $this->flush();
        return true;
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     * @return bool
     * @throws PsrInvalidArgumentException
     */
    public function deleteItem($key)
    {
        if (!$this->isValidEntryIdentifier($key)) {
            throw new PsrInvalidArgumentException('"' . $key . '" is not a valid cache entry identifier.', 1514741469583);
        }

        return $this->remove($key);
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     * @return bool
     * @throws PsrInvalidArgumentException
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
        if ($item instanceof PsrCacheItem) {
            $expiresAt = $item->getExpirationDate();
        }

        if ($expiresAt instanceof \DateTimeInterface) {
            $lifetime = $expiresAt->getTimestamp() - (new \DateTime())->getTimestamp();
        }

        $this->set($item->getKey(), $item->get(), [], $lifetime);
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
}
