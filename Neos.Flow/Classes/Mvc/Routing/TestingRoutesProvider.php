<?php
declare(strict_types=1);

namespace Neos\Flow\Mvc\Routing;

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

/**
 * Extends the routing to be able to add custom routes at runtime.
 *
 * @internal implementation detail. Please use {@see FunctionalTestCase::registerRoute} instead.
 */
#[Flow\Scope("singleton")]
final class TestingRoutesProvider implements RoutesProviderInterface
{
    private Routes $additionalRoutes;

    public function __construct(
        private readonly ConfigurationRoutesProvider $configurationRoutesProvider
    ) {
        $this->additionalRoutes = Routes::empty();
    }

    /**
     * Prepended a route
     */
    public function addRoute(Route $route)
    {
        $this->additionalRoutes = Routes::create($route)->merge($this->additionalRoutes);
    }

    public function reset(): void
    {
        $this->additionalRoutes = Routes::empty();
    }

    public function getRoutes(): Routes
    {
        // prepended a route
        return $this->additionalRoutes->merge(
            $this->configurationRoutesProvider->getRoutes()
        );
    }
}
