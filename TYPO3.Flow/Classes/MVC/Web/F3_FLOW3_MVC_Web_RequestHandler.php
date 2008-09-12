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
 * @version $Id:F3::FLOW3::MVC::Web::RequestHandler.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * A request handler which can handle web requests.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3::FLOW3::MVC::Web::RequestHandler.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RequestHandler implements F3::FLOW3::MVC::RequestHandlerInterface {

	/**
	 * @var F3::FLOW3::Component::FactoryInterface Reference to the component factory
	 */
	protected $componentFactory;

	/**
	 * @var F3::FLOW3::Utility::Environment Reference to the environment utility component
	 */
	protected $utilityEnvironment;

	/**
	 * @var F3::FLOW3::MVC::Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var F3::FLOW3::MVC::RequestProcessorChainManager
	 */
	protected $requestProcessorChainManager;

	/**
	 * Constructs the Web Request Handler
	 *
	 * @param F3::FLOW3::Component::FactoryInterface $componentFactory A reference to the component factory
	 * @param F3::FLOW3::Utility::Environment $utilityEnvironment A reference to the environment
	 * @param F3::FLOW3::MVC::Dispatcher $dispatcher The request dispatcher
	 * @param F3::FLOW3::MVC::RequestProcessorChainManager A reference to the request processor chain manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(
			F3::FLOW3::Component::FactoryInterface $componentFactory,
			F3::FLOW3::Utility::Environment $utilityEnvironment,
			F3::FLOW3::MVC::Dispatcher $dispatcher,
			F3::FLOW3::MVC::RequestProcessorChainManager $requestProcessorChainManager) {
		$this->componentFactory = $componentFactory;
		$this->utilityEnvironment = $utilityEnvironment;
		$this->dispatcher = $dispatcher;
		$this->requestProcessorChainManager = $requestProcessorChainManager;
	}

	/**
	 * Handles the web request. The response will automatically be sent to the client.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleRequest() {
		$request = $this->componentFactory->getComponent('F3::FLOW3::MVC::Web::RequestBuilder')->build();
		$this->requestProcessorChainManager->processRequest($request);
		$response = $this->componentFactory->getComponent('F3::FLOW3::MVC::Web::Response');
		$this->dispatcher->dispatch($request, $response);
		$response->send();
	}

	/**
	 * This request handler can handle any web request.
	 *
	 * @return boolean If the request is a web request, TRUE otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canHandleRequest() {
		switch ($this->utilityEnvironment->getRequestMethod()) {
			case F3::FLOW3::Utility::Environment::REQUEST_METHOD_GET :
			case F3::FLOW3::Utility::Environment::REQUEST_METHOD_POST :
			case F3::FLOW3::Utility::Environment::REQUEST_METHOD_PUT :
			case F3::FLOW3::Utility::Environment::REQUEST_METHOD_DELETE :
			case F3::FLOW3::Utility::Environment::REQUEST_METHOD_OPTIONS :
			case F3::FLOW3::Utility::Environment::REQUEST_METHOD_HEAD :
				return TRUE;
		}
		return FALSE;
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPriority() {
		return 100;
	}
}
?>