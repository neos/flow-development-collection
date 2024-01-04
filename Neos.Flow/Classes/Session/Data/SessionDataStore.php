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
use Neos\Cache\Frontend\StringFrontend;

class SessionDataStore
{
    protected StringFrontend $cache;

    protected bool $useIgBinary = false;

    /**
     * @var array<string, array<string, string>>
     */
    protected array $writeDebounceHashes = [];

    public function injectCache(StringFrontend $cache): void
    {
        $this->cache = $cache;
    }

    public function initializeObject(): void
    {
        if (!$this->cache->getBackend() instanceof IterableBackendInterface) {
            throw new InvalidBackendException(sprintf('The session storage cache must provide a backend implementing the IterableBackendInterface, but the given backend "%s" does not implement it.', get_class($this->cache->getBackend())), 1370964558);
        }
        $this->useIgBinary = extension_loaded('igbinary');
    }

    public function has(SessionMetaData $sessionMetaData, string $key): bool
    {
        $entryIdentifier = $this->createEntryIdentifier($sessionMetaData, $key);
        return $this->cache->has($entryIdentifier);
    }

    public function retrieve(SessionMetaData $sessionMetaData, string $key): mixed
    {
        $entryIdentifier = $this->createEntryIdentifier($sessionMetaData, $key);
        $serializedResult = $this->cache->get($entryIdentifier);
        $this->writeDebounceHashes[$sessionMetaData->getStorageIdentifier()][$key] = md5($serializedResult);
        return ($this->useIgBinary === true) ? igbinary_unserialize($serializedResult) : unserialize($serializedResult);
    }

    public function store(SessionMetaData $sessionMetaData, string $key, mixed $value): void
    {
        $entryIdentifier = $this->createEntryIdentifier($sessionMetaData, $key);
        $serializedValue = ($this->useIgBinary === true) ? igbinary_serialize($value) : serialize($value);
        $currentHash = md5($serializedValue);
        $debounceHash = $this->writeDebounceHashes[$sessionMetaData->getStorageIdentifier()][$key] ?? null;
        if ($debounceHash !== null && $debounceHash === $currentHash) {
            // the same value is already stored we can skip this
            return;
        }
        $this->writeDebounceHashes[$sessionMetaData->getStorageIdentifier()][$key] = $currentHash;
        $this->cache->set($entryIdentifier, $serializedValue, [$sessionMetaData->getStorageIdentifier()], 0);
    }

    public function remove(SessionMetaData $sessionMetaData): int
    {
        if (array_key_exists($sessionMetaData->getStorageIdentifier(), $this->writeDebounceHashes)) {
            unset($this->writeDebounceHashes[$sessionMetaData->getStorageIdentifier()]);
        }
        return $this->cache->flushByTag($sessionMetaData->getStorageIdentifier());
    }

    private function createEntryIdentifier(SessionMetaData $metadata, $key): string
    {
        return $metadata->getStorageIdentifier() . md5($key);
    }
}
