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

/**
 * @internal
 */
class SessionKeyValueStore
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

    public function has(StorageIdentifier $storageIdentifier, string $key): bool
    {
        $entryIdentifier = $this->createEntryIdentifier($storageIdentifier, $key);
        return $this->cache->has($entryIdentifier);
    }

    public function retrieve(StorageIdentifier $storageIdentifier, string $key): mixed
    {
        $entryIdentifier = $this->createEntryIdentifier($storageIdentifier, $key);
        $serializedResult = $this->cache->get($entryIdentifier);
        $this->writeDebounceHashes[$storageIdentifier->value][$key] = md5($serializedResult);
        return ($this->useIgBinary === true) ? igbinary_unserialize($serializedResult) : unserialize($serializedResult);
    }

    public function store(StorageIdentifier $storageIdentifier, string $key, mixed $value): void
    {
        $entryIdentifier = $this->createEntryIdentifier($storageIdentifier, $key);
        $serializedValue = ($this->useIgBinary === true) ? igbinary_serialize($value) : serialize($value);
        $valueHash = md5($serializedValue);
        $debounceHash = $this->writeDebounceHashes[$storageIdentifier->value][$key] ?? null;
        if ($debounceHash !== null && $debounceHash === $valueHash) {
            return;
        }

        $this->writeDebounceHashes[$storageIdentifier->value][$key] = $valueHash;
        $this->cache->set($entryIdentifier, $serializedValue, [$storageIdentifier->value], 0);
    }

    public function remove(StorageIdentifier $storageIdentifier): int
    {
        if (array_key_exists($storageIdentifier->value, $this->writeDebounceHashes)) {
            unset($this->writeDebounceHashes[$storageIdentifier->value]);
        }
        return $this->cache->flushByTag($storageIdentifier->value);
    }

    private function createEntryIdentifier(StorageIdentifier $storageIdentifier, $key): string
    {
        return $storageIdentifier->value . md5($key);
    }
}
