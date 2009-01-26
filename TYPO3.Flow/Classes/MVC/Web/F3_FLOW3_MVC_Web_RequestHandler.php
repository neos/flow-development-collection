<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web;

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
 * A request handler which can handle web requests.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandler implements \F3\FLOW3\MVC\RequestHandlerInterface {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $utilityEnvironment;

	/**
	 * @var \F3\FLOW3\MVC\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var \F3\FLOW3\MVC\Web\RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var \F3\FLOW3\MVC\RequestProcessorChainManager
	 */
	protected $requestProcessorChainManager;

	/**
	 * Constructs the Web Request Handler
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory A reference to the object factory
	 * @param \F3\FLOW3\Utility\Environment $utilityEnvironment A reference to the environment
	 * @param \F3\FLOW3\MVC\Dispatcher $dispatcher The request dispatcher
	 * @param \F3\FLOW3\MVC\Web\RequestBuilder $requestBuilder The request builder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(
			\F3\FLOW3\Object\FactoryInterface $objectFactory,
			\F3\FLOW3\Utility\Environment $utilityEnvironment,
			\F3\FLOW3\MVC\Dispatcher $dispatcher,
			\F3\FLOW3\MVC\Web\RequestBuilder $requestBuilder) {
		$this->objectFactory = $objectFactory;
		$this->utilityEnvironment = $utilityEnvironment;
		$this->dispatcher = $dispatcher;
		$this->requestBuilder = $requestBuilder;
	}

	/**
	 * Handles the web request. The response will automatically be sent to the client.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleRequest() {
		$request = $this->requestBuilder->build();
		$response = $this->objectFactory->create('F3\FLOW3\MVC\Web\Response');
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
			case \F3\FLOW3\Utility\Environment::REQUEST_METHOD_GET :
			case \F3\FLOW3\Utility\Environment::REQUEST_METHOD_POST :
			case \F3\FLOW3\Utility\Environment::REQUEST_METHOD_PUT :
			case \F3\FLOW3\Utility\Environment::REQUEST_METHOD_DELETE :
			case \F3\FLOW3\Utility\Environment::REQUEST_METHOD_OPTIONS :
			case \F3\FLOW3\Utility\Environment::REQUEST_METHOD_HEAD :
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