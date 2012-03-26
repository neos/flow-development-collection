<?php
namespace TYPO3\FLOW3\Http;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Core\Bootstrap;
use TYPO3\FLOW3\MVC\Web\Response;

/**
 * A request handler which can handle HTTP requests.
 *
 * @FLOW3\Scope("singleton")
 */
class RequestHandler implements \TYPO3\FLOW3\Core\RequestHandlerInterface {

	/**
	 * @var \TYPO3\FLOW3\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Request
	 */
	protected $request;

	/**
	 * Make exit() a closure so it can be manipulated during tests
	 *
	 * @var Closure
	 */
	public $exit;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	public function __construct(Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
		$this->exit = function() { exit(); };
	}

	/**
	 * This request handler can handle any web request.
	 *
	 * @return boolean If the request is a web request, TRUE otherwise FALSE
	 */
	public function canHandleRequest() {
		return (PHP_SAPI !== 'cli');
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler.
	 */
	public function getPriority() {
		return 100;
	}

	/**
	 * Handles a HTTP request
	 *
	 * @return void
	 */
	public function handleRequest() {
		$sequence = $this->bootstrap->buildRuntimeSequence();
		$sequence->invoke($this->bootstrap);

		$objectManager = $this->bootstrap->getObjectManager();

		$this->request = $objectManager->get('TYPO3\FLOW3\MVC\Web\RequestBuilder')->build();
		$response = new Response();

		$dispatcher = $objectManager->get('TYPO3\FLOW3\MVC\Dispatcher');
		$dispatcher->dispatch($this->request, $response);

		$response->send();
		$this->bootstrap->shutdown('Runtime');
		$this->exit->__invoke();
	}

	/**
	 * Returns the top level request built by the request handler.
	 *
	 * In most cases the dispatcher or other parts of the request-response chain
	 * should be preferred for retrieving the current request, because sub requests
	 * or simulated requests are built later in the process.
	 *
	 * If, however, the original top level request is wanted, this is the right
	 * method for getting it.
	 *
	 * @return \TYPO3\FLOW3\MVC\RequestInterface The originally built web request
	 * @api
	 */
	public function getRequest() {
		return $this->request;
	}

}
?>