<?php
namespace Neos\Flow\Tests\Functional\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\Functional\Mvc\Fixtures\RoutePartHandler\UriBuilderSetDomainAndPathPrefixRoutePartHandler;
use Neos\Flow\Tests\Functional\Mvc\Fixtures\RoutePartHandler\UriBuilderSetDomainRoutePartHandler;
use Neos\Flow\Tests\FunctionalTestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * Functional tests for the Router
 *
 * HINT: The routes used in these tests are defined in the Routes.yaml file in the
 *       Testing context of the Flow package configuration.
 */
class UriBuilderTest extends FunctionalTestCase
{

    /**
     * @var ServerRequestFactoryInterface
     */
    protected $serverRequestFactory;

    /**
     * Additional setup: Routes
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->serverRequestFactory = $this->objectManager->get(ServerRequestFactoryInterface::class);
    }

    private function registerSingleRoute($routePartHandler): void
    {
        $route = $this->registerRoute('testa', 'test/mvc/uribuilder/{@action}/{someRoutePart}', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
            '@controller' => 'UriBuilder',
            '@action' => 'index',
            '@format' => 'html'
        ]);
        $route->setRoutePartsConfiguration([
            'someRoutePart' => [
                'handler' => $routePartHandler
            ]
        ]);
    }

    private function registerAbsoluteRoute(): void
    {
        $this->registerRoute('absolute', '', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
            '@controller' => 'UriBuilder',
            '@action' => 'root',
            '@format' => 'html'
        ]);
    }

    /**
     * Testcase for https://github.com/neos/flow-development-collection/issues/1803
     *
     * @test
     */
    public function whenLinkingToDifferentHostTheUrlIsAsExpectedNotContainingDoubleSlashes()
    {
        $this->registerSingleRoute(UriBuilderSetDomainRoutePartHandler::class);
        $response = $this->browser->request('http://localhost/test/mvc/uribuilder/differentHost/bla');
        self::assertEquals('http://my-host/test/mvc/uribuilder/target/my-path', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * Testcase for https://github.com/neos/flow-development-collection/issues/1803
     *
     * @test
     */
    public function whenLinkingToDifferentHostTheUrlIsAsExpectedNotContainingDoubleSlashes_forceAbsoluteUris()
    {
        $this->registerSingleRoute(UriBuilderSetDomainRoutePartHandler::class);
        $response = $this->browser->request('http://localhost/test/mvc/uribuilder/differentHostWithCreateAbsoluteUri/bla');
        self::assertEquals('http://my-host/test/mvc/uribuilder/target/my-path', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * Testcase for https://github.com/neos/flow-development-collection/issues/1803
     *
     * @test
     */
    public function whenLinkingToSameHostTheUrlIsAsExpectedNotContainingDoubleSlashes()
    {
        $this->registerSingleRoute(UriBuilderSetDomainRoutePartHandler::class);
        $response = $this->browser->request('http://my-host/test/mvc/uribuilder/differentHost/bla');
        self::assertEquals('/test/mvc/uribuilder/target/my-path', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * Testcase for https://github.com/neos/flow-development-collection/issues/1803
     *
     * @test
     */
    public function whenLinkingToSameHostTheUrlIsAsExpectedNotContainingDoubleSlashes_forceAbsoluteUrls()
    {
        $this->registerSingleRoute(UriBuilderSetDomainRoutePartHandler::class);
        $response = $this->browser->request('http://my-host/test/mvc/uribuilder/differentHostWithCreateAbsoluteUri/bla');
        self::assertEquals('http://my-host/test/mvc/uribuilder/target/my-path', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * Testcase for https://github.com/neos/flow-development-collection/pull/1839 and
     * https://github.com/neos/neos-development-collection/issues/2759
     *
     * @test
     */
    public function whenLinkingToRootOfSameHostTheUrlContainsASingleSlash()
    {
        // NOTE: the route part handler here does not really match; as we link to the the route
        // registered in "registerAbsoluteRoute()".
        $this->registerSingleRoute(UriBuilderSetDomainRoutePartHandler::class);
        // NOTE: the registered route is PREPENDED to the existing list; so we need to register the absolute route LAST as it should match FIRST.
        $this->registerAbsoluteRoute();
        $response = $this->browser->request('http://my-host/test/mvc/uribuilder/linkingToRoot/bla');
        self::assertEquals('/', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * Testcase for https://github.com/neos/flow-development-collection/pull/1839 and
     * https://github.com/neos/neos-development-collection/issues/2759
     *
     * @test
     */
    public function whenLinkingToRootOfSameHostTheUrlContainsASingleSlash_forceAbsoluteUrls()
    {
        // NOTE: the route part handler here does not really match; as we link to the the route
        // registered in "registerAbsoluteRoute()".
        $this->registerSingleRoute(UriBuilderSetDomainRoutePartHandler::class);
        // NOTE: the registered route is PREPENDED to the existing list; so we need to register the absolute route LAST as it should match FIRST.
        $this->registerAbsoluteRoute();
        $response = $this->browser->request('http://my-host/test/mvc/uribuilder/linkingToRootWithCreateAbsoluteUri/bla');
        self::assertEquals('http://my-host/', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * Testcase for https://github.com/neos/flow-development-collection/issues/1803
     *
     * @test
     */
    public function urlPrefix_whenLinkingToDifferentHostTheUrlIsAsExpectedNotContainingDoubleSlashes()
    {
        $this->registerSingleRoute(UriBuilderSetDomainAndPathPrefixRoutePartHandler::class);
        $response = $this->browser->request('http://localhost/test/mvc/uribuilder/differentHost/bla');
        self::assertEquals('http://my-host/my-path/test/mvc/uribuilder/target/my-path', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * Testcase for https://github.com/neos/flow-development-collection/issues/1803
     *
     * @test
     */
    public function urlPrefix_whenLinkingToDifferentHostTheUrlIsAsExpectedNotContainingDoubleSlashes_forceAbsoluteUris()
    {
        $this->registerSingleRoute(UriBuilderSetDomainAndPathPrefixRoutePartHandler::class);
        $response = $this->browser->request('http://localhost/test/mvc/uribuilder/differentHostWithCreateAbsoluteUri/bla');
        self::assertEquals('http://my-host/my-path/test/mvc/uribuilder/target/my-path', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * Testcase for https://github.com/neos/flow-development-collection/issues/1803
     *
     * @test
     */
    public function urlPrefix_whenLinkingToSameHostTheUrlIsAsExpectedNotContainingDoubleSlashes()
    {
        $this->registerSingleRoute(UriBuilderSetDomainAndPathPrefixRoutePartHandler::class);
        $response = $this->browser->request('http://my-host/test/mvc/uribuilder/differentHost/bla');
        self::assertEquals('/my-path/test/mvc/uribuilder/target/my-path', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * Testcase for https://github.com/neos/flow-development-collection/issues/1803
     *
     * @test
     */
    public function urlPrefix_whenLinkingToSameHostTheUrlIsAsExpectedNotContainingDoubleSlashes_forceAbsoluteUrls()
    {
        $this->registerSingleRoute(UriBuilderSetDomainAndPathPrefixRoutePartHandler::class);
        $response = $this->browser->request('http://my-host/test/mvc/uribuilder/differentHostWithCreateAbsoluteUri/bla');
        self::assertEquals('http://my-host/my-path/test/mvc/uribuilder/target/my-path', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }
}
