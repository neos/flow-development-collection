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

use Neos\Flow\Http\Client;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestAController;
use Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\RoutingTestAController;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Utility\Arrays;

/**
 * Functional tests for the Router
 *
 * HINT: The routes used in these tests are defined in the Routes.yaml file in the
 *       Testing context of the Flow package configuration.
 */
class RoutingTest extends FunctionalTestCase
{
    /**
     * Validate that test routes are loaded
     */
    public function setUp()
    {
        parent::setUp();

        $foundRoute = false;
        /** @var $route Route */
        foreach ($this->router->getRoutes() as $route) {
            if ($route->getName() === 'Flow :: Functional Test: HTTP - FooController') {
                $foundRoute = true;
                break;
            }
        }

        if (!$foundRoute) {
            $this->markTestSkipped('In this distribution the Flow routes are not included into the global configuration.');
            return;
        }
    }

    /**
     * @param Request $httpRequest
     * @param array $matchResults
     * @return ActionRequest
     */
    protected function createActionRequest(Request $httpRequest, array $matchResults = null)
    {
        $actionRequest = new ActionRequest($httpRequest);
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
        $requestUri = 'http://localhost/typo3/flow/test/httpmethods';
        $request = Request::create(new Uri($requestUri), 'GET');
        $matchResults = $this->router->route($request);
        $actionRequest = $this->createActionRequest($request, $matchResults);
        $this->assertEquals(ActionControllerTestAController::class, $actionRequest->getControllerObjectName());
        $this->assertEquals('first', $actionRequest->getControllerActionName());
    }

    /**
     * @test
     */
    public function httpMethodsAreRespectedForPostRequests()
    {
        $requestUri = 'http://localhost/typo3/flow/test/httpmethods';
        $request = Request::create(new Uri($requestUri), 'POST');
        $matchResults = $this->router->route($request);
        $actionRequest = $this->createActionRequest($request, $matchResults);
        $this->assertEquals(ActionControllerTestAController::class, $actionRequest->getControllerObjectName());
        $this->assertEquals('second', $actionRequest->getControllerActionName());
    }

