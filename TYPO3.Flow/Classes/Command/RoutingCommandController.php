<?php
namespace TYPO3\FLOW3\Command;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Command controller for tasks related to routing
 *
 * @FLOW3\Scope("singleton")
 */
class RoutingCommandController extends \TYPO3\FLOW3\Cli\CommandController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Mvc\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * List the known routes
	 *
	 * This command displays a list of all currently registered routes.
	 *
	 * @return void
	 */
	public function listCommand() {
		$this->initializeRouter();

		$this->outputLine('Currently registered routes:');
		foreach ($this->router->getRoutes() as $index => $route) {
			$uriPattern = $route->getUriPattern();
			$this->outputLine(str_pad(($index + 1) . '. ' . $uriPattern, 80) . $route->getName());
		}
	}

	/**
	 * Show informations for a route
	 *
	 * This command displays the configuration of a route specified by index number.
	 *
	 * @param integer $index The index of the route as given by routing:list
	 * @return void
	 */
	public function showCommand($index) {
		$this->initializeRouter();

		$routes = $this->router->getRoutes();
		if (isset($routes[$index - 1])) {
			$route = $routes[$index - 1];

			$this->outputLine('<b>Information for route ' . $index . ':</b>');
			$this->outputLine('  Name: ' . $route->getName());
			$this->outputLine('  Pattern: ' . $route->getUriPattern());
			$this->outputLine('  Defaults: ');
			foreach ($route->getDefaults() as $defaultKey => $defaultValue) {
				$this->outputLine('    - ' . $defaultKey . ' => ' . $defaultValue);
			}
			$this->outputLine('  Append: ' . ($route->getAppendExceedingArguments() ? 'TRUE' : 'FALSE'));
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
	 * ./flow3 routing:getPath --format json Acme.Demo\\Sub\\Package
	 *
	 * @param string $package Package key and subpackage, subpackage parts are separated with backslashes
	 * @param string $controller Controller name, default is 'Standard'
	 * @param string $action Action name, default is 'index'
	 * @param string $format Requested Format name default is 'html'
	 * @return void
	 */
	public function getPathCommand($package, $controller = 'Standard', $action = 'index', $format = 'html') {
		$this->initializeRouter();

		$packageParts = explode('\\', $package, 2);
		$package = $packageParts[0];
		$subpackage = isset($packageParts[1]) ? $packageParts[1] : NULL;

		$routeValues = array(
			'@package' => $package,
			'@subpackage' => $subpackage,
			'@controller' => $controller,
			'@action' => $action,
			'@format' => $format
		);

		$this->outputLine('<b>Resolving:</b>');
		$this->outputLine('  Package: ' . $routeValues['@package']);
		$this->outputLine('  Subpackage: ' . $routeValues['@subpackage']);
		$this->outputLine('  Controller: ' . $routeValues['@controller']);
		$this->outputLine('  Action: ' . $routeValues['@action']);
		$this->outputLine('  Format: ' . $routeValues['@format']);

		foreach ($this->router->getRoutes() as $route) {
			try {
				$resolves = $route->resolves($routeValues);
				$controllerObjectName = $this->router->getControllerObjectName($package, $subpackage, $controller);
			} catch (\TYPO3\FLOW3\Mvc\Routing\Exception\InvalidControllerException $e) {
				$resolves = FALSE;
			}

			if ($resolves === TRUE) {
				$this->outputLine('<b>Route:</b>');
				$this->outputLine('  Name: ' . $route->getName());
				$this->outputLine('  Pattern: ' . $route->getUriPattern());

				$this->outputLine('<b>Generated Path:</b>');
				$this->outputLine('  ' . $route->getMatchingUri());

				if($controllerObjectName !== NULL) {
					$this->outputLine('<b>Controller:</b>');
					$this->outputLine('  ' . $controllerObjectName);
				} else {
					$this->outputLine('<b>Controller Error:</b>');
					$this->outputLine('  !!! Controller Object was not found !!!');
				}
				return;
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
	 * @return void
	 */
	public function routePathCommand($path) {
		$this->initializeRouter();

		foreach ($this->router->getRoutes() as $route) {
			if ($route->matches($path) === TRUE) {

				$routeValues = $route->getMatchResults();
				if (!isset($routeValues['@subpackage'])) {
					$routeValues['@subpackage'] = '';
				}

				$this->outputLine('<b>Path:</b>');
				$this->outputLine('  ' . $path);

				$this->outputLine('<b>Route:</b>');
				$this->outputLine('  Name: ' . $route->getName());
				$this->outputLine('  Pattern: ' . $route->getUriPattern());

				$this->outputLine('<b>Result:</b>');
				$this->outputLine('  Package: ' . $routeValues['@package']);
				$this->outputLine('  Subpackage: ' . $routeValues['@subpackage']);
				$this->outputLine('  Controller: ' . $routeValues['@controller']);
				$this->outputLine('  Action: ' . $routeValues['@action']);
				$this->outputLine('  Format: ' . $routeValues['@format']);

				$controllerObjectName = $this->router->getControllerObjectName($routeValues['@package'], $routeValues['@subpackage'], $routeValues['@controller']);
				if ($controllerObjectName !== NULL) {
					$this->outputLine('<b>Controller:</b>');
					$this->outputLine('  ' . $controllerObjectName);
				} else {
					$this->outputLine('<b>Controller Error:</b>');
					$this->outputLine('  !!! No Controller Object found !!!');
				}

				return;
			}
		}
		$this->outputLine('No matching Route was found');
	}

	/**
	 * Initialize the injected router-object
	 *
	 * @return void
	 */
	protected function initializeRouter() {
		$routesConfiguration = $this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
		$this->router->setRoutesConfiguration($routesConfiguration);
	}
}
?>