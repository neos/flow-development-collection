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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\FLow\Annotations as Flow;
use Neos\Flow\Session\Exception\InvalidDataInSessionDataStoreException;

class SessionMetaDataStore
{
    protected VariableFrontend $cache;

    protected const TAG_PREFIX = 'customtag-';

    protected const GARBAGE_COLLECTION_CACHEIDENTIFIER = '_garbage-collection-running';

    /**
     * @var int|null
     * @Flow\InjectConfiguration(path="session.updateMetadataThreshold")
     */
    protected $updateMetadataThreshold = null;

    /**
     * @var array<string, SessionMetaData>
     */
    protected $writeDebounceCache = [];

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

    public function isValidSessionIdentifier(string $sessionIdentifier): bool
    {
        return $this->cache->isValidEntryIdentifier($sessionIdentifier);
    }

    public function isValidSessionTag(string $tag): bool
    {
        return $this->cache->isValidTag(self::TAG_PREFIX . $tag);
    }

    public function has(string $sessionIdentifier): bool
    {
        return $this->cache->has($sessionIdentifier);
    }

    public function retrieve(string $sessionIdentifier): ?SessionMetaData
    {
        /**
         * @var $metaDataFromCache false|array|SessionMetaData
         */
        $metaDataFromCache = $this->cache->get($sessionIdentifier);
        if ($metaDataFromCache === false) {
            return null;
        } elseif ($metaDataFromCache instanceof SessionMetaData) {
            $this->writeDebounceCache[$metaDataFromCache->sessionIdentifier] = $metaDataFromCache;
            return $metaDataFromCache;
        } elseif (is_array($metaDataFromCache)) {
            $metaDataFromCache = SessionMetaData::createFromSessionIdentifierAndOldArrayCacheFormat($sessionIdentifier, $metaDataFromCache);
            $this->writeDebounceCache[$metaDataFromCache->sessionIdentifier] = $metaDataFromCache;
            return $metaDataFromCache;
        }
        throw new InvalidDataInSessionDataStoreException();
    }

    /**
     * @param string $tag
     * @return \Generator<string, SessionMetaData> Session metadata indexed by sessionIdentifier
     */
    public function retrieveByTag(string $tag): \Generator
    {
        foreach ($this->cache->getByTag(self::TAG_PREFIX . $tag) as $sessionIdentifier => $sessionMetaData) {
            if ($sessionIdentifier === self::GARBAGE_COLLECTION_CACHEIDENTIFIER) {
                continue;
            }
            if ($sessionMetaData instanceof SessionMetaData) {
                $this->writeDebounceCache[$sessionIdentifier] = $sessionMetaData;
                yield $sessionIdentifier => $sessionMetaData;
            } elseif (is_array($sessionMetaData)) {
                $sessionMetaData = SessionMetaData::createFromSessionIdentifierAndOldArrayCacheFormat($sessionIdentifier, $sessionMetaData);
                $this->writeDebounceCache[$sessionIdentifier] = $sessionMetaData;
                yield $sessionIdentifier => $sessionMetaData;
            }
        }
    }

    /**
     * @return \Generator<string, SessionMetaData> Session metadata indexed by sessionIdentifier
     */
    public function retrieveAll(): \Generator
    {
        foreach ($this->cache->getIterator() as $sessionIdentifier => $sessionMetaData) {
            if ($sessionIdentifier === self::GARBAGE_COLLECTION_CACHEIDENTIFIER) {
                continue;
            }
            if ($sessionMetaData instanceof SessionMetaData) {
                $this->writeDebounceCache[$sessionIdentifier] = $sessionMetaData;
                yield $sessionIdentifier => $sessionMetaData;
            } elseif (is_array($sessionMetaData)) {
                $sessionMetaData = SessionMetaData::createFromSessionIdentifierAndOldArrayCacheFormat($sessionIdentifier, $sessionMetaData);
                $this->writeDebounceCache[$sessionIdentifier] = $sessionMetaData;
                yield $sessionIdentifier => $sessionMetaData;
            }
        }
    }

    public function store(SessionMetaData $sessionMetaData): void
    {
        $tagsForCacheEntry = array_map(function ($tag) {
            return self::TAG_PREFIX . $tag;
        }, $sessionMetaData->tags);
        $tagsForCacheEntry[] = $sessionMetaData->sessionIdentifier;

        // check whether the same data with an age < updateMetadataThreshold was just read to avoid pointless write operations
        $metaDataFromDebounceCache = $this->writeDebounceCache[$sessionMetaData->sessionIdentifier] ?? null;
        if ($metaDataFromDebounceCache !== null && $this->updateMetadataThreshold > 0) {
            if ($sessionMetaData->isSame($metaDataFromDebounceCache) && $sessionMetaData->ageDifference($metaDataFromDebounceCache) < $this->updateMetadataThreshold) {
                return;
            }
        }

        $this->writeDebounceCache[$sessionMetaData->sessionIdentifier] = $sessionMetaData;
        $this->cache->set($sessionMetaData->sessionIdentifier, $sessionMetaData, $tagsForCacheEntry, 0);
    }

    public function remove(SessionMetaData $sessionMetaData): mixed
    {
        unset($this->writeDebounceCache[$sessionMetaData->sessionIdentifier]);
        return $this->cache->remove($sessionMetaData->sessionIdentifier);
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
