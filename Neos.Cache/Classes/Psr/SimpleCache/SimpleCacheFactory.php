<?php
declare(strict_types=1);

namespace Neos\Cache\Psr\SimpleCache;

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
use Neos\Cache\BackendInstantiationTrait;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Exception\InvalidBackendException;
use Neos\Cache\Frontend\VariableFrontend;
use Psr\SimpleCache\CacheInterface;

/**
 * A factory for PSR-16 simple caches.
 */
class SimpleCacheFactory
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
     * The identifier uniquely identifiers the specific cache, so that entries inside are unique.
     *
     * @param string $cacheIdentifier The name / identifier of the cache to create.
     * @param string $backendObjectName Object name of the cache backend
     * @param array $backendOptions (optional) Array of backend options
     * @return CacheInterface
     * @throws InvalidBackendException
     */
    public function create(string $cacheIdentifier, string $backendObjectName, array $backendOptions = []): CacheInterface
    {
        $backend = $this->instantiateBackend($backendObjectName, $backendOptions, $this->environmentConfiguration);
        $cache = $this->instantiateCache($cacheIdentifier, $backend);
        // TODO: Remove this need.
        $fakeFrontend = new VariableFrontend($cacheIdentifier, $backend);
        $backend->setCache($fakeFrontend);

        return $cache;
    }

    /**
     * @param string $cacheIdentifier
     * @param BackendInterface $backend
     * @return CacheInterface
     */
    protected function instantiateCache($cacheIdentifier, $backend): CacheInterface
    {
        return new SimpleCache($cacheIdentifier, $backend);
    }
}
