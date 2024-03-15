<?php

declare(strict_types=1);

namespace Neos\Flow\Mvc\Routing;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\Loader\RoutesLoader;
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
                if ($provider instanceof RoutesProviderWithOptionsInterface) {
                    $provider = $provider->withOptions($routeConfiguration['providerOptions']);
                }
                assert($provider instanceof RoutesProviderInterface);
                foreach ($provider->getRoutes() as $route) {
                    $routes[] = $route;
                }
            } else {
                $routes[] = Route::fromConfiguration($routeConfiguration);
            }
        }
        return Routes::create(...$routes);
    }
}
