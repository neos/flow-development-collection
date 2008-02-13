<?php
declare(encoding = 'utf-8');

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
 * The default web router
 *
 * @package    FLOW3
 * @subpackage MVC
 * @version    $Id:T3_FLOW3_MVC_Web_RequestHandler.php 467 2008-02-06 19:34:56Z robert $
 * @copyright  Copyright belongs to the respective authors
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_MVC_Web_Router implements T3_FLOW3_MVC_Web_RouterInterface {

	/**
	 * @var T3_FLOW3_Component_ManagerInterface $componentManager: A reference to the Component Manager
	 */
	 protected $componentManager;

	/**
	 * @var T3_FLOW3_Utility_Environment
	 */
	protected $utilityEnvironment;

	/**
	 * Constructs the Web Request Builder
	 *
	 * @param  T3_FLOW3_Component_ManagerInterface $componentManager: A reference to the component manager
	 * @param  T3_FLOW3_Utility_Environment $utilityEnvironment: A reference to the environment
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(T3_FLOW3_Component_ManagerInterface $componentManager, T3_FLOW3_Utility_Environment $utilityEnvironment) {
		$this->componentManager = $componentManager;
		$this->utilityEnvironment = $utilityEnvironment;
	}

	/**
	 * Routes the specified web request by setting the controller name, action and possible
	 * parameters. If the request could not be routed, it will be left untouched.
	 *
	 * @param  T3_FLOW3_MVC_Web_Request $request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function route(T3_FLOW3_MVC_Web_Request $request) {
		$requestURI = $request->getRequestURI();
		$requestPath = T3_PHP6_Functions::substr($requestURI->getPath(), T3_PHP6_Functions::strlen((string)$request->getBaseURI()->getPath()));
		$requestPathSegments = explode('/', $requestPath);
		$this->setControllerName($requestPathSegments, $request);

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
	 * @param  array $requestPathSegments: An array of the request path segements
	 * @param  T3_FLOW3_MVC_Web_Request	$request: The web request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function setControllerName(array $requestPathSegments, T3_FLOW3_MVC_Web_Request $request) {
		if (!isset($requestPathSegments[0]) || $requestPathSegments[0] == '') return;

		$controllerNamePrefix = 'T3_' . $requestPathSegments[0] . '_Controller_';
		$controllerName = $controllerNamePrefix . 'Default';

		if (isset($requestPathSegments[1]) && T3_PHP6_Functions::strlen($requestPathSegments[1])) {
		 	$controllerName = $this->componentManager->getCaseSensitiveComponentName($controllerNamePrefix . $requestPathSegments[1]);
		 	if ($controllerName === FALSE) return;

		 	$this->setActionName($requestPathSegments, $request);
			$request->setControllerName($controllerName);
		} else {
		 	$controllerName = $this->componentManager->getCaseSensitiveComponentName($controllerName);
		 	if ($controllerName === FALSE) return;
		}
		$request->setControllerName($controllerName);
	}

	/**
	 * Determines and sets the action name for the given web request
	 *
	 * @param  array $requestPathSegments: An array of the request path segements
	 * @param  T3_FLOW3_MVC_Web_Request	$request: The web request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function setActionName(array $requestPathSegments, T3_FLOW3_MVC_Web_Request $request) {
		if (isset($requestPathSegments[2]) && T3_PHP6_Functions::strlen($requestPathSegments[2])) {
		 	$actionName = $requestPathSegments[2];
			$request->setActionName($actionName);
		}
	}
}
?>