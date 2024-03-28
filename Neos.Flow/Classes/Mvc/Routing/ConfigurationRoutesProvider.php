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
            if (isset($routeConfiguration['providerFactory'])) {
                $providerFactory = $this->objectManager->get($routeConfiguration['providerFactory']);
                if (!$providerFactory instanceof RoutesProviderFactoryInterface) {
                    throw new \InvalidArgumentException(sprintf('The configured route providerFactory "%s" does not implement the "%s"', $routeConfiguration['providerFactory'], RoutesProviderFactoryInterface::class), 1710784630);
                }
                $provider = $providerFactory->createRoutesProvider($routeConfiguration['providerOptions'] ?? []);
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
