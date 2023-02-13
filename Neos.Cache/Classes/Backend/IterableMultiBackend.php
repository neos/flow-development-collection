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

use Neos\Cache\Exception;
use Neos\Flow\Log\Utility\LogEnvironment;
use Throwable;

/**
 * An iterable, taggable multi backend, falling back to multiple backends if errors occur.
 */
class IterableMultiBackend extends TaggableMultiBackend implements IterableBackendInterface
{
    /**
     * @throws Throwable
     */
    protected function buildSubBackend(string $backendClassName, array $backendOptions): ?BackendInterface
    {
        if (!is_subclass_of($backendClassName, IterableBackendInterface::class)) {
            $message = sprintf('Failed building sub backend %s for %s because it does not implement %s', $backendClassName, get_class($this), IterableBackendInterface::class);
            $throwable = new Exception($message);
            $this->logger?->error(($this->throwableStorage ? $this->throwableStorage->logThrowable($throwable) : $message), LogEnvironment::fromMethodName(__METHOD__));
            $this->handleError($throwable);
            return null;
        }
        return parent::buildSubBackend($backendClassName, $backendOptions);
    }

    /**
     * @throws Throwable
     */
    public function current(): mixed
    {
        $this->prepareBackends();
        foreach ($this->backends as $backend) {
            try {
                return $backend->current();
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed retrieving current cache entry using backend ' . get_class($backend) . ' in ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
                $this->removeUnhealthyBackend($backend);
            }
        }
        return false;
    }

    /**
     * @throws Throwable
     */
    public function next(): void
    {
        $this->prepareBackends();
        foreach ($this->backends as $backend) {
            try {
                $backend->next();
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed retrieving next cache entry using backend ' . get_class($backend) . ' in ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
                $this->removeUnhealthyBackend($backend);
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function key(): string|int|bool|null|float
    {
        $this->prepareBackends();
        foreach ($this->backends as $backend) {
            try {
                return $backend->key();
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed retrieving cache entry key using backend ' . get_class($backend) . ' in ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
                $this->removeUnhealthyBackend($backend);
            }
        }
        return false;
    }

    /**
     * @throws Throwable
     */
    public function valid(): bool
    {
        $this->prepareBackends();
        foreach ($this->backends as $backend) {
            try {
                return $backend->valid();
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed checking if current cache entry is valid using backend ' . get_class($backend) . ' in ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
                $this->removeUnhealthyBackend($backend);
            }
        }
        return false;
    }

    /**
     * @throws Throwable
     */
    public function rewind(): void
    {
        $this->prepareBackends();
        foreach ($this->backends as $backend) {
            try {
                $backend->rewind();
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed rewinding cache entries using backend ' . get_class($backend) . ' in ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
                $this->removeUnhealthyBackend($backend);
            }
        }
    }
}
