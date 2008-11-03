<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::Web;

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
 * @version $Id:F3::FLOW3::MVC::Web::RequestBuilder.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Builds a web request object from the raw HTTP information
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3::FLOW3::MVC::Web::RequestBuilder.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RequestBuilder {

	/**
	 * @var F3::FLOW3::Component::FactoryInterface $componentFactory: A reference to the Component Factory
	 */
	protected $componentFactory;

	/**
	 * @var F3::FLOW3::Utility::Environment
	 */
	protected $environment;

	/**
	 * @var F3::FLOW3::Configuration::Manager
	 */
	protected $configurationManager;

	/**
	 * @var F3::FLOW3::MVC::Web::RouterInterface
	 */
	protected $router;

	/**
	 * Constructs this Web Request Builder
	 *
	 * @param F3::FLOW3::Component::FactoryInterface $componentFactory A reference to the component factory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3::FLOW3::Component::FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Injects the server environment
	 *
	 * @param F3::FLOW3::Utility::Environment $environment The environment
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(F3::FLOW3::Utility::Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param F3::FLOW3::Configuration::Manager $configurationManager A reference to the configuration manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(F3::FLOW3::Configuration::Manager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Injects a router for routing the web request
	 *
	 * @param F3::FLOW3::MVC::Web::Routing::RouterInterface $router A router which routes the web request to a controller and action
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectRouter(F3::FLOW3::MVC::Web::Routing::RouterInterface $router) {
		$this->router = $router;
	}

	/**
	 * Builds a web request object from the raw HTTP information
	 *
	 * @return F3::FLOW3::MVC::Web::Request The web request as an object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build() {
		$request = $this->componentFactory->create('F3::FLOW3::MVC::Web::Request');
		$request->injectEnvironment($this->environment);
		$request->setRequestURI($this->environment->getRequestURI());
		$request->setMethod($this->environment->getRequestMethod());

		$routesConfiguration = $this->configurationManager->getSpecialConfiguration(F3::FLOW3::Configuration::Manager::CONFIGURATION_TYPE_ROUTES);
		$this->router->setRoutesConfiguration($routesConfiguration);
		$this->router->route($request);

		return $request;
	}
}
?>