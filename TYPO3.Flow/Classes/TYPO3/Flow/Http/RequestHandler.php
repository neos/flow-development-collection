<?php
namespace TYPO3\Flow\Http;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Http\Component\ComponentContext;

/**
 * A request handler which can handle HTTP requests.
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class RequestHandler implements HttpRequestHandlerInterface {

	/**
	 * @var Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Response
	 */
	protected $response;

	/**
	 * @var Component\ComponentChain
	 */
	protected $baseComponentChain;

	/**
	 * The "http" settings
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Make exit() a closure so it can be manipulated during tests
	 *
	 * @var \Closure
	 */
	public $exit;

	/**
	 * @param Bootstrap $bootstrap
	 */
	public function __construct(Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
		$this->exit = function() { exit(); };
	}

	/**
	 * This request handler can handle any web request.
	 *
	 * @return boolean If the request is a web request, TRUE otherwise FALSE
	 * @api
	 */
	public function canHandleRequest() {
		return (PHP_SAPI !== 'cli');
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler.
	 * @api
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
		// Create the request very early so the Resource Management has a chance to grab it:
		$this->request = Request::createFromEnvironment();
		$this->response = new Response();

		$this->boot();
		$this->resolveDependencies();
		if (isset($this->settings['http']['baseUri'])) {
			$this->request->setBaseUri(new Uri($this->settings['http']['baseUri']));
		}

		$componentContext = new ComponentContext($this->request, $this->response);
		$this->baseComponentChain->handle($componentContext);

		$this->response->send();

		$this->bootstrap->shutdown(Bootstrap::RUNLEVEL_RUNTIME);
		$this->exit->__invoke();
	}

	/**
	 * Returns the currently handled HTTP request
	 *
	 * @return Request
	 * @api
	 */
	public function getHttpRequest() {
		return $this->request;
	}

	/**
	 * Returns the HTTP response corresponding to the currently handled request
	 *
	 * @return Response
	 * @api
	 */
	public function getHttpResponse() {
		return $this->response;
	}

	/**
	 * Boots up Flow to runtime
	 *
	 * @return void
	 */
	protected function boot() {
		$sequence = $this->bootstrap->buildRuntimeSequence();
		$sequence->invoke($this->bootstrap);
	}

	/**
	 * Resolves a few dependencies of this request handler which can't be resolved
	 * automatically due to the early stage of the boot process this request handler
	 * is invoked at.
	 *
	 * @return void
	 */
	protected function resolveDependencies() {
		$objectManager = $this->bootstrap->getObjectManager();
		$this->baseComponentChain = $objectManager->get('TYPO3\Flow\Http\Component\ComponentChain');

		$configurationManager = $objectManager->get('TYPO3\Flow\Configuration\ConfigurationManager');
		$this->settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');
	}
}
