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
use Neos\Cache\Exception;
use Neos\Cache\Exception\InvalidBackendException;
use Neos\Cache\Exception\NotSupportedByBackendException;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations\InjectConfiguration;
use Neos\Flow\Session\Exception\InvalidDataInSessionDataStoreException;

/**
 * @internal
 */
class SessionMetaDataStore
{
    protected const TAG_PREFIX = 'customtag-';
    protected const GARBAGE_COLLECTION_CACHE_IDENTIFIER = '_garbage-collection-running';

    #[InjectConfiguration(path: "session.updateMetadataThreshold")]
    protected ?int $updateMetadataThreshold = null;

    protected VariableFrontend $cache;

    /**
     * @var array<string, SessionMetaData>
     */
    protected $writeDebounceCache = [];

    public function injectCache(VariableFrontend $cache): void
    {
        $this->cache = $cache;
    }

    public function initializeObject(): void
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

    public function has(SessionIdentifier $sessionIdentifier): bool
    {
        return $this->cache->has($sessionIdentifier->value);
    }

    /**
     * @throws InvalidDataInSessionDataStoreException
     */
    public function retrieve(SessionIdentifier $sessionIdentifier): ?SessionMetaData
    {
        $metaDataFromCache = $this->cache->get($sessionIdentifier->value);
        if ($metaDataFromCache === false) {
            return null;
        }

        if ($metaDataFromCache instanceof SessionMetaData) {
            $this->writeDebounceCache[$metaDataFromCache->sessionIdentifier->value] = $metaDataFromCache;
            return $metaDataFromCache;
        }

        if (is_array($metaDataFromCache)) {
            $metaDataFromCache = SessionMetaData::createFromSessionIdentifierStringAndOldArrayCacheFormat($sessionIdentifier->value, $metaDataFromCache);
            $this->writeDebounceCache[$metaDataFromCache->sessionIdentifier->value] = $metaDataFromCache;
            return $metaDataFromCache;
        }
        throw new InvalidDataInSessionDataStoreException();
    }

    /**
     * @param string $tag
     * @return \Generator<string, SessionMetaData> Session metadata indexed by sessionIdentifier
     * @throws NotSupportedByBackendException
     */
    public function retrieveByTag(string $tag): \Generator
    {
        foreach ($this->cache->getByTag(self::TAG_PREFIX . $tag) as $sessionIdentifier => $sessionMetaData) {
            if ($sessionIdentifier === self::GARBAGE_COLLECTION_CACHE_IDENTIFIER) {
                continue;
            }
            if ($sessionMetaData instanceof SessionMetaData) {
                $this->writeDebounceCache[$sessionIdentifier] = $sessionMetaData;
                yield $sessionIdentifier => $sessionMetaData;
            } elseif (is_array($sessionMetaData)) {
                $sessionMetaData = SessionMetaData::createFromSessionIdentifierStringAndOldArrayCacheFormat($sessionIdentifier, $sessionMetaData);
                $this->writeDebounceCache[$sessionIdentifier] = $sessionMetaData;
                yield $sessionIdentifier => $sessionMetaData;
            }
        }
    }

    /**
     * @return \Generator<string, SessionMetaData> Session metadata indexed by sessionIdentifier
     * @throws NotSupportedByBackendException
     */
    public function retrieveAll(): \Generator
    {
        foreach ($this->cache->getIterator() as $sessionIdentifier => $sessionMetaData) {
            if ($sessionIdentifier === self::GARBAGE_COLLECTION_CACHE_IDENTIFIER) {
                continue;
            }
            if ($sessionMetaData instanceof SessionMetaData) {
                $this->writeDebounceCache[$sessionIdentifier] = $sessionMetaData;
                yield $sessionIdentifier => $sessionMetaData;
            } elseif (is_array($sessionMetaData)) {
                $sessionMetaData = SessionMetaData::createFromSessionIdentifierStringAndOldArrayCacheFormat($sessionIdentifier, $sessionMetaData);
                $this->writeDebounceCache[$sessionIdentifier] = $sessionMetaData;
                yield $sessionIdentifier => $sessionMetaData;
            }
        }
    }

    /**
     * @throws Exception
     */
    public function store(SessionMetaData $sessionMetaData): void
    {
        $tagsForCacheEntry = array_map(function ($tag) {
            return self::TAG_PREFIX . $tag;
        }, $sessionMetaData->tags);
        $tagsForCacheEntry[] = $sessionMetaData->sessionIdentifier->value;

        // check whether the same data with an age < updateMetadataThreshold was just read to avoid pointless write operations
        $metaDataFromDebounceCache = $this->writeDebounceCache[$sessionMetaData->sessionIdentifier->value] ?? null;
        if ($metaDataFromDebounceCache !== null && $this->updateMetadataThreshold > 0) {
            if ($sessionMetaData->isSame($metaDataFromDebounceCache) && $sessionMetaData->ageDifference($metaDataFromDebounceCache) < $this->updateMetadataThreshold) {
                return;
            }
        }

        $this->writeDebounceCache[$sessionMetaData->sessionIdentifier->value] = $sessionMetaData;
        $this->cache->set($sessionMetaData->sessionIdentifier->value, $sessionMetaData, $tagsForCacheEntry, 0);
    }

    public function remove(SessionMetaData $sessionMetaData): bool
    {
        unset($this->writeDebounceCache[$sessionMetaData->sessionIdentifier->value]);
        return $this->cache->remove($sessionMetaData->sessionIdentifier->value);
    }

    /**
     * @throws Exception
     */
    public function startGarbageCollection(): void
    {
        $this->cache->set(self::GARBAGE_COLLECTION_CACHE_IDENTIFIER, true, [], 120);
    }

    public function isGarbageCollectionRunning(): bool
    {
        return $this->cache->has(self::GARBAGE_COLLECTION_CACHE_IDENTIFIER);
    }

    public function endGarbageCollection(): void
    {
        $this->cache->remove(self::GARBAGE_COLLECTION_CACHE_IDENTIFIER);
    }
}
