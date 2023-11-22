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
use Neos\Cache\Exception\InvalidCacheException;
use Neos\Cache\Exception\InvalidBackendException;
use Neos\Cache\Frontend\LowLevelFrontendInterface;

/**
 * This cache factory takes care of instantiating a cache frontend and injecting
 * a certain cache backend. After creation of the new cache, the cache object
 * is registered at the cache manager.
 *
 * @api
 */
interface CacheFactoryInterface
{
    /**
     * Factory method which creates the specified cache along with the specified kind of backend.
     * After creating the cache, it will be registered at the cache manager.
     *
     * @param string $cacheIdentifier The name / identifier of the cache to create
     * @param string $cacheObjectName Object name of the cache frontend
     * @param string $backendObjectName Object name of the cache backend
     * @param array $backendOptions (optional) Array of backend options
     * @return \Neos\Cache\Frontend\LowLevelFrontendInterface The created cache frontend
     * @throws InvalidBackendException
     * @throws InvalidCacheException
     * @api
     */
    public function create(string $cacheIdentifier, string $cacheObjectName, string $backendObjectName, array $backendOptions = []): LowLevelFrontendInterface;
}