    /**
     * Data provider for routeTests()
     *
     * @return array
     */
    public function routeTestsDataProvider()
    {
        return [
            // non existing route is not matched:
            [
                'requestUri' => 'http://localhost/typo3/flow/test/some/non/existing/route',
                'expectedMatchingRouteName' => null
            ],

            // static route parts are case sensitive:
            [
                'requestUri' => 'http://localhost/typo3/flow/test/Upper/Camel/Case',
                'expectedMatchingRouteName' => 'static route parts are case sensitive'
            ],
            [
                'requestUri' => 'http://localhost/typo3/flow/test/upper/camel/case',
                'expectedMatchingRouteName' => null
            ],

            // dynamic route parts are case insensitive
            [
                'requestUri' => 'http://localhost/typo3/flow/test/Neos.Flow/ActionControllerTestA/index.html',
                'expectedMatchingRouteName' => 'controller route parts are case insensitive',
                'expectedControllerObjectName' => ActionControllerTestAController::class
            ],
            [
                'requestUri' => 'http://localhost/typo3/flow/test/neos.flow/actioncontrollertesta/index.HTML',
                'expectedMatchingRouteName' => 'controller route parts are case insensitive',
                'expectedControllerObjectName' => ActionControllerTestAController::class
            ],

            // dynamic route part defaults are overwritten by request path
            [
                'requestUri' => 'http://localhost/typo3/flow/test/dynamic/part/without/default/DynamicOverwritten',
                'expectedMatchingRouteName' => 'dynamic part without default',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['dynamic' => 'DynamicOverwritten']
            ],
            [
                'requestUri' => 'http://localhost/typo3/flow/test/dynamic/part/with/default/DynamicOverwritten',
                'expectedMatchingRouteName' => 'dynamic part with default',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['dynamic' => 'DynamicOverwritten']
            ],
            [
                'requestUri' => 'http://localhost/typo3/flow/test/optional/dynamic/part/with/default/DynamicOverwritten',
                'expectedMatchingRouteName' => 'optional dynamic part with default',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['optionalDynamic' => 'DynamicOverwritten']
            ],
            [
                'requestUri' => 'http://localhost/typo3/flow/test/optional/dynamic/part/with/default',
                'expectedMatchingRouteName' => 'optional dynamic part with default',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['optionalDynamic' => 'OptionalDynamicDefault']
            ],
            [
                'requestUri' => 'http://localhost/typo3/flow/test/optional/dynamic/part/with/default',
                'expectedMatchingRouteName' => 'optional dynamic part with default',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['optionalDynamic' => 'OptionalDynamicDefault']
            ],

            // toLowerCase has no effect when matching routes
            [
                'requestUri' => 'http://localhost/typo3/flow/test/dynamic/part/case/Dynamic1Overwritten/Dynamic2Overwritten',
                'expectedMatchingRouteName' => 'dynamic part case',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['dynamic1' => 'Dynamic1Overwritten', 'dynamic2' => 'Dynamic2Overwritten']
            ],

            // query arguments are ignored when matching routes
            [
                'requestUri' => 'http://localhost/typo3/flow/test/exceeding/arguments2/FromPath?dynamic=FromQuery',
                'expectedMatchingRouteName' => 'exceeding arguments 02',
                'expectedControllerObjectName' => RoutingTestAController::class,
                'expectedArguments' => ['dynamic' => 'FromPath']
            ],
            [
                'requestUri' => 'http://localhost/typo3/flow/test/exceeding/arguments1?dynamic=FromQuery',
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
        $request = Request::create(new Uri($requestUri));
        $matchResults = $this->router->route($request);
        $actionRequest = $this->createActionRequest($request, $matchResults);
        $matchedRoute = $this->router->getLastMatchedRoute();
        if ($expectedMatchingRouteName === null) {
            if ($matchedRoute !== null) {
                $this->fail('Expected no route to match URI "' . $requestUri . '" but route "' . $matchedRoute->getName() . '" matched');
            }
        } else {
            if ($matchedRoute === null) {
                $this->fail('Expected route "' . $expectedMatchingRouteName . '" to match, but no route matched request URI "' . $requestUri . '"');
            } else {
                $this->assertEquals('Flow :: Functional Test: ' . $expectedMatchingRouteName, $matchedRoute->getName());
            }
        }
        $this->assertEquals($expectedControllerObjectName, $actionRequest->getControllerObjectName());
        if ($expectedArguments !== null) {
            $this->assertEquals($expectedArguments, $actionRequest->getArguments());
        }
    }

    /**
     * Data provider for resolveTests()
     *
     * @return array
     */
    public function resolveTestsDataProvider()
    {
        $defaults = ['@package' => 'Neos.Flow', '@subpackage' => 'Tests\Functional\Mvc\Fixtures', '@controller' => 'RoutingTestA'];
        return [
            // route resolves no matter if defaults are equal to route values
            [
                'routeValues' => array_merge($defaults, ['dynamic' => 'DynamicDefault']),
                'expectedResolvedRouteName' => 'dynamic part without default',
                'expectedResolvedUriPath' => 'typo3/flow/test/dynamic/part/without/default/dynamicdefault'
            ],
            [
                'routeValues' => array_merge($defaults, ['dynamic' => 'OverwrittenDynamicValue']),
                'expectedResolvedRouteName' => 'dynamic part without default',
                'expectedResolvedUriPath' => 'typo3/flow/test/dynamic/part/without/default/overwrittendynamicvalue'
            ],

            // if route value is omitted, only routes with a default value resolve
            [
                'routeValues' => $defaults,
                'expectedResolvedRouteName' => 'dynamic part with default',
                'expectedResolvedUriPath' => 'typo3/flow/test/dynamic/part/with/default/DynamicDefault'
            ],
            [
                'routeValues' => array_merge($defaults, ['optionalDynamic' => 'OptionalDynamicDefault']),
                'expectedResolvedRouteName' => 'optional dynamic part with default',
                'expectedResolvedUriPath' => 'typo3/flow/test/optional/dynamic/part/with/default'
            ],

            // toLowerCase has an effect on generated URIs
            [
                'routeValues' => array_merge($defaults, ['dynamic1' => 'DynamicRouteValue1', 'dynamic2' => 'DynamicRouteValue2']),
                'expectedResolvedRouteName' => 'dynamic part case',
                'expectedResolvedUriPath' => 'typo3/flow/test/dynamic/part/case/DynamicRouteValue1/dynamicroutevalue2'
            ],

            // exceeding arguments are appended to resolved URI if appendExceedingArguments is set
            [
                'routeValues' => array_merge($defaults, ['@action' => 'test1', 'dynamic' => 'DynamicDefault', 'exceedingArgument2' => 'foo', 'exceedingArgument1' => 'bar']),
                'expectedResolvedRouteName' => 'exceeding arguments 01',
                'expectedResolvedUriPath' => 'typo3/flow/test/exceeding/arguments1?%40action=test1&exceedingArgument2=foo&exceedingArgument1=bar'
            ],
            [
                'routeValues' => array_merge($defaults, ['@action' => 'test1', 'exceedingArgument2' => 'foo', 'exceedingArgument1' => 'bar', 'dynamic' => 'DynamicOther']),
                'expectedResolvedRouteName' => 'exceeding arguments 02',
                'expectedResolvedUriPath' => 'typo3/flow/test/exceeding/arguments2/dynamicother?%40action=test1&exceedingArgument2=foo&exceedingArgument1=bar'
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
        $resolvedUriPath = $this->router->resolve($routeValues);
        $resolvedRoute = $this->router->getLastResolvedRoute();
        if ($expectedResolvedRouteName === null) {
            if ($resolvedRoute !== null) {
                $this->fail('Expected no route to resolve but route "' . $resolvedRoute->getName() . '" resolved');
            }
        } else {
            if ($resolvedRoute === null) {
                $this->fail('Expected route "' . $expectedResolvedRouteName . '" to resolve');
            } else {
                $this->assertEquals('Flow :: Functional Test: ' . $expectedResolvedRouteName, $resolvedRoute->getName());
            }
        }
        $this->assertEquals($expectedResolvedUriPath, $resolvedUriPath);
    }

    /**
     * @return array
     */
    public function requestMethodAcceptArray()
    {
        return [
            ['GET', '404 Not Found'],
            ['PUT', '404 Not Found'],
            ['POST', '200 OK'],
            ['DELETE', '200 OK']
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
        $this->assertEquals($expectedStatus, $response->getStatus());
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
        $actualResult = $this->router->resolve($routeValues);

        $this->assertSame('typo3/flow/test/http/foo', $actualResult);
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
        $this->assertSame('custom/uri/pattern', $this->router->resolve($routeValues));

        // reset router configuration for following tests
        $this->router->setRoutesConfiguration(null);
    }
}
