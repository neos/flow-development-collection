<?php

namespace Neos\Flow\ObjectManagement\Proxy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\SimpleFileBackend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Utility\Files;

/**
 * @Flow\Scope("singleton")
 */
class XdebugPathMappingBuilder
{
    private const PERSISTED_DATA_FILENAME = '.xdebug-pathmap-data.serialized';

    /**
     * @Flow\Inject
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function injectCacheManager(CacheManager $cacheManager): void
    {
        $this->cacheManager = $cacheManager;
    }

    public function injectBootstrap(Bootstrap $bootstrap): void
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * @param array<string, array{path: string, proxyClassIdentifier: string}> $compiledClasses
     * @return void
     */
    public function buildFromCompiledClasses(array $compiledClasses): void
    {
        if ($compiledClasses === []) {
            return;
        }

        if (!$this->isXdebugPathMappingEnabled()) {
            return;
        }

        $cacheDirectory = $this->getProxyCacheDirectory();
        if ($cacheDirectory === null) {
            return;
        }

        // Persist the input data next to the proxy class cache. The proxy cache
        // directory is typically mounted/persisted on the host, whereas the map
        // output directory may live only in the container layer. Persisting here
        // lets us re-emit the map after a restart without re-compiling.
        file_put_contents(
            Files::concatenatePaths([$cacheDirectory, self::PERSISTED_DATA_FILENAME]),
            serialize($compiledClasses)
        );

        $this->writeMapFile($cacheDirectory, $compiledClasses);
    }

    /**
     * Re-writes the path mapping file from previously persisted data when the
     * map file is missing. Intended to recover from situations where the proxy
     * cache is still warm (so no compile signal fires) but the `.xdebug/` map
     * directory was wiped (e.g. docker container restart, manual cleanup).
     */
    public function buildFromPersistedDataIfMapMissing(): void
    {
        if (!$this->isXdebugPathMappingEnabled()) {
            return;
        }
        $mapFilePath = $this->getMapFilePath();
        if ($mapFilePath === null || file_exists($mapFilePath)) {
            return;
        }

        $cacheDirectory = $this->getProxyCacheDirectory();
        if ($cacheDirectory === null) {
            return;
        }

        $persistedDataPath = Files::concatenatePaths([$cacheDirectory, self::PERSISTED_DATA_FILENAME]);
        if (!file_exists($persistedDataPath)) {
            return;
        }

        $compiledClasses = unserialize((string)file_get_contents($persistedDataPath), ['allowed_classes' => false]);
        if (!is_array($compiledClasses) || $compiledClasses === []) {
            return;
        }
        $this->writeMapFile($cacheDirectory, $compiledClasses);
    }

    /**
     * @param array<string, array{path: string, proxyClassIdentifier: string}> $compiledClasses
     */
    private function writeMapFile(string $cacheDirectory, array $compiledClasses): void
    {
        $mappingFileLines = [
            '# Created by Flow Framework during compile time proxy generation.',
            '#',
            '# Ensure you are using xdebug >= v3.5 with enabled path mapping.',
            '# Configuration in your php.ini:',
            '# xdebug.mode = debug',
            '# xdebug.path_mapping = 1',
            '#',
            '# ----------------------------------------------------------------',
            sprintf('# Last update: %s', date('Y-m-d H:i:s')),
            sprintf('remote_prefix:%s', $cacheDirectory),
            sprintf('local_prefix:%s', FLOW_PATH_ROOT),
        ];
        foreach ($compiledClasses as $data) {
            $localPath = str_replace(FLOW_PATH_ROOT, '', $data['path']);
            $remotePath = $data['proxyClassIdentifier'];
            $mappingFileLines[] = sprintf("%s.php = %s", $remotePath, $localPath);
        }
        // Add an empty line to the end of the file
        $mappingFileLines[] = '';

        $mapFilePath = $this->getMapFilePath();
        if ($mapFilePath === null) {
            return;
        }
        Files::createDirectoryRecursively($this->getXdebugMappingFilePath());
        file_put_contents($mapFilePath, implode("\n", $mappingFileLines));
    }

    private function getProxyCacheDirectory(): ?string
    {
        $cacheBackend = $this->cacheManager->getCache('Flow_Object_Classes')->getBackend();
        if (!$cacheBackend instanceof SimpleFileBackend) {
            return null;
        }
        return $cacheBackend->getCacheDirectory();
    }

    private function getMapFilePath(): ?string
    {
        if ($this->bootstrap === null) {
            return null;
        }
        $contextIdentifier = str_replace(['/', '\\'], '_', (string)$this->bootstrap->getContext());
        return Files::concatenatePaths([$this->getXdebugMappingFilePath(), 'flow-' . $contextIdentifier . '.map']);
    }

    private function isXdebugPathMappingEnabled(): bool
    {
        return isset($this->settings['object']['proxy']['enableXdebugPathMapping']) && $this->settings['object']['proxy']['enableXdebugPathMapping'] === true;
    }

    private function getXdebugMappingFilePath(): string
    {
        return Files::concatenatePaths([FLOW_PATH_ROOT, '.xdebug']);
    }
}
