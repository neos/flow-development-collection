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

/**
 * A taggable multi backend, falling back to multiple backends if errors occur.
 */
class TaggableMultiBackend extends MultiBackend implements TaggableBackendInterface
{
    /**
     * @var TaggableBackendInterface[]
     */
    protected $backends = [];

    /**
     * @param string $backendClassName
     * @param array $backendOptions
     * @return BackendInterface
     * @throws \Throwable
     */
    protected function buildSubBackend(string $backendClassName, array $backendOptions): ?BackendInterface
    {
        $backend = null;
        if (!is_subclass_of($backendClassName, TaggableBackendInterface::class)) {
            return $backend;
        }

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
     * @param string $tag
     * @return int
     * @throws \Throwable
     */
    public function flushByTag(string $tag): int
    {
        $count = 0;
        foreach ($this->backends as $backend) {
            try {
                $count = $count | $backend->flushByTag($tag);
            } catch (\Throwable $t) {
                $this->handleError($t);
            }
        }

        return $count;
    }

    /**
     * @param string $tag
     * @return array
     */
    public function findIdentifiersByTag(string $tag): array
    {
        $identifiers = [];
        foreach ($this->backends as $backend) {
            try {
                $localIdentifiers = $backend->findIdentifiersByTag($tag);
                $identifiers = array_merge($identifiers, $localIdentifiers);
            } catch (\Throwable $t) {
            }
        }

        return array_values(array_unique($identifiers));
    }
}
