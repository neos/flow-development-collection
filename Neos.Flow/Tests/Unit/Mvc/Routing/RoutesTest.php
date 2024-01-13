<?php
namespace Neos\Flow\Tests\Unit\Mvc\Routing;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Routing;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Mvc\Routing\Routes;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC Web Routing Routes Class
 */
class RoutesTest extends UnitTestCase
{
    /**
     * @test
     */
    public function emptyCreatesEmptyRoutes(): void
    {
        $routes = Routes::empty();
        $this->assertSame([], iterator_to_array($routes->getIterator()));
    }

    /**
     * @test
     */
    public function fromConfigurationWorksAsExpected(): void
    {
        $route1 = new Route();
        $route1->setName('Route 1');
        $route1->setUriPattern('route1/{@package}/{@controller}/{@action}(.{@format})');
        $route1->setDefaults(['@format' => 'html']);

        $route2 = new Route();
        $route2->setName('Route 2');
        $route2->setUriPattern('route2/{@package}/{@controller}/{@action}(.{@format})');
        $route2->setDefaults(['@format' => 'html']);
        $route2->setCacheLifetime(Routing\Dto\RouteLifetime::fromInt(10000));
        $route2->setCacheTags(Routing\Dto\RouteTags::createFromArray(['foo', 'bar']));
        $route2->setAppendExceedingArguments(true);

        $configuration = [
            [
                'name' => 'Route 1',
                'uriPattern' => 'route1/{@package}/{@controller}/{@action}(.{@format})',
                'defaults' => ['@format' => 'html']
            ],
            [
                'name' => 'Route 2',
                'uriPattern' => 'route2/{@package}/{@controller}/{@action}(.{@format})',
                'defaults' => ['@format' => 'html'],
                'appendExceedingArguments' => true,
                'cache' => ['lifetime' => 10000, 'tags' => ['foo', 'bar']]
            ],
        ];

        $routes = Routes::fromConfiguration($configuration);

        $this->assertEquals(
            [$route1, $route2],
            iterator_to_array($routes->getIterator())
        );
    }

    /**
     * @test
     */
    public function mergeRoutes(): void
    {
        $route1 = new Route();
        $route1->setName("Route 1");

        $route2 = new Route();
        $route2->setName("Route 2");

        $routes = Routes::create($route1)->merge(Routes::create($route2));

        $this->assertSame([$route1, $route2], iterator_to_array($routes->getIterator()));
    }
}
