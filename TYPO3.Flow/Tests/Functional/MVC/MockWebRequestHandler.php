<?php
namespace TYPO3\FLOW3\Tests\Functional\MVC;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A mock web request handler suitable for functional tests
 *
 */
class MockWebRequestHandler extends \TYPO3\FLOW3\MVC\Web\RequestHandler {

	/**
	 * Explicitly sets the request
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request
	 * @return void
	 */
	public function setRequest(\TYPO3\FLOW3\MVC\RequestInterface $request) {
		$this->request = $request;
	}

	/**
	 * Handles the web request.
	 *
	 * @return void
	 */
	public function handleRequest() {
		$response = new Response();

		switch ($this->request->getFormat()) {
			case 'rss.xml' :
			case 'rss' :
				$response->setHeader('Content-Type', 'application/rss+xml');
				break;
			case 'atom.xml' :
			case 'atom' :
				$response->setHeader('Content-Type', 'application/atom+xml');
				break;
		}

		$this->dispatcher->dispatch($this->request, $response);
		$response->send();
	}

	/**
	 * Checks if the request handler can handle the current request.
	 *
	 * @return mixed TRUE or an integer > 0 if it can handle the request, otherwise FALSE or an integer < 0
	 */
	public function canHandleRequest() {
		return TRUE;
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request. An integer > 0 means "I want to handle this request" where
	 * "100" is default. "0" means "I am a fallback solution".
	 *
	 * @return integer The priority of the request handler
	 */
	public function getPriority() {
		return 200;
	}

}

?>