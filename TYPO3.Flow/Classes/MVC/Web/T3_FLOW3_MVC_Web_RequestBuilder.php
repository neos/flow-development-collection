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
 * Builds a web request object from the raw HTTP information
 * 
 * @package		FLOW3
 * @subpackage	MVC
 * @version 	$Id:T3_FLOW3_MVC_Web_RequestBuilder.php 467 2008-02-06 19:34:56Z robert $
 * @author 		Robert Lemke <robert@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope		prototype
 */
class T3_FLOW3_MVC_Web_RequestBuilder {
	
	/**
	 * @var T3_FLOW3_Utility_Environment
	 */
	protected $utilityEnvironment;

	/**
	 * @var T3_FLOW3_Component_ManagerInterface $componentManager: A reference to the Component Manager
	 */
	 protected $componentManager;

	 /**
	 * Constructs the Web Request Builder
	 *
	 * @param  T3_FLOW3_Component_ManagerInterface $componentManager: A reference to the component manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(T3_FLOW3_Component_ManagerInterface $componentManager, T3_FLOW3_Utility_Environment $utilityEnvironment) {
		$this->componentManager = $componentManager;
		$this->utilityEnvironment = $utilityEnvironment;
	}
	
	/**
	 * Builds a web request object from the raw HTTP information
	 *
	 * @return T3_FLOW3_MVC_Web_Request		The web request as an object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build() {
		$request = $this->componentManager->getComponent('T3_FLOW3_MVC_Web_Request', $this->utilityEnvironment);
		$requestURI = $this->utilityEnvironment->getRequestURI();
		$request->setRequestURI($requestURI);
		
		/* TODO: Call router here, for now there's only a poor man's RealURL: */
		$requestPath = T3_PHP6_Functions::substr((string)$requestURI->getPath(), T3_PHP6_Functions::strlen((string)$request->getBaseURI()->getPath()));
		$requestPathSegments = explode('/', $requestPath);
		$this->setControllerName($requestPathSegments, $request);

		foreach ($this->utilityEnvironment->getPOSTArguments() as $argumentName => $argumentValue) {
			$request->setArgument($argumentName, $argumentValue);
		}
		foreach ($requestURI->getArguments() as $argumentName => $argumentValue) {
			$request->setArgument($argumentName, $argumentValue);
		}

		return $request;
	}

	/**
	 * Determines and sets the controller name for the given web request
	 * 
	 * @param  array						$requestPathSegments: An array of the request path segements
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
		 	if ($controllerName !== FALSE) {
		 		$this->setActionName($requestPathSegments, $request);
				$request->setControllerName($controllerName);
		 	}
		} else {
		 	$controllerName = $this->componentManager->getCaseSensitiveComponentName($controllerName);
		 	if ($controllerName === FALSE) return;
		}
		$request->setControllerName($controllerName);
	}

	/**
	 * Determines and sets the action name for the given web request
	 * 
	 * @param  array						$requestPathSegments: An array of the request path segements
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

	protected function setArguments() {

	}
}
?>