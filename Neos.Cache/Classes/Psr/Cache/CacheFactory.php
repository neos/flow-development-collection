<?php
namespace Neos\Cache\Psr\Cache;

use Neos\Cache\BackendInstantiationTrait;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Exception\InvalidBackendException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * A factory for the PSR-6 compatible cache pool.
 */
class CacheFactory
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
     * @return CacheItemPoolInterface
     * @throws InvalidBackendException
     */
    public function create($cacheIdentifier, $backendObjectName, array $backendOptions = []): CacheItemPoolInterface
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
     * @return CacheItemPoolInterface
     */
    protected function instantiateCache($cacheIdentifier, $backend): CacheItemPoolInterface
    {
        return new CachePool($cacheIdentifier, $backend);
    }
}
