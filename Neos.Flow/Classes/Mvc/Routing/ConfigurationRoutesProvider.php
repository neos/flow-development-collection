<?php

declare(strict_types=1);

namespace Neos\Flow\Mvc\Routing;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * @Flow\Scope("singleton")
 */
final class ConfigurationRoutesProvider implements RoutesProviderInterface
{
    public function __construct(
        private ConfigurationManager $configurationManager,
        private ObjectManagerInterface $objectManager,
    ) {
    }

    public function getRoutes(): Routes
    {
        $routes = [];
        foreach ($this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_ROUTES) as $routeConfiguration) {
            if (isset($routeConfiguration['provider'])) {
                $provider = $this->objectManager->get($routeConfiguration['provider']);
                if ($provider instanceof RoutesProviderWithOptionsInterface && array_key_exists('providerOptions',$routeConfiguration)) {
                    $provider = $provider->withOptions($routeConfiguration['providerOptions']);
                }
                if ($provider instanceof RoutesProviderInterface) {
                    foreach ($provider->getRoutes() as $route) {
                        $routes[] = $route;
                    }
                } else {
                    throw new \InvalidArgumentException(sprintf('configured route provider "%s" does not implent the "%s"', $routeConfiguration['provider'], RoutesProviderInterface::class), 1710784630);
                }
            } else {
                $routes[] = Route::fromConfiguration($routeConfiguration);
            }
        }
        return Routes::create(...$routes);
    }
}
