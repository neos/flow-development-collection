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
 * @version $Id:F3_FLOW3_MVC_Web_RequestBuilder.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Builds a web request object from the raw HTTP information
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Web_RequestBuilder.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Web_RequestBuilder {

	/**
	 * @var F3_FLOW3_Component_FactoryInterface $componentFactory: A reference to the Component Factory
	 */
	protected $componentFactory;

	/**
	 * @var F3_FLOW3_Utility_Environment
	 */
	protected $environment;

	/**
	 * @var F3_FLOW3_Configuration_Manager
	 */
	protected $configurationManager;

	/**
	 * @var F3_FLOW3_MVC_Web_RouterInterface
	 */
	protected $router;

	/**
	 * Constructs this Web Request Builder
	 *
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory A reference to the component factory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Injects the server environment
	 *
	 * @param F3_FLOW3_Utility_Environment $environment The environment
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(F3_FLOW3_Utility_Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param F3_FLOW3_Configuration_Manager $configurationManager A reference to the configuration manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(F3_FLOW3_Configuration_Manager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Injects a router for routing the web request
	 *
	 * @param F3_FLOW3_MVC_Web_Routing_RouterInterface $router A router which routes the web request to a controller and action
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectRouter(F3_FLOW3_MVC_Web_Routing_RouterInterface $router) {
		$this->router = $router;
	}

	/**
	 * Builds a web request object from the raw HTTP information
	 *
	 * @return F3_FLOW3_MVC_Web_Request The web request as an object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build() {
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$request->injectEnvironment($this->environment);
		$request->setRequestURI($this->environment->getRequestURI());
		$request->setMethod($this->environment->getRequestMethod());

		$routesConfiguration = $this->configurationManager->getSpecialConfiguration(F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_ROUTES);
		$this->router->setRoutesConfiguration($routesConfiguration);
		$this->router->route($request);

		return $request;
	}
}
?>