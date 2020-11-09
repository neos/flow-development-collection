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

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestAController;
use Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\RoutingTestAController;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Utility\Arrays;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Functional tests for the Router
 *
 * HINT: The routes used in these tests are defined in the Routes.yaml file in the
 *       Testing context of the Flow package configuration.
 */
class RoutingTest extends FunctionalTestCase
{
    /**
     * @var ServerRequestFactoryInterface
     */
    protected $serverRequestFactory;

    /**
     * Validate that test routes are loaded
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->serverRequestFactory = $this->objectManager->get(ServerRequestFactoryInterface::class);

        $foundRoute = false;
        /** @var $route Route */
        foreach ($this->router->getRoutes() as $route) {
            if ($route->getName() === 'Neos.Flow :: Functional Test: HTTP - FooController') {
                $foundRoute = true;
                break;
            }
        }

        if (!$foundRoute) {
            self::markTestSkipped('In this distribution the Flow routes are not included into the global configuration.');
            return;
        }
    }

    /**
     * @param ServerRequestInterface $httpRequest
     * @param array $matchResults
     * @return ActionRequest
     */
    protected function createActionRequest(ServerRequestInterface $httpRequest, array $matchResults = null): ActionRequest
    {
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        if ($matchResults !== null) {
            $requestArguments = $actionRequest->getArguments();
            $mergedArguments = Arrays::arrayMergeRecursiveOverrule($requestArguments, $matchResults);
            $actionRequest->setArguments($mergedArguments);
        }
        return $actionRequest;
    }

    /**
     * @test
     */
    public function httpMethodsAreRespectedForGetRequests()
    {
        $requestUri = 'http://localhost/neos/flow/test/httpmethods';
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri($requestUri));
        $matchResults = $this->router->route(new RouteContext($request, RouteParameters::createEmpty()));
        $actionRequest = $this->createActionRequest($request, $matchResults);
        self::assertEquals(ActionControllerTestAController::class, $actionRequest->getControllerObjectName());
        self::assertEquals('first', $actionRequest->getControllerActionName());
    }

    /**
     * @test
     */
    public function httpMethodsAreRespectedForPostRequests()
    {
        $requestUri = 'http://localhost/neos/flow/test/httpmethods';
        $request = $this->serverRequestFactory->createServerRequest('POST', new Uri($requestUri));
        $matchResults = $this->router->route(new RouteContext($request, RouteParameters::createEmpty()));
        $actionRequest = $this->createActionRequest($request, $matchResults);
        self::assertEquals(ActionControllerTestAController::class, $actionRequest->getControllerObjectName());
        self::assertEquals('second', $actionRequest->getControllerActionName());
    }

    /**
     * Data provider for routeTests()
     *
     * @return array
     */
    public function routeTestsDataProvider(): array
    {
        return [
            // non existing route is not matched:
            [
                'requestUri' => 'http://localhost/neos/flow/test/some/non/existing/route',
                'expectedMatchingRouteName' => null
            ],

            // static route parts are case sensitive:
            [
                'requestUri' => 'http://localhost/neos/flow/test/Upper/Camel/Case',
                'expectedMatchingRouteName' => 'static route parts are case sensitive'
            ],
            [
                'requestUri' => 'http://localhost/neos/flow/test/upper/camel/case',
                'expectedMatchingRouteName' => null
            ],

            // dynamic route parts are case insensitive
            [
                'requestUri' => 'http://localhost/neos/flow/test/Neos.Flow/ActionControllerTestA/index.html',
                'expectedMatchingRouteName' => 'controller route parts are case insensitive',
                'expectedControllerObjectName' => ActionControllerTestAController::class
            ],
            [
                'requestUri' => 'http://localhost/neos/flow/test/neos.flow/actioncontrollertesta/index.HTML',
                'expectedMatchingRouteName' => 'controller route parts are case insensitive',
                'expectedControllerObjectName' => ActionControllerTestAController::class
            ],

            // dynamic route part defaults are overwritten by request path
            [
                'requestUri' => 'http://localhost/neos/flow/test/dynamic/part/without/default/DynamicOverwritten',
                'expectedMatchingRouteName' => 'dynamic part without default',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['dynamic' => 'DynamicOverwritten']
            ],
            [
                'requestUri' => 'http://localhost/neos/flow/test/dynamic/part/with/default/DynamicOverwritten',
                'expectedMatchingRouteName' => 'dynamic part with default',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['dynamic' => 'DynamicOverwritten']
            ],
            [
                'requestUri' => 'http://localhost/neos/flow/test/optional/dynamic/part/with/default/DynamicOverwritten',
                'expectedMatchingRouteName' => 'optional dynamic part with default',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['optionalDynamic' => 'DynamicOverwritten']
            ],
            [
                'requestUri' => 'http://localhost/neos/flow/test/optional/dynamic/part/with/default',
                'expectedMatchingRouteName' => 'optional dynamic part with default',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['optionalDynamic' => 'OptionalDynamicDefault']
            ],
            [
                'requestUri' => 'http://localhost/neos/flow/test/optional/dynamic/part/with/default',
                'expectedMatchingRouteName' => 'optional dynamic part with default',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['optionalDynamic' => 'OptionalDynamicDefault']
            ],

            // toLowerCase has no effect when matching routes
            [
                'requestUri' => 'http://localhost/neos/flow/test/dynamic/part/case/Dynamic1Overwritten/Dynamic2Overwritten',
                'expectedMatchingRouteName' => 'dynamic part case',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['dynamic1' => 'Dynamic1Overwritten', 'dynamic2' => 'Dynamic2Overwritten']
            ],

            // query arguments are ignored when matching routes
            [
                'requestUri' => 'http://localhost/neos/flow/test/exceeding/arguments2/FromPath?dynamic=FromQuery',
                'expectedMatchingRouteName' => 'exceeding arguments 02',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['dynamic' => 'FromPath']
            ],
            [
                'requestUri' => 'http://localhost/neos/flow/test/exceeding/arguments1?dynamic=FromQuery',
                'expectedMatchingRouteName' => 'exceeding arguments 01',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['dynamic' => 'DynamicDefault']
            ],
        ];
    }

    /**
     * @param string $requestUri request URI
     * @param string $expectedMatchingRouteName expected route
     * @param string $expectedControllerObjectName expected controller object name
     * @param array $expectedArguments expected request arguments after routing or NULL if this should not be checked
     * @test
     * @dataProvider routeTestsDataProvider
     */
    public function routeTests($requestUri, $expectedMatchingRouteName, $expectedControllerObjectName = null, array $expectedArguments = null)
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri($requestUri));
        try {
            $matchResults = $this->router->route(new RouteContext($request, RouteParameters::createEmpty()));
        } catch (NoMatchingRouteException $exception) {
            $matchResults = null;
        }
        $actionRequest = $this->createActionRequest($request, $matchResults);
        $matchedRoute = $this->router->getLastMatchedRoute();
        if ($expectedMatchingRouteName === null) {
            if ($matchedRoute !== null) {
                self::fail('Expected no route to match URI "' . $requestUri . '" but route "' . $matchedRoute->getName() . '" matched');
            }
        } else {
            if ($matchedRoute === null) {
                self::fail('Expected route "' . $expectedMatchingRouteName . '" to match, but no route matched request URI "' . $requestUri . '"');
            } else {
                self::assertEquals('Neos.Flow :: Functional Test: ' . $expectedMatchingRouteName, $matchedRoute->getName());
            }
        }
        self::assertEquals($expectedControllerObjectName, $actionRequest->getControllerObjectName());
        if ($expectedArguments !== null) {
            self::assertEquals($expectedArguments, $actionRequest->getArguments());
        }
    }

    /**
     * Data provider for resolveTests()
     *
     * @return array
     */
    public function resolveTestsDataProvider(): array
    {
        $defaults = ['@package' => 'Neos.Flow', '@subpackage' => 'Tests\Functional\Mvc\Fixtures', '@controller' => 'RoutingTestA'];
        return [
            // route resolves no matter if defaults are equal to route values
            [
                'routeValues' => array_merge($defaults, ['dynamic' => 'DynamicDefault']),
                'expectedResolvedRouteName' => 'dynamic part without default',
                'expectedResolvedUriPath' => '/neos/flow/test/dynamic/part/without/default/dynamicdefault'
            ],
            [
                'routeValues' => array_merge($defaults, ['dynamic' => 'OverwrittenDynamicValue']),
                'expectedResolvedRouteName' => 'dynamic part without default',
                'expectedResolvedUriPath' => '/neos/flow/test/dynamic/part/without/default/overwrittendynamicvalue'
            ],

            // if route value is omitted, only routes with a default value resolve
            [
                'routeValues' => $defaults,
                'expectedResolvedRouteName' => 'dynamic part with default',
                'expectedResolvedUriPath' => '/neos/flow/test/dynamic/part/with/default/DynamicDefault'
            ],
            [
                'routeValues' => array_merge($defaults, ['optionalDynamic' => 'OptionalDynamicDefault']),
                'expectedResolvedRouteName' => 'optional dynamic part with default',
                'expectedResolvedUriPath' => '/neos/flow/test/optional/dynamic/part/with/default'
            ],

            // toLowerCase has an effect on generated URIs
            [
                'routeValues' => array_merge($defaults, ['dynamic1' => 'DynamicRouteValue1', 'dynamic2' => 'DynamicRouteValue2']),
                'expectedResolvedRouteName' => 'dynamic part case',
                'expectedResolvedUriPath' => '/neos/flow/test/dynamic/part/case/DynamicRouteValue1/dynamicroutevalue2'
            ],

            // exceeding arguments are appended to resolved URI if appendExceedingArguments is set
            [
                'routeValues' => array_merge($defaults, ['@action' => 'test1', 'dynamic' => 'DynamicDefault', 'exceedingArgument2' => 'foo', 'exceedingArgument1' => 'bar']),
                'expectedResolvedRouteName' => 'exceeding arguments 01',
                'expectedResolvedUriPath' => '/neos/flow/test/exceeding/arguments1?%40action=test1&exceedingArgument2=foo&exceedingArgument1=bar'
            ],
            [
                'routeValues' => array_merge($defaults, ['@action' => 'test1', 'exceedingArgument2' => 'foo', 'exceedingArgument1' => 'bar', 'dynamic' => 'DynamicOther']),
                'expectedResolvedRouteName' => 'exceeding arguments 02',
                'expectedResolvedUriPath' => '/neos/flow/test/exceeding/arguments2/dynamicother?%40action=test1&exceedingArgument2=foo&exceedingArgument1=bar'
            ],
        ];
    }

    /**
     * @param array $routeValues route values to resolve
     * @param string $expectedResolvedRouteName expected route
     * @param string $expectedResolvedUriPath expected matching URI
     * @test
     * @dataProvider resolveTestsDataProvider
     */
    public function resolveTests(array $routeValues, $expectedResolvedRouteName, $expectedResolvedUriPath = null)
    {
        $baseUri = new Uri('http://localhost');
        $resolvedUriPath = $this->router->resolve(new ResolveContext($baseUri, $routeValues, false, '', RouteParameters::createEmpty()));
        $resolvedRoute = $this->router->getLastResolvedRoute();
        if ($expectedResolvedRouteName === null) {
            if ($resolvedRoute !== null) {
                self::fail('Expected no route to resolve but route "' . $resolvedRoute->getName() . '" resolved');
            }
        } else {
            if ($resolvedRoute === null) {
                self::fail('Expected route "' . $expectedResolvedRouteName . '" to resolve');
            } else {
                self::assertEquals('Neos.Flow :: Functional Test: ' . $expectedResolvedRouteName, $resolvedRoute->getName());
            }
        }
        self::assertEquals($expectedResolvedUriPath, $resolvedUriPath);
    }

    /**
     * @return array
     */
    public function requestMethodAcceptArray(): array
    {
        return [
            ['GET', 404],
            ['PUT', 404],
            ['POST', 200],
            ['DELETE', 200]
        ];
    }

    /**
     * @test
     * @dataProvider requestMethodAcceptArray
     */
    public function routesWithoutRequestedHttpMethodConfiguredResultInA404($requestMethod, $expectedStatus)
    {
        $this->registerRoute(
            'HTTP Method Test',
            'http-method-test',
            [
                '@package' => 'Neos.Flow',
                '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
                '@controller' => 'ActionControllerTestA',
                '@action' => 'second',
                '@format' =>'html'
            ],
            false,
            ['POST', 'DELETE']
        );

        $response = $this->browser->request('http://localhost/http-method-test/', $requestMethod);
        self::assertEquals($expectedStatus, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function routerInitializesRoutesIfNotInjectedExplicitly()
    {
        $routeValues = [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Http\Fixtures',
            '@controller' => 'Foo',
            '@action' => 'index',
            '@format' => 'html'
        ];
        $baseUri = new Uri('http://localhost');
        $actualResult = $this->router->resolve(new ResolveContext($baseUri, $routeValues, false, '', RouteParameters::createEmpty()));

        self::assertSame('/neos/flow/test/http/foo', (string)$actualResult);
    }

    /**
     * @test
     */
    public function uriPathPrefixIsRespectedInRoute()
    {
        $routeValues = [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Http\Fixtures',
            '@controller' => 'Foo',
            '@action' => 'index',
            '@format' => 'html'
        ];
        $baseUri = new Uri('http://localhost');
        $actualResult = $this->router->resolve(new ResolveContext($baseUri, $routeValues, false, 'index.php/', RouteParameters::createEmpty()));

        self::assertSame('/index.php/neos/flow/test/http/foo', (string)$actualResult);
    }

    /**
     * @test
     */
    public function explicitlySpecifiedRoutesOverruleConfiguredRoutes()
    {
        $routeValues = [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Http\Fixtures',
            '@controller' => 'Foo',
            '@action' => 'index',
            '@format' => 'html'
        ];
        $routesConfiguration = [
            [
                'uriPattern' => 'custom/uri/pattern',
                'defaults' => [
                    '@package' => 'Neos.Flow',
                    '@subpackage' => 'Tests\Functional\Http\Fixtures',
                    '@controller' => 'Foo',
                    '@action' => 'index',
                    '@format' => 'html'
                ],
            ]
        ];
        $this->router->setRoutesConfiguration($routesConfiguration);
        $baseUri = new Uri('http://localhost');
        $actualResult = $this->router->resolve(new ResolveContext($baseUri, $routeValues, false, '', RouteParameters::createEmpty()));
        self::assertSame('/custom/uri/pattern', (string)$actualResult);

        // reset router configuration for following tests
        $this->router->setRoutesConfiguration(null);
    }
}
