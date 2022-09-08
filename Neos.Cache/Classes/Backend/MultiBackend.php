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
use Neos\Cache\EnvironmentConfiguration;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\ObjectManagement\Exception\UnknownObjectException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
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
    protected bool $logErrors = true;
    protected bool $removeUnhealthyBackends = true;
    protected bool $initialized = false;

    protected ?LoggerInterface $logger = null;
    protected ?ThrowableStorageInterface $throwableStorage = null;

    public function __construct(EnvironmentConfiguration $environmentConfiguration = null, array $options = [])
    {
        parent::__construct($environmentConfiguration, $options);

        if ($this->logErrors && class_exists(Bootstrap::class) && Bootstrap::$staticObjectManager instanceof ObjectManagerInterface) {
            try {
                $logger = Bootstrap::$staticObjectManager->get(LoggerInterface::class);
                assert($logger instanceof LoggerInterface);
                $this->logger = $logger;
                $throwableStorage = Bootstrap::$staticObjectManager->get(ThrowableStorageInterface::class);
                assert($throwableStorage instanceof ThrowableStorageInterface);
                $this->throwableStorage = $throwableStorage;
            } catch (UnknownObjectException) {
                // Logging might not be available during compile time
            }
        }
    }

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
        } catch (Throwable $throwable) {
            $this->logger?->error('Failed creating sub backend ' . $backendClassName . ' for ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
            $this->handleError($throwable);
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
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed setting cache entry using backend ' . get_class($backend) . ' in ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
                $this->removeUnhealthyBackend($backend);
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
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed retrieving cache entry using backend ' . get_class($backend) . ' in ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
                $this->removeUnhealthyBackend($backend);
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
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed checking if cache entry exists using backend ' . get_class($backend) . ' in ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
                $this->removeUnhealthyBackend($backend);
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
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed removing cache entry using backend ' . get_class($backend) . ' in ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
                $this->removeUnhealthyBackend($backend);
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
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed flushing cache using backend ' . get_class($backend) . ' in ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
                $this->removeUnhealthyBackend($backend);
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
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed collecting garbage using cache backend ' . get_class($backend) . ' in ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
                $this->removeUnhealthyBackend($backend);
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
     * This setter is used by AbstractBackend::setProperties()
     */
    public function setRemoveUnhealthyBackends(bool $removeUnhealthyBackends): void
    {
        $this->removeUnhealthyBackends = $removeUnhealthyBackends;
    }

    /**
     * This setter is used by AbstractBackend::setProperties()
     */
    public function setLogErrors(bool $logErrors): void
    {
        $this->logErrors = $logErrors;
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

    protected function removeUnhealthyBackend(BackendInterface $unhealthyBackend): void
    {
        if ($this->removeUnhealthyBackends === false || count($this->backends) <= 1) {
            return;
        }
        $i = array_search($unhealthyBackend, $this->backends, true);
        if ($i !== false) {
            unset($this->backends[$i]);
            $this->logger?->warning(sprintf('Removing unhealthy cache backend %s from backends used by %s', get_class($unhealthyBackend), get_class($this)), LogEnvironment::fromMethodName(__METHOD__));
        }
    }
}
