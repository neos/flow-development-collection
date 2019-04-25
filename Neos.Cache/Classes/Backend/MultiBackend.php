<?php
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
use Neos\Cache\EnvironmentConfiguration;

/**
 * A multi backend, falling back to multiple backends if errors occur.
 */
class MultiBackend extends AbstractBackend implements BackendInterface
{
    use BackendInstantiationTrait;

    /**
     * Configuration for all sub backends (each with the keys "backend" and "backendOptions")
     *
     * @var array
     */
    protected $backendConfigurations = [];

    /**
     * @var BackendInterface[]
     */
    protected $backends = [];

    /**
     * @var bool
     */
    protected $setInAllBackends = true;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * Are the backends initialized
     *
     * @var bool
     */
    protected $initialized = false;

    /**
     * Constructs this backend
     *
     * @param EnvironmentConfiguration $environmentConfiguration
     * @param array $options Configuration options - depends on the actual backend
     */
    public function __construct(EnvironmentConfiguration $environmentConfiguration, array $options)
    {
        parent::__construct($environmentConfiguration, $options);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    protected function prepareBackends()
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
     * @param string $backendClassName
     * @param array $backendOptions
     * @return BackendInterface
     * @throws \Throwable
     */
    protected function buildSubBackend(string $backendClassName, array $backendOptions): ?BackendInterface
    {
        $backend = null;
        try {
            $backend = $this->instantiateBackend($backendClassName, $backendOptions, $this->environmentConfiguration);
            $backend->setCache($this->cache);
        } catch (\Throwable $t) {
            $this->handleError($t);
            $backend = null;
        }

        return $backend;
    }

    /**
     * @param string $entryIdentifier
     * @param string $data
     * @param array $tags
     * @param int|null $lifetime
     * @throws \Throwable
     */
    public function set(string $entryIdentifier, string $data, array $tags = [], int $lifetime = null)
    {
        $this->prepareBackends();
        foreach ($this->backends as $backend) {
            try {
                $this->setInBackend($backend, $entryIdentifier, $data, $tags, $lifetime);
            } catch (\Throwable $t) {
                $this->handleError($t);
                if (!$this->setInAllBackends) {
                    return;
                }
            }
        }
    }

    /**
     * @param string $entryIdentifier
     * @return bool|mixed
     * @throws \Throwable
     */
    public function get(string $entryIdentifier)
    {
        $this->prepareBackends();
        $result = false;
        foreach ($this->backends as $backend) {
            try {
                $result = $this->getFromBackend($backend, $entryIdentifier);

                return $result;
            } catch (\Throwable $t) {
                $this->handleError($t);
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @param string $entryIdentifier
     * @return bool
     * @throws \Throwable
     */
    public function has(string $entryIdentifier): bool
    {
        $this->prepareBackends();
        $result = false;
        foreach ($this->backends as $backend) {
            try {
                $result = $this->backendHas($backend, $entryIdentifier);

                return $result;
            } catch (\Throwable $t) {
                $this->handleError($t);
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @param string $entryIdentifier
     * @return bool
     * @throws \Throwable
     */
    public function remove(string $entryIdentifier): bool
    {
        $this->prepareBackends();
        $result = false;
        foreach ($this->backends as $backend) {
            try {
                $result = $result || $this->removeFromBackend($backend, $entryIdentifier);
            } catch (\Throwable $t) {
                $this->handleError($t);
            }
        }

        return $result;
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function flush()
    {
        $this->prepareBackends();
        foreach ($this->backends as $backend) {
            try {
                $this->flushBackend($backend);
            } catch (\Throwable $t) {
                $this->handleError($t);
            }
        }
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function collectGarbage()
    {
        $this->prepareBackends();
        foreach ($this->backends as $backend) {
            try {
                $backend->collectGarbage();
            } catch (\Throwable $t) {
                $this->handleError($t);
            }
        }
    }

    /**
     * @param array $backendConfigurations
     * @return void
     */
    protected function setBackendConfigurations(array $backendConfigurations)
    {
        $this->backendConfigurations = $backendConfigurations;
    }

    /**
     * @param bool $setInAllBackends
     * @return void
     */
    protected function setSetInAllBackends(bool $setInAllBackends)
    {
        $this->setInAllBackends = $setInAllBackends;
    }

    /**
     * @param bool $debug
     * @return void
     */
    protected function setDebug(bool $debug)
    {
        $this->debug = $debug;
    }

    /**
     * @param BackendInterface $backend
     * @param string $entryIdentifier
     * @param string $data
     * @param array $tags
     * @param int|null $lifetime
     * @return void
     * @throws \Neos\Cache\Exception
     */
    protected function setInBackend(BackendInterface $backend, string $entryIdentifier, string $data, array $tags = [], int $lifetime = null)
    {
        $backend->set($entryIdentifier, $data, $tags, $lifetime);
    }

    /**
     * @param BackendInterface $backend
     * @param string $entryIdentifier
     * @return mixed
     */
    protected function getFromBackend(BackendInterface $backend, string $entryIdentifier)
    {
        return $backend->get($entryIdentifier);
    }

    /**
     * @param BackendInterface $backend
     * @param string $entryIdentifier
     * @return bool
     */
    protected function backendHas(BackendInterface $backend, string $entryIdentifier)
    {
        return $backend->has($entryIdentifier);
    }

    /**
     * @param BackendInterface $backend
     * @param string $entryIdentifier
     * @return bool
     */
    protected function removeFromBackend(BackendInterface $backend, string $entryIdentifier)
    {
        return $backend->remove($entryIdentifier);
    }

    /**
     * @param BackendInterface $backend
     */
    protected function flushBackend(BackendInterface $backend)
    {
        $backend->flush();
    }

    /**
     * @param \Throwable $throwable
     * @throws \Throwable
     */
    protected function handleError(\Throwable $throwable)
    {
        if ($this->debug) {
            throw $throwable;
        }
    }
}
