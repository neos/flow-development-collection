<?php
namespace TYPO3\Flow\Cache;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cache\Backend\BackendInterface;
use TYPO3\Flow\Cache\Frontend\FrontendInterface;

/**
 * This cache factory takes care of instantiating a cache frontend and injecting
 * a certain cache backend. After creation of the new cache, the cache object
 * is registered at the cache manager.
 *
 * @api
 */
class GenericCacheFactory implements CacheFactoryInterface
{
    /**
     * @var EnvironmentConfiguration
     */
    protected $environmentConfiguration;

    /**
     * Constructs this cache factory
     *
     * @param EnvironmentConfiguration $environmentConfiguration
     */
    public function __construct(EnvironmentConfiguration $environmentConfiguration)
    {
        $this->environmentConfiguration = $environmentConfiguration;
    }

    /**
     * Factory method which creates the specified cache along with the specified kind of backend.
     * After creating the cache, it will be registered at the cache manager.
     *
     * @param string $cacheIdentifier The name / identifier of the cache to create
     * @param string $cacheObjectName Object name of the cache frontend
     * @param string $backendObjectName Object name of the cache backend
     * @param array $backendOptions (optional) Array of backend options
     * @return Frontend\FrontendInterface The created cache frontend
     * @throws Exception\InvalidBackendException
     * @throws Exception\InvalidCacheException
     * @api
     */
    public function create($cacheIdentifier, $cacheObjectName, $backendObjectName, array $backendOptions = [])
    {
        $backend = $this->instantiateBackend($backendObjectName, $backendOptions);
        $cache = $this->instantiateCache($cacheIdentifier, $cacheObjectName, $backend);
        $backend->setCache($cache);

        return $cache;
    }

    /**
     * @param string $backendObjectName
     * @param array $backendOptions
     * @return BackendInterface
     * @throws Exception\InvalidBackendException
     */
    protected function instantiateBackend($backendObjectName, $backendOptions)
    {
        $backend = new $backendObjectName($this->environmentConfiguration, $backendOptions);
        if (!$backend instanceof Backend\BackendInterface) {
            throw new Exception\InvalidBackendException('"' . $backendObjectName . '" is not a valid cache backend object.', 1216304301);
        }

        return $backend;
    }

    /**
     * @param string $cacheIdentifier
     * @param string $cacheObjectName
     * @param BackendInterface $backend
     * @return FrontendInterface
     * @throws Exception\InvalidCacheException
     */
    protected function instantiateCache($cacheIdentifier, $cacheObjectName, $backend)
    {
        $cache = new $cacheObjectName($cacheIdentifier, $backend);
        if (!$cache instanceof Frontend\FrontendInterface) {
            throw new Exception\InvalidCacheException('"' . $cacheObjectName . '" is not a valid cache frontend object.', 1216304300);
        }

        return $cache;
    }
}
