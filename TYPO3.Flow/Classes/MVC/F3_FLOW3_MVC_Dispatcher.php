<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC;

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
 * @version $Id:F3::FLOW3::MVC::Dispatcher.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3::FLOW3::MVC::Dispatcher.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Dispatcher {

	/**
	 * @var F3::FLOW3::Component::ManagerInterface A reference to the component manager
	 */
	protected $componentManager;

	/**
	 * @var F3::FLOW3::Component::FactoryInterface A reference to the component factory
	 */
	protected $componentFactory;

	/**
	 * @var F3::FLOW3::Security::ContextHolderInterface A reference to the security contextholder
	 */
	protected $securityContextHolder;

	/**
	 * @var F3::FLOW3::Security::Auhtorization::FirewallInterface A reference to the firewall
	 */
	protected $firewall;

	/**
	 * @var F3::FLOW3::Configuration::Manager A reference to the configuration manager
	 */
	protected $configurationManager;

	/**
	 * Constructs the global dispatcher
	 *
	 * @param F3::FLOW3::Component::ManagerInterface $componentManager A reference to the component manager
	 * @param F3::FLOW3::Component::FactoryInterface $componentFactory A reference to the component factory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3::FLOW3::Component::ManagerInterface $componentManager, F3::FLOW3::Component::FactoryInterface $componentFactory) {
		$this->componentManager = $componentManager;
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Injects the security context holder
	 *
	 * @param F3::FLOW3::Security::ContextHolderInterface $securityContextHolder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSecurityContextHolder(F3::FLOW3::Security::ContextHolderInterface $securityContextHolder) {
		$this->securityContextHolder = $securityContextHolder;
	}

	/**
	 * Injects the authorization firewall
	 *
	 * @param F3::FLOW3::Security::Authorization::FirewallInterface $firewall
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectFirewall(F3::FLOW3::Security::Authorization::FirewallInterface $firewall) {
		$this->firewall = $firewall;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param F3::FLOW3::Configuration::Manager $configurationManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(F3::FLOW3::Configuration::Manager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Dispatches a request to a controller and initializes the security framework.
	 *
	 * @param F3::FLOW3::MVC::RequestInterface $request The request to dispatch
	 * @param F3::FLOW3::MVC::ResponseInterface $response The response, to be modified by the controller
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function dispatch(F3::FLOW3::MVC::Request $request, F3::FLOW3::MVC::Response $response) {
		$dispatchLoopCount = 0;
		while (!$request->isDispatched()) {
			$dispatchLoopCount ++;
			if ($dispatchLoopCount > 99) throw new F3::FLOW3::MVC::Exception::InfiniteLoop('Could not ultimately dispatch the request after '  . $dispatchLoopCount . ' iterations.', 1217839467);

			$this->securityContextHolder->initializeContext($request);
			$this->firewall->blockIllegalRequests($request);

			$controller = $this->getPreparedController($request);
			try {
				$controller->processRequest($request, $response);
			} catch (F3::FLOW3::MVC::Exception::StopAction $ignoredException) {
			}
		}
	}

	/**
	 * Resolves, prepares and returns the controller which is specified in the request object.
	 *
	 * @param F3::FLOW3::MVC::Request $request The current request
	 * @return F3::FLOW3::MVC::Controller::RequestHandlingController The controller
	 * @throws F3::FLOW3::MVC::Exception::NoSuchController, F3::FLOW3::MVC::Exception::InvalidController
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getPreparedController(F3::FLOW3::MVC::Request $request) {
		$controllerComponentName = $request->getControllerComponentName();
		$controller = $this->componentFactory->getComponent($controllerComponentName);
		if (!$controller instanceof F3::FLOW3::MVC::Controller::RequestHandlingController) throw new F3::FLOW3::MVC::Exception::InvalidController('Invalid controller "' . $controllerComponentName . '". The controller must be a valid request handling controller.', 1202921619);

		$controller->setSettings($this->configurationManager->getSettings($request->getControllerPackageKey()));
		return $controller;
	}
}
?>