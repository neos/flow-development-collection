<?php
declare(strict_types=1);

namespace Neos\Flow\Command;

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
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Mvc\Exception\InvalidRoutePartValueException;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Mvc\Routing\RouteConfiguration;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Http\Factories\ServerRequestFactory;

/**
 * Command controller for tasks related to routing
 *
 * @Flow\Scope("singleton")
 */
class RoutingCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var RouteConfiguration
     */
    protected $routeConfiguration;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var ServerRequestFactory
     */
    protected $serverRequestFactory;

    /**
     * List the known routes
     *
     * This command displays a list of all currently registered routes.
     *
     * @return void
     */
    public function listCommand(): void
    {
        $this->outputLine('Currently registered routes:');
        /** @var Route $route */
        foreach ($this->routeConfiguration->getRoutes() as $index => $route) {
            $routeNumber = $index + 1;
            $rows[] = [
                '#' => $routeNumber,
                'uriPattern' => $route->getUriPattern(),
                'httpMethods' => $route->hasHttpMethodConstraints() ? implode(', ', $route->getHttpMethods()) : '<i>any</i>',
                'name' => $route->getName(),
            ];
        }
    }

    /**
     * Show information for a route
     *
     * This command displays the configuration of a route specified by index number.
     *
     * @param integer $index The index of the route as given by routing:list
     * @return void
     */
    public function showCommand(int $index): void
    {
        /** @var Route[] $routes */
        $routes = $this->routeConfiguration->getRoutes();
        if (!isset($routes[$index - 1])) {
            $this->outputLine('<error>Route %d was not found!</error>', [$index]);
            $this->outputLine('Run <i>./flow routing:list</i> to show all registered routes');
            $this->quit(1);
            return;
        }
        $route = $routes[$index - 1];

            $this->outputLine('<b>Information for route ' . $index . ':</b>');
            $this->outputLine('  Name: ' . $route->getName());
            $this->outputLine('  Pattern: ' . $route->getUriPattern());
            $this->outputLine('  Defaults: ');
            foreach ($route->getDefaults() as $defaultKey => $defaultValue) {
                $this->outputLine('    - ' . $defaultKey . ' => ' . $defaultValue);
            }
            $this->outputLine('  Append: ' . ($route->getAppendExceedingArguments() ? 'true' : 'false'));
        } else {
            $this->outputLine('Route ' . $index . ' was not found!');
        }
    }

    /**
     * Generate a route path
     *
     * This command takes package, controller and action and displays the
     * generated route path and the selected route:
     *
     * ./flow routing:getPath --format json Acme.Demo\\Sub\\Package
     *
     * @param string $package Package key and subpackage, subpackage parts are separated with backslashes
     * @param string $controller Controller name, default is 'Standard'
     * @param string $action Action name, default is 'index'
     * @param string $format Requested Format name default is 'html'
     * @return void
     */
    public function getPathCommand(string $package, string $controller = 'Standard', string $action = 'index', string $format = 'html'): void
    {
        $packageParts = explode('\\', $package, 2);
        $package = $packageParts[0];
        $subpackage = isset($packageParts[1]) ? $packageParts[1] : null;

        $routeValues = [
            '@package' => $package,
            '@subpackage' => $subpackage,
            '@controller' => $controller,
            '@action' => $action,
            '@format' => $format
        ];

        $this->outputLine('<b>Resolving:</b>');
        $this->outputLine('  Package: ' . $routeValues['@package']);
        $this->outputLine('  Subpackage: ' . $routeValues['@subpackage']);
        $this->outputLine('  Controller: ' . $routeValues['@controller']);
        $this->outputLine('  Action: ' . $routeValues['@action']);
        $this->outputLine('  Format: ' . $routeValues['@format']);

<<<<<<< HEAD
        $controllerObjectName = null;
        /** @var $route Route */
        foreach ($this->router->getRoutes() as $route) {
            try {
                $resolves = $route->resolves($routeValues);
                $controllerObjectName = $this->getControllerObjectName($package, $subpackage, $controller);
            } catch (InvalidRoutePartValueException $exception) {
                $resolves = false;
            }

            if ($resolves === true) {
                $this->outputLine('<b>Route:</b>');
                $this->outputLine('  Name: ' . $route->getName());
                $this->outputLine('  Pattern: ' . $route->getUriPattern());

                $this->outputLine('<b>Generated Path:</b>');
                $this->outputLine('  ' . $route->getResolvedUriConstraints()->getPathConstraint());

                if ($controllerObjectName !== null) {
                    $this->outputLine('<b>Controller:</b>');
                    $this->outputLine('  ' . $controllerObjectName);
                } else {
                    $this->outputLine('<b>Controller Error:</b>');
                    $this->outputLine('  !!! Controller Object was not found !!!');
                }
                return;
=======
        /** @var Route|null $resolvedRoute */
        $resolvedRoute = null;
        $resolvedRouteNumber = 0;
        /** @var int $index */
        foreach ($this->routeConfiguration->getRoutes() as $index => $route) {
            /** @var Route $route */
            if ($route->resolves($resolveContext) === true) {
                $resolvedRoute = $route;
                $resolvedRouteNumber = $index + 1;
                break;
>>>>>>> f8d23a99a (Change implementation in commandcontroller)
            }
        }
        $this->outputLine('<b>No Matching Controller found</b>');
    }

    /**
     * Route the given route path
     *
     * This command takes a given path and displays the detected route and
     * the selected package, controller and action.
     *
     * @param string $path The route path to resolve
     * @param string $method The request method (GET, POST, PUT, DELETE, ...) to simulate
     * @return void
     * @throws InvalidRoutePartValueException
     * @throws StopCommandException
     */
    public function routePathCommand(string $path, string $method = 'GET'): void
    {
        $httpRequest = $this->serverRequestFactory->createServerRequest($method, (new Uri('http://localhost/'))->withPath($path));
        $routeContext = new RouteContext($httpRequest, RouteParameters::createEmpty());

<<<<<<< HEAD
        /** @var Route $route */
        foreach ($this->router->getRoutes() as $route) {
=======
    /**
     * Match the given URI to a corresponding route
     *
     * This command takes an incoming URI and displays the
     * matched Route and the mapped routing values (if any):
     *
     * ./flow routing:match "/de" --parameters="{\"requestUriHost\": \"localhost\"}"
     *
     * @param string $uri The incoming route, absolute or relative
     * @param string|null $method The HTTP method to simulate (default is 'GET')
     * @param string|null $parameters Route parameters as JSON string. Make sure to specify this option as described in the description in order to prevent parsing issues
     * @throws InvalidRoutePartValueException | StopCommandException
     */
    public function matchCommand(string $uri, string $method = null, string $parameters = null): void
    {
        $method = $method ?? 'GET';
        $requestUri = new Uri($uri);
        if (isset($requestUri->getPath()[0]) && $requestUri->getPath()[0] !== '/') {
            $this->outputLine('<error>The URI "%s" is not valid. The path has to start with a "/"</error>', [$requestUri]);
            $this->quit(1);
            return;
        }
        $httpRequest = $this->serverRequestFactory->createServerRequest($method, $requestUri);
        $routeParameters = $this->createRouteParametersFromJson($parameters);
        $routeContext = new RouteContext($httpRequest, $routeParameters);

        $this->outputLine('<b>Matching:</b>');
        $this->outputLine('  <b>URI:</b> %s', [$httpRequest->getUri()]);
        $this->outputLine('  <b>Path:</b> %s', [RequestInformationHelper::getRelativeRequestPath($httpRequest)]);
        $this->outputLine('  <b>HTTP Method:</b> %s', [$method]);
        if (!$routeContext->getParameters()->isEmpty()) {
            $this->outputLine('  <b>Parameters:</b>');
            $this->outputArray($routeContext->getParameters()->toArray(), 4);
        }
        $this->outputLine();

        /** @var Route|null $matchedRoute */
        $matchedRoute = null;
        $matchedRouteNumber = 0;
        /** @var int $index */
        foreach ($this->routeConfiguration->getRoutes() as $index => $route) {
            /** @var Route $route */
>>>>>>> f8d23a99a (Change implementation in commandcontroller)
            if ($route->matches($routeContext) === true) {
                $routeValues = $route->getMatchResults();

                $this->outputLine('<b>Path:</b>');
                $this->outputLine('  ' . $path);

                $this->outputLine('<b>Route:</b>');
                $this->outputLine('  Name: ' . $route->getName());
                $this->outputLine('  Pattern: ' . $route->getUriPattern());

                $this->outputLine('<b>Result:</b>');
                $this->outputLine('  Package: ' . ($routeValues['@package'] ?? '-'));
                $this->outputLine('  Subpackage: ' . ($routeValues['@subpackage'] ?? '-'));
                $this->outputLine('  Controller: ' . ($routeValues['@controller'] ?? '-'));
                $this->outputLine('  Action: ' . ($routeValues['@action'] ?? '-'));
                $this->outputLine('  Format: ' . ($routeValues['@format'] ?? '-'));

                $controllerObjectName = $this->getControllerObjectName($routeValues['@package'] ?? '', $routeValues['@subpackage'] ?? '', $routeValues['@controller'] ?? '');
                if ($controllerObjectName === null) {
                    $this->outputLine('<b>Controller Error:</b>');
                    $this->outputLine('  !!! No Controller Object found !!!');
                    $this->quit(1);
                }
                $this->outputLine('<b>Controller:</b>');
                $this->outputLine('  ' . $controllerObjectName);
                $this->quit(0);
            }
        }
        $this->outputLine('No matching Route was found');
        $this->quit(1);
    }

    /**
     * Returns the object name of the controller defined by the package, subpackage key and
     * controller name or NULL if the controller does not exist
     *
     * @param string $packageKey the package key of the controller
     * @param string|null $subPackageKey the subpackage key of the controller
     * @param string $controllerName the controller name excluding the "Controller" suffix
     * @return string|null The controller's Object Name or NULL if the controller does not exist
     */
    protected function getControllerObjectName(string $packageKey, ?string $subPackageKey, string $controllerName): ?string
    {
        $possibleObjectName = '@package\@subpackage\Controller\@controllerController';
        $possibleObjectName = str_replace('@package', str_replace('.', '\\', $packageKey), $possibleObjectName);
        $possibleObjectName = str_replace('@subpackage', $subPackageKey, $possibleObjectName);
        $possibleObjectName = str_replace('@controller', $controllerName, $possibleObjectName);
        $possibleObjectName = str_replace('\\\\', '\\', $possibleObjectName);

        return $this->objectManager->getCaseSensitiveObjectName($possibleObjectName);
    }
}
