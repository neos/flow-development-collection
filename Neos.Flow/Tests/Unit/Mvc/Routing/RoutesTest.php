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

use Neos\Flow\Mvc\Exception\InvalidRouteSetupException;
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
        $this->assertSame([], iterator_to_array($routes));
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
        $route2->setDefaults(['@format' => 'html']);
        $route2->setUriPattern('route2/{@package}/{@controller}/{@action}(.{@format})');
        $route2->setLowerCase(false);
        $route2->setAppendExceedingArguments(true);
        $route2->setRoutePartsConfiguration(
            [
                '@controller' => [
                    'handler' => 'MyRoutePartHandler'
                ]
            ]
        );
        $route2->setHttpMethods(['PUT']);
        $route2->setCacheTags(Routing\Dto\RouteTags::createFromArray(['foo', 'bar']));
        $route2->setCacheLifetime(Routing\Dto\RouteLifetime::fromInt(10000));

        $configuration = [
            [
                'name' => 'Route 1',
                'uriPattern' => 'route1/{@package}/{@controller}/{@action}(.{@format})',
                'defaults' => ['@format' => 'html']
            ],
            [
                'name' => 'Route 2',
                'defaults' => ['@format' => 'html'],
                'uriPattern' => 'route2/{@package}/{@controller}/{@action}(.{@format})',
                'toLowerCase' => false,
                'appendExceedingArguments' => true,
                'routeParts' => [
                    '@controller' => [
                        'handler' => 'MyRoutePartHandler'
                    ]
                ],
                'httpMethods' => ['PUT'],
                'cache' => [
                    'lifetime' => 10000,
                    'tags' => ['foo', 'bar']
                ],
            ],
        ];

        $routes = Routes::fromConfiguration($configuration);

        $this->assertEquals(
            [$route1, $route2],
            iterator_to_array($routes)
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

        $this->assertSame([$route1, $route2], iterator_to_array($routes));
    }

    /**
     * @test
     */
    public function createRoutesFromConfigurationThrowsExceptionIfOnlySomeRoutesWithTheSameUriPatternHaveHttpMethodConstraints()
    {
        // multiple routes with the uriPattern and "httpMethods" option
        $this->expectException(InvalidRouteSetupException::class);
        $routesConfiguration = [
            [
                'uriPattern' => 'somePattern'
            ],
            [
                'uriPattern' => 'somePattern',
                'httpMethods' => ['POST', 'PUT']
            ],
        ];
        shuffle($routesConfiguration);
        Routes::fromConfiguration($routesConfiguration);
    }

    /**
     * @test
     */
    public function createRoutesFromConfigurationParsesTheGivenConfigurationAndBuildsRouteObjectsFromIt()
    {
        $routesConfiguration = [];
        $routesConfiguration['route1']['uriPattern'] = 'number1';
        $routesConfiguration['route2']['uriPattern'] = 'number2';
        $routesConfiguration['route3'] = [
            'name' => 'route3',
            'defaults' => ['foodefault'],
            'routeParts' => ['fooroutepart'],
            'uriPattern' => 'number3',
            'toLowerCase' => false,
            'appendExceedingArguments' => true,
            'httpMethods' => ['POST', 'PUT']
        ];

        /** @var Route[] $createdRoutes */
        $createdRoutes = iterator_to_array(Routes::fromConfiguration($routesConfiguration));

        self::assertEquals('number1', $createdRoutes[0]->getUriPattern());
        self::assertTrue($createdRoutes[0]->isLowerCase());
        self::assertFalse($createdRoutes[0]->getAppendExceedingArguments());
        self::assertEquals('number2', $createdRoutes[1]->getUriPattern());
        self::assertFalse($createdRoutes[1]->hasHttpMethodConstraints());
        self::assertEquals([], $createdRoutes[1]->getHttpMethods());
        self::assertEquals('route3', $createdRoutes[2]->getName());
        self::assertEquals(['foodefault'], $createdRoutes[2]->getDefaults());
        self::assertEquals(['fooroutepart'], $createdRoutes[2]->getRoutePartsConfiguration());
        self::assertEquals('number3', $createdRoutes[2]->getUriPattern());
        self::assertFalse($createdRoutes[2]->isLowerCase());
        self::assertTrue($createdRoutes[2]->getAppendExceedingArguments());
        self::assertTrue($createdRoutes[2]->hasHttpMethodConstraints());
        self::assertEquals(['POST', 'PUT'], $createdRoutes[2]->getHttpMethods());
    }
}
