<?php
namespace Neos\Flow\Reflection;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Utility\Environment;

/**
 * Factory for getting an reflection service instance.
 * This is purely to delay instanciating the reflection service until needed.
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class ReflectionServiceFactory
{
    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Constructs the factory
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Get reflection service instance
     */
    public function create()
    {
        if ($this->reflectionService !== null) {
            return $this->reflectionService;
        }

        $cacheManager = $this->bootstrap->getEarlyInstance(CacheManager::class);
        $configurationManager = $this->bootstrap->getEarlyInstance(ConfigurationManager::class);
        $settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');

        $reflectionService = new ReflectionService();
        $reflectionService->injectLogger($this->bootstrap->getEarlyInstance(PsrLoggerFactoryInterface::class)->get('systemLogger'));
        $reflectionService->injectSettings($settings);
        $reflectionService->injectPackageManager($this->bootstrap->getEarlyInstance(PackageManager::class));
        $reflectionService->setStatusCache($cacheManager->getCache('Flow_Reflection_Status'));
        $reflectionService->setReflectionDataCompiletimeCache($cacheManager->getCache('Flow_Reflection_CompiletimeData'));
        $reflectionService->setReflectionDataRuntimeCache($cacheManager->getCache('Flow_Reflection_RuntimeData'));
        $reflectionService->setClassSchemataRuntimeCache($cacheManager->getCache('Flow_Reflection_RuntimeClassSchemata'));
        $reflectionService->injectEnvironment($this->bootstrap->getEarlyInstance(Environment::class));

        $this->reflectionService = $reflectionService;
        return $reflectionService;
    }
}
