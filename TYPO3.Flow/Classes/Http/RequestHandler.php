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
use TYPO3\FLOW3\Core\RequestHandlerInterface;
use TYPO3\FLOW3\Configuration\ConfigurationManager;
use TYPO3\FLOW3\Security\Exception\AccessDeniedException;

/**
 * A request handler which can handle HTTP requests.
 *
 * @FLOW3\Scope("singleton")
 * @FLOW3\Proxy("disable")
 */
class RequestHandler implements HttpRequestHandlerInterface {

	/**
	 * @var \TYPO3\FLOW3\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var array
	 */
	protected $routesConfiguration;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Routing\Router
	 */
	protected $router;

	/**
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\FLOW3\Http\Request
	 */
	protected $request;


	/**
	 * The "http" settings
	 *
	 * @var array
	 */
	protected $settings;

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
			// Create the request very early so the Resource Management has a chance to grab it:
		$this->request = Request::createFromEnvironment();

		$this->boot();
		$this->resolveDependencies();
		$this->request->injectSettings($this->settings);

		$response = new Response();

		$this->router->setRoutesConfiguration($this->routesConfiguration);
		$actionRequest = $this->router->route($this->request);

		$this->securityContext->injectRequest($actionRequest);

		$this->dispatcher->dispatch($actionRequest, $response);

		$response->send();

		$this->bootstrap->shutdown('Runtime');
		$this->exit->__invoke();
	}

	/**
	 * Returns the currently handled HTTP request
	 *
	 * @return \TYPO3\FLOW3\Http\Request
	 */
	public function getHttpRequest() {
		return $this->request;
	}

	/**
	 * Boots up FLOW3 to runtime
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
		$this->dispatcher = $objectManager->get('TYPO3\FLOW3\Mvc\Dispatcher');

		$configurationManager = $objectManager->get('TYPO3\FLOW3\Configuration\ConfigurationManager');
		$this->settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.FLOW3');

		$this->routesConfiguration = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
		$this->router = $objectManager->get('TYPO3\FLOW3\Mvc\Routing\Router');

		$this->securityContext = $objectManager->get('TYPO3\FLOW3\Security\Context');
	}
}
?>
