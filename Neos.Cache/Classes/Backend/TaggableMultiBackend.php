<?php
namespace Neos\Cache\Backend;

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
     */
    protected function buildSubBackend(string $backendClassName, array $backendOptions):? BackendInterface
    {
        $backend = null;
        if (!is_a($backendClassName, TaggableBackendInterface::class)) {
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
     */
    public function flushByTag(string $tag): int
    {
        $count = 0;
        /** @var TaggableBackendInterface $backend */
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
        /** @var TaggableBackendInterface $backend */
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
