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

    public function get(string $entryIdentifier): mixed
    {
        return $this->cache->get($entryIdentifier);
    }

    public function set(string $entryIdentifier, mixed $value, array $tags = [], int $lifetime = null): mixed
    {
        return $this->cache->set($entryIdentifier, $value, $tags, $lifetime);
    }

    public function remove(string $entryIdentifier): mixed
    {
        return $this->cache->remove($entryIdentifier);
    }

    public function flushByTag(string $tag): int
    {
        return $this->cache->flushByTag($tag);
    }
}
