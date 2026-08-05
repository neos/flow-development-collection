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
use Neos\Utility\Files;

/**
 * @Flow\Scope("singleton")
 */
class XdebugPathMappingBuilder
{
    /**
     * @Flow\Inject
     * @var CacheManager
     */
    protected $cacheManager;

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

    /**
     * @param array<string, array{path: string, proxyClassIdentifier: string}> $compiledClasses
     * @param string $flowContextName
     * @return void
     */
    public function buildFromCompiledClasses(array $compiledClasses, string $flowContextName): void
    {
        if ($compiledClasses === []) {
            return;
        }

        if (!$this->isXdebugPathMappingEnabled()) {
            return;
        }

        $cacheBackend = $this->cacheManager->getCache('Flow_Object_Classes')->getBackend();
        if (!$cacheBackend instanceof SimpleFileBackend) {
            return;
        }
        $cacheDirectory = $cacheBackend->getCacheDirectory();

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

        Files::createDirectoryRecursively($this->getXdebugMappingFilePath());
        $mappingFileName = sprintf("%s.flow.map", str_replace("/", "_", $flowContextName));
        file_put_contents(
            Files::concatenatePaths([$this->getXdebugMappingFilePath(), $mappingFileName]),
            implode("\n", $mappingFileLines)
        );
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
