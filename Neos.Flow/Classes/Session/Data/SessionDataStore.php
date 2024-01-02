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

class SessionDataStore
{
    private const FLOW_OBJECT_STORAGE_KEY = 'Neos_Flow_Object_ObjectManager';
    private const FLOW_ACCOUNTS_STORAGE_KEY = 'Neos_Flow_Security_Accounts';

    protected VariableFrontend $cache;

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

    public function has(SessionMetaData $sessionMetaData, string $key): bool
    {
        $entryIdentifier = $this->creteEntryIdentifier($sessionMetaData, $key);
        return $this->cache->has($entryIdentifier);
    }

    public function retrieve(SessionMetaData $sessionMetaData, string $key): mixed
    {
        $entryIdentifier = $this->creteEntryIdentifier($sessionMetaData, $key);
        return $this->cache->get($entryIdentifier);
    }

    public function store(SessionMetaData $sessionMetaData, string $key, mixed $value): void
    {
        $entryIdentifier = $this->creteEntryIdentifier($sessionMetaData, $key);
        $this->cache->set($entryIdentifier, $value, [$sessionMetaData->getStorageIdentifier()], 0);
    }

    public function storeFlowObjectsForSessionMetadata(SessionMetaData $sessionMetaData, array $objects): void
    {
        $this->store($sessionMetaData, self::FLOW_OBJECT_STORAGE_KEY, $objects);
    }

    /**
     * @param SessionMetaData $sessionMetaData
     * @param string[] $accounts Accounts and Providers
     * @return void
     */
    public function storeFlowAccountsForSessionMetadata(SessionMetaData $sessionMetaData, array $accounts): void
    {
        $this->store($sessionMetaData, self::FLOW_ACCOUNTS_STORAGE_KEY, $accounts);
    }

    public function retrieveFlowObjectsForSessionMetadata(SessionMetaData $sessionMetaData): array
    {
        return $this->retrieve($sessionMetaData, self::FLOW_OBJECT_STORAGE_KEY);
    }

    public function remove(SessionMetaData $sessionMetaData): int
    {
        return $this->cache->flushByTag($sessionMetaData->getStorageIdentifier());
    }

    private function creteEntryIdentifier(SessionMetaData $metadata, $key): string
    {
        return $metadata->getStorageIdentifier() . md5($key);
    }
}
