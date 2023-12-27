<?php
declare(strict_types=1);

namespace Neos\Flow\Session\Data;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\IterableBackendInterface;
use Neos\Cache\Exception\InvalidBackendException;
use Neos\Cache\Frontend\CacheEntryIterator;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Session\Exception\InvalidDataInSessionDataStoreException;
use Neos\Flow\Session\Session;

class SessionMetaDataStore
{
    protected VariableFrontend $cache;

    protected const SESSION_TAG = 'session';

    protected const TAG_PREFIX = 'customtag-';

    protected const GARBAGE_COLLECTION_CACHEIDENTIFIER = '_garbage-collection-running';

    public function injectCache(VariableFrontend $cache): void
    {
        $this->cache = $cache;
    }

    public function initializeObject()
    {
        if (!$this->cache->getBackend() instanceof IterableBackendInterface) {
            throw new InvalidBackendException(sprintf('The session storage cache must provide a backend implementing the IterableBackendInterface, but the given backend "%s" does not implement it.', get_class($this->cache->getBackend())), 1370964558);
        }
    }

    public function isValidEntryIdentifier(string $entryIdentifier): bool
    {
        return $this->cache->isValidEntryIdentifier($entryIdentifier);
    }

    public function isValidTag(string $tag): bool
    {
        return $this->cache->isValidTag($tag);
    }

    public function has(string $entryIdentifier): bool
    {
        return $this->cache->has($entryIdentifier);
    }

    public function findBySessionIdentifier(string $sessionIdentifier): ?SessionMetaData
    {
        /**
         * @var $metaDataFromCache false|array|SessionMetaData
         */
        $metaDataFromCache = $this->cache->get($sessionIdentifier);
        if ($metaDataFromCache === false) {
            return null;
        } elseif ($metaDataFromCache instanceof SessionMetaData) {
            return $metaDataFromCache;
        } elseif (is_array($metaDataFromCache)) {
            return SessionMetaData::fromSessionIdentifierAndArray($sessionIdentifier, $metaDataFromCache);
        }
        throw new InvalidDataInSessionDataStoreException();
    }

    /**
     * @param string $tag
     * @return \Generator<string, SessionMetaData> Session metadata indexed by session id
     */
    public function findByTag(string $tag): \Generator
    {
        foreach ($this->cache->getByTag(self::TAG_PREFIX . $tag) as $sessionIdentifier => $sessionMetaData) {
            if ($sessionIdentifier === self::GARBAGE_COLLECTION_CACHEIDENTIFIER) {
                continue;
            }
            if ($sessionMetaData instanceof SessionMetaData) {
                yield $sessionIdentifier => $sessionMetaData;
            } elseif (is_array($sessionMetaData)) {
                yield $sessionIdentifier => SessionMetaData::fromSessionIdentifierAndArray($sessionIdentifier, $sessionMetaData);
            }
        }
    }

    public function store(SessionMetaData $sessionMetaData): void
    {
        $tagsForCacheEntry = array_map(function ($tag) {
            return self::TAG_PREFIX . $tag;
        }, $sessionMetaData->getTags());
        $tagsForCacheEntry[] = $sessionMetaData->getSessionIdentifier();
        $tagsForCacheEntry[] = self::SESSION_TAG;

        $this->cache->set($sessionMetaData->getSessionIdentifier(), $sessionMetaData, $tagsForCacheEntry, 0);
    }

    public function remove(string $entryIdentifier): mixed
    {
        return $this->cache->remove($entryIdentifier);
    }

    /**
     * @return \Generator<string, SessionMetaData>
     */
    public function findAll(): \Generator
    {
        foreach ($this->cache->getIterator() as $sessionIdentifier => $sessionMetaData) {
            if ($sessionIdentifier === self::GARBAGE_COLLECTION_CACHEIDENTIFIER) {
                continue;
            }
            if ($sessionMetaData instanceof SessionMetaData) {
                yield $sessionIdentifier => $sessionMetaData;
            } elseif (is_array($sessionMetaData)) {
                yield $sessionIdentifier => SessionMetaData::fromSessionIdentifierAndArray($sessionIdentifier, $sessionMetaData);
            }
        }
    }

    public function flushByTag(string $tag): CacheEntryIterator
    {
        return $this->cache->flushByTag($tag);
    }

    public function startGarbageCollection(): void
    {
        $this->cache->set(self::GARBAGE_COLLECTION_CACHEIDENTIFIER, true, [], 120);
    }

    public function isGarbageCollectionRunning(): bool
    {
        return $this->cache->has(self::GARBAGE_COLLECTION_CACHEIDENTIFIER);
    }

    public function endGarbageCollection(): void
    {
        $this->cache->remove(self::GARBAGE_COLLECTION_CACHEIDENTIFIER);
    }
}
