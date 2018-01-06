<?php
namespace Neos\Cache;

use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Exception\InvalidBackendException;
use Neos\Cache\Frontend\PsrSimpleCacheFrontend;
use Psr\SimpleCache\CacheInterface;

/**
 *
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
    public function create($cacheIdentifier, $backendObjectName, array $backendOptions = []): CacheInterface
    {
        $backend = $this->instantiateBackend($backendObjectName, $backendOptions, $this->environmentConfiguration);
        $cache = $this->instantiateCache($cacheIdentifier, $cacheObjectName, $backend);
        $backend->setCache($cache);

        return $cache;
    }

    /**
     * @param string $cacheIdentifier
     * @param BackendInterface $backend
     * @return CacheInterface
     */
    protected function instantiateCache($cacheIdentifier, $backend): CacheInterface
    {
        return new PsrSimpleCacheFrontend($cacheIdentifier, $backend);
    }
}
