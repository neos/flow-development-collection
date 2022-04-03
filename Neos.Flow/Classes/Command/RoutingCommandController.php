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
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Mvc\Exception\InvalidRoutePartValueException;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Utility\Arrays;

/**
 * Command controller for tasks related to routing
 *
 * @Flow\Scope("singleton")
 */
class RoutingCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @Flow\Inject
     * @var Router
     */
    protected $router;

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
        $this->outputLine('<b>Currently registered routes:</b>');
        $rows = [];
        /** @var Route $route */
        foreach ($this->router->getRoutes() as $index => $route) {
            $routeNumber = $index + 1;
            $rows[] = [
                '#' => $routeNumber,
                'uriPattern' => $route->getUriPattern(),
                'httpMethods' => $route->hasHttpMethodConstraints() ? implode(', ', $route->getHttpMethods()) : '<i>any</i>',
                'name' => $route->getName(),
            ];
        }
        $this->output->outputTable($rows, ['#', 'Uri Pattern', 'HTTP Method(s)', 'Name']);
        $this->outputLine();
        $this->outputLine('Run <i>./flow routing:show <index></i> to show details for a route');
    }

    /**
     * Show information for a route
     *
     * This command displays the configuration of a route specified by index number.
     *
     * @param integer $index The index of the route as given by routing:list
     * @return void
     * @throws StopCommandException
     */
    public function showCommand(int $index): void
    {
        /** @var Route[] $routes */
        $routes = $this->router->getRoutes();
        if (!isset($routes[$index - 1])) {
            $this->outputLine('<error>Route %d was not found!</error>', [$index]);
            $this->outputLine('Run <i>./flow routing:list</i> to show all registered routes');
            $this->quit(1);
            return;
        }
        $route = $routes[$index - 1];

        $this->outputLine('<b>Information for route #' . $index . ':</b>');
        $this->outputLine();
        $this->outputLine('<b>Name:</b> %s', [$route->getName()]);
        $this->outputLine('<b>URI Pattern:</b> %s', [$route->getUriPattern() === '' ? '<i>(empty)</i>' : $route->getUriPattern()]);
        $this->outputLine('<b>HTTP method(s):</b> %s', [$route->hasHttpMethodConstraints() ? implode(', ', $route->getHttpMethods()) : '<i>any</i>']);
        $this->outputLine('<b>Defaults:</b>');
        $this->outputArray($route->getDefaults(), 2);
        if ($route->getAppendExceedingArguments()) {
            $this->outputLine();
            $this->outputLine('  Exceeding arguments will be appended as query string');
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
     * @throws StopCommandException | InvalidRoutePartValueException
     * @deprecated since 7.0 @see resolveCommand(). Will probably be removed with 8.0
     * @internal
     */
    public function getPathCommand(string $package, string $controller = 'Standard', string $action = 'index', string $format = 'html'): void
    {
        $packageParts = explode('\\', $package, 2);
        $package = $packageParts[0];
        $subpackage = $packageParts[1] ?? null;

        $this->resolveCommand($package, $controller, $action, $format, $subpackage, null);
    }

    /**
     * Build an URI for the given parameters
     *
     * This command takes package, controller and action and displays the
     * resolved URI and which route matched (if any):
     *
     * ./flow routing:resolve Some.Package --controller SomeController --additional-arguments="{\"some-argument\": \"some-value\"}"
     *
     * @param string $package Package key (according to "@package" route value)
     * @param string|null $controller Controller name (according to "@controller" route value), default is 'Standard'
     * @param string|null $action Action name (according to "@action" route value), default is 'index'
     * @param string|null $format Requested Format name (according to "@format" route value), default is 'html'
     * @param string|null $subpackage SubPackage name (according to "@subpackage" route value)
     * @param string|null $additionalArguments Additional route values as JSON string. Make sure to specify this option as described in the description in order to prevent parsing issues
     * @param string|null $parameters Route parameters as JSON string. Make sure to specify this option as described in the description in order to prevent parsing issues
     * @param string|null $baseUri Base URI of the simulated request, default ist 'http://localhost'
     * @param bool|null $forceAbsoluteUri Whether or not to force the creation of an absolute URI
     * @return void
     * @throws StopCommandException | InvalidRoutePartValueException
     */
    public function resolveCommand(string $package, string $controller = null, string $action = null, string $format = null, string $subpackage = null, string $additionalArguments = null, string $parameters = null, string $baseUri = null, bool $forceAbsoluteUri = null): void
    {
        $routeValues = [
            '@package' => $package,
            '@controller' => $controller ?? 'Standard',
            '@action' => $action ?? 'index',
            '@format' => $format ?? 'html'
        ];
        if ($subpackage !== null) {
            $routeValues['@subpackage'] = $subpackage;
        }
        $additionalArgumentsValue = $this->parseJsonToArray($additionalArguments);
        if ($additionalArgumentsValue !== []) {
            $routeValues = Arrays::arrayMergeRecursiveOverrule($routeValues, $additionalArgumentsValue);
        }
        $routeParameters = $this->createRouteParametersFromJson($parameters);
        $resolveContext = new ResolveContext(new Uri($baseUri ?? 'http://localhost'), $routeValues, $forceAbsoluteUri ?? false, '', $routeParameters);

        $this->outputLine('<b>Resolving:</b>');
        $this->outputLine('  <b>Values:</b>');
        $this->outputArray($resolveContext->getRouteValues(), 4);
        $this->outputLine('  <b>Base URI:</b> %s', [$resolveContext->getBaseUri()]);
        $this->outputLine('  <b>Force absolute URI:</b> %s', [$resolveContext->isForceAbsoluteUri() ? 'yes' : 'no']);
        if (!$resolveContext->getParameters()->isEmpty()) {
            $this->outputLine('  <b>Parameters:</b>');
            $this->outputArray($resolveContext->getParameters()->toArray(), 4);
        }
        $this->outputLine();
        $this->output('  <b>=> Controller:</b> ');
        $this->outputControllerObjectName($package, $subpackage, $controller);
        $this->outputLine();

        /** @var Route|null $resolvedRoute */
        $resolvedRoute = null;
        $resolvedRouteNumber = 0;
        /** @var int $index */
        foreach ($this->router->getRoutes() as $index => $route) {
            /** @var Route $route */
            if ($route->resolves($resolveContext) === true) {
                $resolvedRoute = $route;
                $resolvedRouteNumber = $index + 1;
                break;
            }
        }

        if ($resolvedRoute === null) {
            $this->outputLine('<error>No route could resolve these values...</error>');
            $this->quit(1);
            return;
        }

        /** @var UriConstraints $uriConstraints */
        $uriConstraints = $resolvedRoute->getResolvedUriConstraints();
        $resolvedUri = $uriConstraints->applyTo($resolveContext->getBaseUri(), $resolveContext->isForceAbsoluteUri());

        $this->outputLine('<b><success>Route resolved!</success></b>');
        $this->outputLine('<b>Name:</b> %s', [$resolvedRoute->getName()]);
        $this->outputLine('<b>Pattern:</b> %s', [$resolvedRoute->getUriPattern() === '' ? '<i>(empty)</i>' : $resolvedRoute->getUriPattern()]);

        $this->outputLine();
        $this->outputLine('<b>Resolved URI:</b> <success>%s</success>', [$resolvedUri]);
        $this->outputLine();

        $this->outputRouteTags($resolvedRoute->getResolvedTags());

        $this->outputLine('Run <i>./flow routing:show %d</i> to show details about this route', [$resolvedRouteNumber]);
    }

    /**
     * Route the given route path
     *
     * This command takes a given path and displays the detected route and
     * the selected package, controller and action.
     *
     * @param string $path The route path to resolve
     * @param string $method The request method (GET, POST, PUT, DELETE, ...) to simulate
     * @throws InvalidRoutePartValueException | StopCommandException
     * @deprecated since 7.0 @see matchCommand(). Will probably be removed with 8.0
     * @internal
     */
    public function routePathCommand(string $path, string $method = 'GET'): void
    {
        $this->matchCommand('/' . ltrim($path, '/'), $method, null);
    }

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
        foreach ($this->router->getRoutes() as $index => $route) {
            /** @var Route $route */
            if ($route->matches($routeContext) === true) {
                $matchedRoute = $route;
                $matchedRouteNumber = $index + 1;
                break;
            }
        }

        if ($matchedRoute === null) {
            $this->outputLine('<error>No route could match %s request to URL <i>%s</i>...</error>', [$method, $requestUri]);
            $this->quit(1);
            return;
        }

        $this->outputLine('<b><success>Route matched!</success></b>');
        $this->outputLine('<b>Name:</b> %s', [$matchedRoute->getName()]);
        $this->outputLine('<b>Pattern:</b> %s', [$matchedRoute->getUriPattern() === '' ? '<i>(empty)</i>' : $matchedRoute->getUriPattern()]);

        $this->outputLine();
        $this->outputLine('<b>Results:</b>');
        $matchResults = $matchedRoute->getMatchResults();
        $this->outputArray($matchResults, 2);

        $this->outputLine();
        $this->output('<b>Matched Controller:</b> ');
        $this->outputControllerObjectName($matchResults['@package'] ?? '', $matchResults['@subpackage'] ?? null, $matchResults['@controller'] ?? null);
        $this->outputLine();

        $this->outputRouteTags($matchedRoute->getMatchedTags());

        $this->outputLine('Run <i>./flow routing:show %d</i> to show details about this route', [$matchedRouteNumber]);
    }

    /**
     * @param string|null $json
     * @return RouteParameters
     * @throws StopCommandException
     */
    private function createRouteParametersFromJson(?string $json): RouteParameters
    {
        $routeParameters = RouteParameters::createEmpty();
        if ($json === null) {
            return $routeParameters;
        }
        foreach ($this->parseJsonToArray($json) as $parameterName => $parameterValue) {
            try {
                $routeParameters = $routeParameters->withParameter($parameterName, $parameterValue);
            } catch (\InvalidArgumentException $exception) {
                $this->outputLine('<error>Failed to create Route Parameters from the given JSON string "%s": %s</error>', [$json, $exception->getMessage()]);
                $this->quit(1);
            }
        }
        return $routeParameters;
    }

    /**
     * Parses the given JSON string as array
     *
     * @param string|null $json
     * @return array
     * @throws StopCommandException
     */
    private function parseJsonToArray(?string $json): array
    {
        if ($json === null) {
            return [];
        }
        $parsedValue = \json_decode($json, true);
        if ($parsedValue === null && \json_last_error() !== JSON_ERROR_NONE) {
            $this->outputLine('<error>Failed to parse <i>%s</i> as JSON: %s</error>', [$json, \json_last_error_msg()]);
            $this->quit(1);
            return [];
        }
        if (!is_array($parsedValue)) {
            $this->outputLine('<error>Failed to parse <i>%s</i> to an array, please a provide valid JSON object that can be represented as PHP array</error>', [$json]);
            $this->quit(1);
            return [];
        }
        return $parsedValue;
    }

    /**
     * Outputs a (potentially multi-dimensional) array to the console
     *
     * @param array $array
     * @param int $indention
     */
    private function outputArray(array $array, int $indention): void
    {
        foreach ($array as $key => $value) {
            $this->output('%s%s:', [str_pad(' ', $indention), $key]);
            if (is_array($value)) {
                $this->outputLine();
                $this->outputArray($value, $indention + 2);
                return;
            }
            if (is_object($value)) {
                $this->outputLine(' object (%s)', [get_class($value)]);
            }
            $this->outputLine(' %s', [$value]);
        }
    }

    /**
     * Outputs the controller object name that corresponds to the given package, subpackage and controller to the console
     *
     * If the corresponding class is not known to the ObjectManager, an error message is added
     *
     * @param string $package
     * @param string|null $subpackage
     * @param string|null $controller
     */
    private function outputControllerObjectName(string $package, ?string $subpackage, ?string $controller): void
    {
        $possibleControllerObjectName = str_replace(['@package', '@subpackage', '@controller', '\\\\'], [str_replace('.', '\\', $package), $subpackage, $controller, '\\'], '@package\@subpackage\Controller\@controllerController');
        $controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($possibleControllerObjectName);
        if ($controllerObjectName === null) {
            $this->outputLine('<error>%s</error> (no corresponding class exists)', [$possibleControllerObjectName]);
        } else {
            $this->outputLine('<success>%s</success>', [$controllerObjectName]);
        }
    }


    /**
     * Outputs route tags to the console, if there are any
     *
     * @param RouteTags|null $routeTags
     */
    private function outputRouteTags(?RouteTags $routeTags): void
    {
        if ($routeTags === null) {
            return;
        }
        if ($routeTags->getTags() !== []) {
            $this->outputLine('<b>Tags:</b>');
            $this->outputArray($routeTags->getTags(), 2);
        }
        $this->outputLine();
    }
}
