<?php
namespace TYPO3\FLOW3\MVC\Web;

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
 * A request handler which can handle web requests.
 *
 * @scope singleton
 */
class RequestHandler implements \TYPO3\FLOW3\MVC\RequestHandlerInterface {

	/**
	 * @var \TYPO3\FLOW3\MVC\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * Constructs the Web Request Handler
	 *
	 * @param \TYPO3\FLOW3\MVC\Dispatcher $dispatcher The request dispatcher
	 * @param \TYPO3\FLOW3\MVC\Web\RequestBuilder $requestBuilder The request builder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(
			\TYPO3\FLOW3\MVC\Dispatcher $dispatcher,
			\TYPO3\FLOW3\MVC\Web\RequestBuilder $requestBuilder) {
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
		$response = new Response();

		switch ($request->getFormat()) {
			case 'rss.xml' :
			case 'rss' :
				$response->setHeader('Content-Type', 'application/rss+xml');
				break;
			case 'atom.xml' :
			case 'atom' :
				$response->setHeader('Content-Type', 'application/atom+xml');
				break;
		}

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
		return (FLOW3_SAPITYPE === 'Web');
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