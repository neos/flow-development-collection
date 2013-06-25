<?php
namespace TYPO3\Flow\Http\Client;

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
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Mvc\Routing\Route;

/**
 * A Request Engine which uses Flow's request dispatcher directly for processing
 * HTTP requests internally.
 *
 * This engine is particularly useful in functional test scenarios.
 */
class InternalRequestEngine implements RequestEngineInterface {

	/**
	 * @Flow\Inject(lazy = false)
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @Flow\Inject(lazy = false)
	 * @var \TYPO3\Flow\Mvc\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @Flow\Inject(lazy = false)
	 * @var \TYPO3\Flow\Mvc\Routing\Router
	 */
	protected $router;

	/**
	 * @Flow\Inject(lazy = false)
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Validation\ValidatorResolver
	 */
	protected $validatorResolver;

	/**
	 * Intialize this engine
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->router->setRoutesConfiguration($this->configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES));
	}

	/**
	 * Sends the given HTTP request
	 *
	 * @param \TYPO3\Flow\Http\Request $request
	 * @return \TYPO3\Flow\Http\Response
	 * @throws \TYPO3\Flow\Http\Exception
	 * @api
	 */
	public function sendRequest(Request $request) {
		$requestHandler = $this->bootstrap->getActiveRequestHandler();
		if (!$requestHandler instanceof \TYPO3\Flow\Tests\FunctionalTestRequestHandler) {
			throw new \TYPO3\Flow\Http\Exception('The browser\'s internal request engine has only been designed for use within functional tests.', 1335523749);
		}

		$response = new Response();
		$requestHandler->setHttpRequest($request);
		$requestHandler->setHttpResponse($response);

		try {
			$actionRequest = $this->router->route($request);
			$this->securityContext->clearContext();
			$this->securityContext->setRequest($actionRequest);
			$this->validatorResolver->reset();

			$this->dispatcher->dispatch($actionRequest, $response);

			$session = $this->bootstrap->getObjectManager()->get('TYPO3\Flow\Session\SessionInterface');
			if ($session->isStarted()) {
				$session->close();
			}
		} catch (\Exception $exception) {
			$pathPosition = strpos($exception->getFile(), 'Packages/');
			$filePathAndName = ($pathPosition !== FALSE) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();
			$exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';
			$content = PHP_EOL . 'Uncaught Exception in Flow ' . $exceptionCodeNumber . $exception->getMessage() . PHP_EOL;
			$content .= 'thrown in file ' . $filePathAndName . PHP_EOL;
			$content .= 'in line ' . $exception->getLine() . PHP_EOL . PHP_EOL;
			$content .= \TYPO3\Flow\Error\Debugger::getBacktraceCode($exception->getTrace(), FALSE, TRUE) . PHP_EOL;

			if ($exception instanceof \TYPO3\Flow\Exception) {
				$statusCode = $exception->getStatusCode();
			} else {
				$statusCode = 500;
			}
			$response->setStatus($statusCode);
			$response->setContent($content);
			$response->setHeader('X-Flow-ExceptionCode', $exception->getCode());
			$response->setHeader('X-Flow-ExceptionMessage', $exception->getMessage());
		}
		return $response;
	}

	/**
	 * Returns the router used by this internal request engine
	 *
	 * @return \TYPO3\Flow\Mvc\Routing\Router
	 */
	public function getRouter() {
		return $this->router;
	}


}

?>