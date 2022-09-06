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
        $count = 0;
        foreach ($this->backends as $backend) {
            try {
                $count |= $backend->flushByTag($tag);
            } catch (Throwable $throwable) {
                $this->logger && $this->logger->error(sprintf('Failed flushing cache by tag using backend %s in %s: %s', get_class($backend), get_class($this), $this->throwableStorage->logThrowable($throwable)), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
            }
        }
        return $count;
    }

    /**
     * @throws Throwable
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
                $this->logger && $this->logger->error(sprintf('Failed finding identifiers by tag using backend %s in %s: %s', get_class($backend), get_class($this), $this->throwableStorage->logThrowable($throwable)), LogEnvironment::fromMethodName(__METHOD__));
                $this->handleError($throwable);
            }
        }
        // avoid array_merge in the loop, this trades memory for speed
        // the empty array covers cases when no loops were made
        return array_values(array_unique(array_merge([], ...$identifiers)));
    }
}
