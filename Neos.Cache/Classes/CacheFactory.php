<?php
namespace Neos\Cache;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Exception\InvalidBackendException;
use Neos\Cache\Exception\InvalidCacheException;
use Neos\Cache\Frontend\FrontendInterface;

/**
 * This cache factory takes care of instantiating a cache frontend and injecting
 * a certain cache backend. After creation of the new cache, the cache object
 * is registered at the cache manager.
 *
 * @api
 */
class CacheFactory implements CacheFactoryInterface
{
    use BackendInstantiationTrait;

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
     * @return FrontendInterface The created cache frontend
     * @throws InvalidBackendException
     * @throws InvalidCacheException
     * @api
     */
    public function create($cacheIdentifier, $cacheObjectName, $backendObjectName, array $backendOptions = []): FrontendInterface
    {
        $backend = $this->instantiateBackend($backendObjectName, $backendOptions, $this->environmentConfiguration);
        $cache = $this->instantiateCache($cacheIdentifier, $cacheObjectName, $backend);
        $backend->setCache($cache);

        return $cache;
    }

    /**
     * @param string $cacheIdentifier
     * @param string $cacheObjectName
     * @param BackendInterface $backend
     * @return FrontendInterface
     * @throws InvalidCacheException
     */
    protected function instantiateCache(string $cacheIdentifier, string $cacheObjectName, BackendInterface $backend): FrontendInterface
    {
        $cache = new $cacheObjectName($cacheIdentifier, $backend);
        if (!$cache instanceof FrontendInterface) {
            throw new InvalidCacheException('"' . $cacheObjectName . '" is not a valid cache frontend object.', 1216304300);
        }

        return $cache;
    }
}
