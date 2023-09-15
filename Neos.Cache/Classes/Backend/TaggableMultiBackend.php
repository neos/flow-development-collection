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
 * A taggable multi backend, falling back to multiple backends if errors occur.
 */
class TaggableMultiBackend extends MultiBackend implements TaggableBackendInterface
{
    /**
     * @throws Throwable
     */
    protected function buildSubBackend(string $backendClassName, array $backendOptions): ?BackendInterface
    {
        if (!is_subclass_of($backendClassName, TaggableBackendInterface::class)) {
            $message = sprintf('Failed building sub backend %s for %s because it does not implement %s', $backendClassName, get_class($this), TaggableBackendInterface::class);
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
    public function flushByTag(string $tag): int
    {
        $this->prepareBackends();
        $flushed = 0;
        foreach ($this->backends as $backend) {
            try {
                $flushed += $backend->flushByTag($tag);
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed flushing cache by tag using backend ' . get_class($backend) . ' in ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
                $this->removeUnhealthyBackend($backend);
            }
        }
        return $flushed;
    }

    /**
     * @param array<string> $tags The tags the entries must have
     * @return integer The number of entries which have been affected by this flush
     * @throws Throwable
     * @psalm-suppress MethodSignatureMismatch
     */
    public function flushByTags(array $tags): int
    {
        $flushed = 0;
        foreach ($tags as $tag) {
            $flushed += $this->flushByTag($tag);
        }
        return $flushed;
    }

    /**
     * @return string[]
     * @throws Throwable
     */
    public function findIdentifiersByTag(string $tag): array
    {
        $this->prepareBackends();
        $identifiers = [];
        foreach ($this->backends as $backend) {
            try {
                $identifiers[] = $backend->findIdentifiersByTag($tag);
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed finding identifiers by tag using backend ' . get_class($backend) . ' in ' . get_class($this) . ': ' . $this->throwableStorage?->logThrowable($throwable), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
                $this->removeUnhealthyBackend($backend);
            }
        }
        // avoid array_merge in the loop, this trades memory for speed
        return array_values(array_unique(array_merge(...$identifiers)));
    }
}
