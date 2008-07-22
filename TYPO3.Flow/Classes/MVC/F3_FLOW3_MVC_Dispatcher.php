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
 * @version $Id:F3_FLOW3_MVC_Dispatcher.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Dispatcher.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Dispatcher {

	/**
	 * @var F3_FLOW3_Component_ManagerInterface A reference to the component manager
	 */
	protected $componentManager;

	/**
	 * @var F3_FLOW3_Component_FactoryInterface A reference to the component factory
	 */
	protected $componentFactory;

	/**
	 * @var F3_FLOW3_Security_ContextHolderInterface A reference to the security contextholder
	 */
	protected $securityContextHolder;

	/**
	 * @var F3_FLOW3_Security_Auhtorization_FirewallInterface A reference to the firewall
	 */
	protected $firewall;

	/**
	 * @var F3_FLOW3_Configuration_Manager A reference to the configuration manager
	 */
	protected $configurationManager;

	/**
	 * Constructs the global dispatcher
	 *
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager A reference to the component manager
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory A reference to the component factory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager, F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->componentManager = $componentManager;
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Injects the security context holder
	 *
	 * @param F3_FLOW3_Security_ContextHolderInterface $securityContextHolder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSecurityContextHolder(F3_FLOW3_Security_ContextHolderInterface $securityContextHolder) {
		$this->securityContextHolder = $securityContextHolder;
	}

	/**
	 * Injects the authorization firewall
	 *
	 * @param F3_FLOW3_Security_Authorization_FirewallInterface $firewall
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectFirewall(F3_FLOW3_Security_Authorization_FirewallInterface $firewall) {
		$this->firewall = $firewall;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param F3_FLOW3_Configuration_Manager $configurationManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(F3_FLOW3_Configuration_Manager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Dispatches a request to a controller and initializes the security framework.
	 *
	 * @param F3_FLOW3_MVC_RequestInterface $request The request to dispatch
	 * @param F3_FLOW3_MVC_ResponseInterface $response The response, to be modified by the controller
	 * @return void
	 * @throws F3_FLOW3_MVC_Exception_NoSuchController, F3_FLOW3_MVC_Exception_InvalidController
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo dispatch until $request->isHandled()
	 * @todo implement forwards
	 */
	public function dispatch(F3_FLOW3_MVC_Request $request, F3_FLOW3_MVC_Response $response) {
		$controllerName = $request->getControllerName();
		if (!$this->componentManager->isComponentRegistered($controllerName)) throw new F3_FLOW3_MVC_Exception_NoSuchController('Invalid controller "' . $controllerName . '". The controller "' . $controllerName . '" is not a registered component.', 1202921618);

		$controller = $this->componentFactory->getComponent($controllerName);
		if (!$controller instanceof F3_FLOW3_MVC_Controller_RequestHandlingController) throw new F3_FLOW3_MVC_Exception_InvalidController('Invalid controller "' . $controllerName . '". The controller must be a valid request handling controller.', 1202921619);

		list(, $controllerPackageKey) = explode('_', $controllerName);
		$controller->setSettings($this->configurationManager->getConfiguration($controllerPackageKey, F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_SETTINGS));

		$this->securityContextHolder->initializeContext($request);
		$this->firewall->analyzeRequest($request);

		$controller->processRequest($request, $response);
	}
}
?>