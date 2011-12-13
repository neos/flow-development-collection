<?php
namespace TYPO3\FLOW3\Tests\Functional;

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

/**
 * A request handler which boots up FLOW3 into a basic runtime level and then returns
 * without actually further handling command line commands.
 *
 * @FLOW3\Proxy(false)
 * @FLOW3\Scope("singleton")
 */
class FunctionalTestRequestHandler implements \TYPO3\FLOW3\Core\RequestHandlerInterface {

	/**
	 * @var \TYPO3\FLOW3\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var \TYPO3\FLOW3\Cli\Request
	 */
	protected $request;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	public function __construct(Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * This request handler can handle CLI requests.
	 *
	 * @return boolean If the request is a CLI request, TRUE otherwise FALSE
	 */
	public function canHandleRequest() {
		return (PHP_SAPI === 'cli' && $this->bootstrap->getContext() === 'Testing');
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * As this request handler can only be used as a preselected request handler,
	 * the priority for all other cases is 0.
	 *
	 * @return integer The priority of the request handler.
	 */
	public function getPriority() {
		return 0;
	}

	/**
	 * Handles a command line request
	 *
	 * @return void
	 */
	public function handleRequest() {
		$sequence = $this->bootstrap->buildRuntimeSequence();
		$sequence->invoke($this->bootstrap);
	}

	/**
	 * Returns the request which has previously been set with setRequest()
	 *
	 * @return \TYPO3\FLOW3\MVC\RequestInterface The originally built web request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Sets the request – used by the base functional test case
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request
	 * @return void
	 */
	public function setRequest(\TYPO3\FLOW3\MVC\RequestInterface $request) {
		$this->request = $request;
	}
}

?>