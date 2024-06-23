<?php

/*
 * (c) Contributors of the Neos Project - www.neos.io
 * Please see the LICENSE file which was distributed with this source code.
 */

declare(strict_types=1);

namespace Neos\Flow\Tests\PhpBench\Configuration;

use Neos\BuildEssentials\PhpBench\FrameworkEnabledBenchmark;
use Neos\Flow\Configuration\ConfigurationManager;

/**
 * Benchmark cases for the ConfigurationManager
 * Check performance of getting different types of configuration and handling the low level configuration caches.
 */
class ConfigurationManagerBench extends FrameworkEnabledBenchmark
{
    /**
     * @BeforeMethods("bootstrapWithTestRequestHandler")
     * @Revs(5)
     */
    public function benchGetSettings(): void
    {
        /** @var ConfigurationManager $configurationManager */
        $configurationManager = $this->flowBootstrap->getObjectManager()->get(ConfigurationManager::class);
        $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
    }

    /**
     * @BeforeMethods("bootstrapWithTestRequestHandler")
     * @Revs(5)
     */
    public function benchGetRoutes(): void
    {
        /** @var ConfigurationManager $configurationManager */
        $configurationManager = $this->flowBootstrap->getObjectManager()->get(ConfigurationManager::class);
        $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
    }

    /**
     * @BeforeMethods("bootstrapWithTestRequestHandler")
     * @Revs(5)
     */
    public function benchRefreshConfiguration(): void
    {
        /** @var ConfigurationManager $configurationManager */
        $configurationManager = $this->flowBootstrap->getObjectManager()->get(ConfigurationManager::class);
        $configurationManager->refreshConfiguration();
    }
}
