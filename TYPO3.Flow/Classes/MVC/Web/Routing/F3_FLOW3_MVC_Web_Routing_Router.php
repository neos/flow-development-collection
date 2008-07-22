<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * The default web router
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Web_Routing_Router implements F3_FLOW3_MVC_Web_Routing_RouterInterface {

	/**
	 * @var F3_FLOW3_Component_ManagerInterface $componentManager: A reference to the Component Manager
	 */
	protected $componentManager;

	/**
	 * @var F3_FLOW3_Component_FactoryInterface $componentFactory
	 */
	protected $componentFactory;

	/**
	 * @var F3_FLOW3_Utility_Environment
	 */
	protected $utilityEnvironment;

	/**
	 * @var F3_FLOW3_Configuration_Container The FLOW3 configuration
	 */
	protected $configuration;

	/**
	 * Array of routes to match against
	 * @var array
	 */
	protected $routes = array();

	/**
	 * Constructor
	 *
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager A reference to the component manager
	 * @param F3_FLOW3_Utility_Environment $utilityEnvironment A reference to the environment
	 * @param F3_FLOW3_Configuration_Manager $configurationManager A reference to the configuration manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager, F3_FLOW3_Utility_Environment $utilityEnvironment, F3_FLOW3_Configuration_Manager $configurationManager) {
		$this->componentManager = $componentManager;
		$this->componentFactory = $componentManager->getComponentFactory();
		$this->utilityEnvironment = $utilityEnvironment;
		$this->configuration = $configurationManager->getConfiguration('FLOW3', F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_ROUTES);

		if (isset($this->configuration->routes)) {
			foreach ($this->configuration->routes as $routeName => $routeConfiguration) {
				$route = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Routing_RouteInterface');
				$route->setUrlPattern($routeConfiguration->urlPattern);
				$route->setDefaults($routeConfiguration->defaults);
				$this->routes[$routeName] = $route;
			}
		}
	}

	/**
	 * Routes the specified web request by setting the controller name, action and possible
	 * parameters. If the request could not be routed, it will be left untouched.
	 *
	 * @param F3_FLOW3_MVC_Web_Request $request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function route(F3_FLOW3_MVC_Web_Request $request) {
		$requestURI = $request->getRequestURI();
		$requestPath = F3_PHP6_Functions::substr($requestURI->getPath(), F3_PHP6_Functions::strlen((string)$request->getBaseURI()->getPath()));
		if (F3_PHP6_Functions::substr($requestPath, 0, 9) == 'index.php' || F3_PHP6_Functions::substr($requestPath, 0, 13) == 'index_dev.php') {
			$requestPath = strstr($requestPath, '/');
		}

		/** Find the matching route */
		foreach (array_reverse($this->routes) as $route) {
			if ($route->match($requestPath)) {
				$values = $route->getValues();
				$this->setControllerName($values['package'], $values['controller'], $request);
				$this->setActionName($values['action'], $request);
				break;
			}
		}

		foreach ($this->utilityEnvironment->getPOSTArguments() as $argumentName => $argumentValue) {
			$request->setArgument($argumentName, $argumentValue);
		}
		foreach ($requestURI->getArguments() as $argumentName => $argumentValue) {
			$request->setArgument($argumentName, $argumentValue);
		}
	}

	/**
	 * Determines and sets the controller name for the given web request
	 *
	 * @param array $requestPathSegments An array of the request path segements
	 * @param F3_FLOW3_MVC_Web_Request $request The web request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setControllerName($packageName, $controllerName, F3_FLOW3_MVC_Web_Request $request) {
		$controllerNamePrefix = 'F3_' . $packageName . '_Controller_';
		if ($controllerName == '') {
			$controllerName = 'Default';
		}

		$controllerName = $this->componentManager->getCaseSensitiveComponentName($controllerNamePrefix . $controllerName);
		if ($controllerName === FALSE) return;
		$request->setControllerName($controllerName);
	}

	/**
	 * Determines and sets the action name for the given web request
	 *
	 * @param array $requestPathSegments An array of the request path segements
	 * @param F3_FLOW3_MVC_Web_Request $request The web request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setActionName($actionName, F3_FLOW3_MVC_Web_Request $request) {
		if ($actionName == '') {
			$actionName = 'Default';
		}

		$request->setActionName($actionName);
	}
}
?>