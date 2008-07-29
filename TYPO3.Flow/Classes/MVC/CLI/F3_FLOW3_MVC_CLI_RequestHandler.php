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
 * @version $Id:F3_FLOW3_MVC_CLI_RequestHandler.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * The generic command line interface request handler for the MVC framework.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_CLI_RequestHandler.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_CLI_RequestHandler implements F3_FLOW3_MVC_RequestHandlerInterface {

	/**
	 * @var F3_FLOW3_Component_FactoryInterface Reference to the component factory
	 */
	protected $componentFactory;

	/**
	 * @var F3_FLOW3_Utility_Environment Reference to the environment utility component
	 */
	protected $utilityEnvironment;

	/**
	 * @var F3_FLOW3_MVC_Dispatcher The dispatcher
	 */
	protected $dispatcher = NULL;

	/**
	 * Constructs the CLI Request Handler
	 *
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory A reference to the component factory
	 * @param F3_FLOW3_Utility_Environment $utilityEnvironment Reference to the environment utility component
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_FactoryInterface $componentFactory, F3_FLOW3_Utility_Environment $utilityEnvironment) {
		$this->componentFactory = $componentFactory;
		$this->utilityEnvironment = $utilityEnvironment;
	}

	/**
	 * Injects the dispatcher.
	 *
	 * @param F3_FLOW3_MVC_Dispatcher $dispatcher The dispatcher
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectDispatcher(F3_FLOW3_MVC_Dispatcher $dispatcher) {
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Handles the request
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function handleRequest() {
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_CLI_RequestBuilder')->build();
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_CLI_Response');
		$request->lock();

		$this->dispatcher->dispatch($request, $response);

		$response->send();
	}

	/**
	 * This request handler can handle any command line request.
	 *
	 * @return boolean If the request is a command line request, TRUE otherwise FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function canHandleRequest() {
		return ($this->utilityEnvironment->getSAPIName() == 'cli');
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