<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\CLI;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * The generic command line interface request handler for the MVC framework.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandler implements \F3\FLOW3\MVC\RequestHandlerInterface {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface Reference to the object factory
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Utility\Environment Reference to the environment utility object
	 */
	protected $utilityEnvironment;

	/**
	 * @var \F3\FLOW3\MVC\Dispatcher The dispatcher
	 */
	protected $dispatcher = NULL;

	/**
	 * @var \F3\FLOW3\MVC\CLI\RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var \F3\FLOW3\MVC\RequestProcessorChainManager
	 */
	protected $requestProcessorChainManager;

	/**
	 * Constructs the CLI Request Handler
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory A reference to the object factory
	 * @param \F3\FLOW3\Utility\Environment $utilityEnvironment A reference to the environment
	 * @param \F3\FLOW3\MVC\Dispatcher $dispatcher The request dispatcher
	 * @param \F3\FLOW3\MVC\CLI\RequestBuilder $requestBuilder The request builder
	 * @param \F3\FLOW3\MVC\RequestProcessorChainManager A reference to the request processor chain manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(
			\F3\FLOW3\Object\FactoryInterface $objectFactory,
			\F3\FLOW3\Utility\Environment $utilityEnvironment,
			\F3\FLOW3\MVC\Dispatcher $dispatcher,
			\F3\FLOW3\MVC\CLI\RequestBuilder $requestBuilder,
			\F3\FLOW3\MVC\RequestProcessorChainManager $requestProcessorChainManager) {
		$this->objectFactory = $objectFactory;
		$this->utilityEnvironment = $utilityEnvironment;
		$this->dispatcher = $dispatcher;
		$this->requestBuilder = $requestBuilder;
		$this->requestProcessorChainManager = $requestProcessorChainManager;
	}

	/**
	 * Handles the request
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleRequest() {
		$request = $this->requestBuilder->build();
		$this->requestProcessorChainManager->processRequest($request);
		$response = $this->objectFactory->create('F3\FLOW3\MVC\CLI\Response');
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