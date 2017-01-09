<?php
namespace Neos\Flow\Cache;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Backend\SimpleFileBackend;
use Neos\Cache\CacheFactoryInterface;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\Backend\AbstractBackend as FlowAbstractBackend;
use Neos\Flow\Cache\Backend\FlowSpecificBackendInterface;
use Neos\Cache\Exception\InvalidBackendException;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Utility\Environment;

/**
 * This cache factory takes care of instantiating a cache frontend and injecting
 * a certain cache backend. In a Flow context you should use the CacheManager to
 * get a Cache.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class CacheFactory extends \Neos\Cache\CacheFactory implements CacheFactoryInterface
{
    /**
     * The current Flow context ("Production", "Development" etc.)
     *
     * @var ApplicationContext
     */
    protected $context;

    /**
     * A reference to the cache manager
     *
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var EnvironmentConfiguration
     */
    protected $environmentConfiguration;

    /**
     * @param CacheManager $cacheManager
     * @Flow\Autowiring(enabled=false)
     */
    public function injectCacheManager(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param EnvironmentConfiguration $environmentConfiguration
     * @Flow\Autowiring(enabled=false)
     */
    public function injectEnvironmentConfiguration(EnvironmentConfiguration $environmentConfiguration)
    {
        $this->environmentConfiguration = $environmentConfiguration;
    }

    /**
     * Constructs this cache factory
     *
     * @param ApplicationContext $context The current Flow context
     * @param Environment $environment
     * @Flow\Autowiring(enabled=false)
     */
    public function __construct(ApplicationContext $context, Environment $environment)
    {
        $this->context = $context;
        $this->environment = $environment;

        $environmentConfiguration = new EnvironmentConfiguration(
            FLOW_PATH_ROOT . '~' . (string)$environment->getContext(),
            $environment->getPathToTemporaryDirectory()
        );

        parent::__construct($environmentConfiguration);
    }

    /**
     * @param string $cacheIdentifier
     * @param string $cacheObjectName
     * @param string $backendObjectName
     * @param array $backendOptions
     * @param bool $persistent
     * @return FrontendInterface
     */
    public function create($cacheIdentifier, $cacheObjectName, $backendObjectName, array $backendOptions = [], $persistent = false)
    {
        $backend = $this->instantiateBackend($backendObjectName, $backendOptions, $persistent);
        $cache = $this->instantiateCache($cacheIdentifier, $cacheObjectName, $backend);
        $backend->setCache($cache);

        return $cache;
    }

    /**
     * {@inheritdoc}
     */
    protected function instantiateCache($cacheIdentifier, $cacheObjectName, $backend)
    {
        $cache = parent::instantiateCache($cacheIdentifier, $cacheObjectName, $backend);

        if (is_callable([$cache, 'initializeObject'])) {
            $cache->initializeObject(ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);
        }

        return $cache;
    }

    /**
     * @param string $backendObjectName
     * @param array $backendOptions
     * @param boolean $persistent
     * @return FlowAbstractBackend|BackendInterface
     * @throws InvalidBackendException
     */
    protected function instantiateBackend($backendObjectName, $backendOptions, $persistent = false)
    {
        if (
            $persistent &&
            is_a($backendObjectName, SimpleFileBackend::class, true) &&
            (!isset($backendOptions['cacheDirectory']) || $backendOptions['cacheDirectory'] === '') &&
            (!isset($backendOptions['baseDirectory']) || $backendOptions['baseDirectory'] === '')
        ) {
            $backendOptions['baseDirectory'] = FLOW_PATH_DATA . 'Persistent/';
        }

        if (is_a($backendObjectName, FlowSpecificBackendInterface::class, true)) {
            return $this->instantiateFlowSpecificBackend($backendObjectName, $backendOptions);
        }

        return parent::instantiateBackend($backendObjectName, $backendOptions);
    }

    /**
     * @param string $backendObjectName
     * @param array $backendOptions
     * @return FlowAbstractBackend
     * @throws InvalidBackendException
     */
    protected function instantiateFlowSpecificBackend($backendObjectName, $backendOptions)
    {
        $backend = new $backendObjectName($this->context, $backendOptions);

        if (!$backend instanceof BackendInterface) {
            throw new InvalidBackendException('"' . $backendObjectName . '" is not a valid cache backend object.', 1216304301);
        }

        /** @var FlowAbstractBackend $backend */
        $backend->injectEnvironment($this->environment);

        if (is_callable([$backend, 'injectCacheManager'])) {
            $backend->injectCacheManager($this->cacheManager);
        }
        if (is_callable([$backend, 'initializeObject'])) {
            $backend->initializeObject(ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);
        }

        return $backend;
    }
}
