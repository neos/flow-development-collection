<?php

namespace Neos\Flow\Mvc\Routing;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;

/**
 * @Flow\Scope("singleton")
 */
final class ConfigurationRoutesProvider implements RoutesProviderInterface
{
    public function __construct(
        private readonly ConfigurationManager $configurationManager
    ) {
    }

    public function getRoutes(): Routes
    {
        return Routes::fromConfiguration($this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_ROUTES));
    }
}
