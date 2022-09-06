<?php
declare(strict_types=1);

namespace Neos\Cache\Backend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\BackendInstantiationTrait;
use Throwable;

/**
 * A multi backend, falling back to multiple backends if errors occur.
 */
class MultiBackend extends AbstractBackend
{
    use BackendInstantiationTrait;

    protected array $backendConfigurations = [];
    protected array $backends = [];
    protected bool $setInAllBackends = true;
    protected bool $debug = false;
    protected bool $initialized = false;

    /**
     * @throws Throwable
     */
    protected function prepareBackends(): void
    {
        if ($this->initialized === true) {
            return;
        }
        foreach ($this->backendConfigurations as $backendConfiguration) {
            $backendOptions = $backendConfiguration['backendOptions'] ?? [];
            $backend = $this->buildSubBackend($backendConfiguration['backend'], $backendOptions);
            if ($backend !== null) {
                $this->backends[] = $backend;
            }
        }
        $this->initialized = true;
    }

    /**
     * @throws Throwable
     */
    protected function buildSubBackend(string $backendClassName, array $backendOptions): ?BackendInterface
    {
        try {
            $backend = $this->instantiateBackend($backendClassName, $backendOptions, $this->environmentConfiguration);
            $backend->setCache($this->cache);
        } catch (Throwable $t) {
            $this->handleError($t);
            $backend = null;
        }
        return $backend;
    }

    /**
     * @throws Throwable
     */
    public function set(string $entryIdentifier, string $data, array $tags = [], int $lifetime = null): void
    {
        $this->prepareBackends();
        foreach ($this->backends as $backend) {
            try {
                $backend->set($entryIdentifier, $data, $tags, $lifetime);
            } catch (Throwable $t) {
                $this->handleError($t);
                if (!$this->setInAllBackends) {
                    return;
                }
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function get(string $entryIdentifier)
    {
        $this->prepareBackends();
        foreach ($this->backends as $backend) {
            try {
                return $backend->get($entryIdentifier);
            } catch (Throwable $t) {
                $this->handleError($t);
            }
        }
        return false;
    }

    /**
     * @throws Throwable
     */
    public function has(string $entryIdentifier): bool
    {
        $this->prepareBackends();
        foreach ($this->backends as $backend) {
            try {
                return $backend->has($entryIdentifier);
            } catch (Throwable $t) {
                $this->handleError($t);
            }
        }
        return false;
    }

    /**
     * @throws Throwable
     */
    public function remove(string $entryIdentifier): bool
    {
        $this->prepareBackends();
        $result = false;
        foreach ($this->backends as $backend) {
            try {
                $result = $result || $backend->remove($entryIdentifier);
            } catch (Throwable $t) {
                $this->handleError($t);
            }
        }
        return $result;
    }

    /**
     * @throws Throwable
     */
    public function flush(): void
    {
        $this->prepareBackends();
        foreach ($this->backends as $backend) {
            try {
                $backend->flush();
            } catch (Throwable $t) {
                $this->handleError($t);
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function collectGarbage(): void
    {
        $this->prepareBackends();
        foreach ($this->backends as $backend) {
            try {
                $backend->collectGarbage();
            } catch (Throwable $t) {
                $this->handleError($t);
            }
        }
    }

    /**
     * This setter is used by AbstractBackend::setProperties()
     */
    protected function setBackendConfigurations(array $backendConfigurations): void
    {
        $this->backendConfigurations = $backendConfigurations;
    }

    /**
     * This setter is used by AbstractBackend::setProperties()
     */
    protected function setSetInAllBackends(bool $setInAllBackends): void
    {
        $this->setInAllBackends = $setInAllBackends;
    }

    /**
     * This setter is used by AbstractBackend::setProperties()
     */
    protected function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * @throws Throwable
     */
    protected function handleError(Throwable $throwable): void
    {
        if ($this->debug) {
            throw $throwable;
        }
    }
}
