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

        $route = $this->registerRoute('testa', 'test/mvc/uribuilder/{@action}/{someRoutePart}', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
            '@controller' => 'UriBuilder',
            '@action' => 'index',
            '@format' => 'html'
        ]);
        $route->setRoutePartsConfiguration([
            'someRoutePart' => [
                'handler' => UriBuilderSetDomainRoutePartHandler::class
            ]
        ]);

        $this->serverRequestFactory = $this->objectManager->get(ServerRequestFactoryInterface::class);
    }

    /**
     * Testcase for https://github.com/neos/flow-development-collection/issues/1803
     *
     * @test
     */
    public function whenLinkingToDifferentHostTheUrlIsAsExpectedNotContainingDoubleSlashes()
    {
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
        $response = $this->browser->request('http://my-host/test/mvc/uribuilder/differentHost/bla');
        self::assertEquals('test/mvc/uribuilder/target/my-path', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * Testcase for https://github.com/neos/flow-development-collection/issues/1803
     *
     * @test
     */
    public function whenLinkingToSameHostTheUrlIsAsExpectedNotContainingDoubleSlashes_forceAbsoluteUrls()
    {
        $response = $this->browser->request('http://my-host/test/mvc/uribuilder/differentHostWithCreateAbsoluteUri/bla');
        self::assertEquals('http://my-host/test/mvc/uribuilder/target/my-path', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }
}
