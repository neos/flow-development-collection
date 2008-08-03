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
 * @version $Id:F3_FLOW3_MVC_Web_RequestHandler.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * A request handler which can handle web requests.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Web_RequestHandler.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Web_RequestHandler implements F3_FLOW3_MVC_RequestHandlerInterface {

	/**
	 * @var F3_FLOW3_Component_FactoryInterface Reference to the component factory
	 */
	protected $componentFactory;

	/**
	 * @var F3_FLOW3_Utility_Environment Reference to the environment utility component
	 */
	protected $utilityEnvironment;

	/**
	 * @var F3_FLOW3_MVC_Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var F3_FLOW3_MVC_RequestProcessorChainManager
	 */
	protected $requestProcessorChainManager;

	/**
	 * Constructs the Web Request Handler
	 *
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory A reference to the component factory
	 * @param F3_FLOW3_Utility_Environment $utilityEnvironment A reference to the environment
	 * @param F3_FLOW3_MVC_Dispatcher $dispatcher The request dispatcher
	 * @param F3_FLOW3_MVC_RequestProcessorChainManager A reference to the request processor chain manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(
			F3_FLOW3_Component_FactoryInterface $componentFactory,
			F3_FLOW3_Utility_Environment $utilityEnvironment,
			F3_FLOW3_MVC_Dispatcher $dispatcher,
			F3_FLOW3_MVC_RequestProcessorChainManager $requestProcessorChainManager) {
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
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_RequestBuilder')->build();
		$this->requestProcessorChainManager->processRequest($request);
		$request->lock();
			# TODO intercepting filter chain should be invoked here.
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');
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
			case F3_FLOW3_Utility_Environment::REQUEST_METHOD_GET :
			case F3_FLOW3_Utility_Environment::REQUEST_METHOD_POST :
			case F3_FLOW3_Utility_Environment::REQUEST_METHOD_PUT :
			case F3_FLOW3_Utility_Environment::REQUEST_METHOD_DELETE :
			case F3_FLOW3_Utility_Environment::REQUEST_METHOD_OPTIONS :
			case F3_FLOW3_Utility_Environment::REQUEST_METHOD_HEAD :
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