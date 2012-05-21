<?php
namespace TYPO3\FLOW3\Http\Client;

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
use TYPO3\FLOW3\Http\Request;
use TYPO3\FLOW3\Http\Response;
use TYPO3\FLOW3\Mvc\Routing\Route;

/**
 * A Request Engine which uses FLOW3's request dispatcher directly for processing
 * HTTP requests internally.
 *
 * This engine is particularly useful in functional test scenarios.
 *
 * @FLOW3\Scope("singleton")
 */
class InternalRequestEngine implements RequestEngineInterface {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Mvc\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Mvc\Routing\Router
	 */
	protected $router;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * Intialize this engine
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->router->setRoutesConfiguration($this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES));
	}

	/**
	 * Sends the given HTTP request
	 *
	 * @param \TYPO3\FLOW3\Http\Request $request
	 * @return \TYPO3\FLOW3\Http\Response
	 * @api
	 */
	public function sendRequest(Request $request) {
		$requestHandler = $this->bootstrap->getActiveRequestHandler();
		if (!$requestHandler instanceof \TYPO3\FLOW3\Tests\FunctionalTestRequestHandler) {
			throw new \TYPO3\FLOW3\Http\Exception('The browser\'s internal request engine has only been designed for use within functional tests.', 1335523749);
		}

		$response = new Response();
		$requestHandler->setHttpRequest($request);
		$requestHandler->setHttpResponse($response);

		try {
			$actionRequest = $this->router->route($request);
			$this->securityContext->clearContext();
			$this->securityContext->injectRequest($actionRequest);

			$this->dispatcher->dispatch($actionRequest, $response);
		} catch (\Exception $exception) {
			$pathPosition = strpos($exception->getFile(), 'Packages/');
			$filePathAndName = ($pathPosition !== FALSE) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();
			$exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';
			$content = PHP_EOL . 'Uncaught Exception in FLOW3 ' . $exceptionCodeNumber . $exception->getMessage() . PHP_EOL;
			$content .= 'thrown in file ' . $filePathAndName . PHP_EOL;
			$content .= 'in line ' . $exception->getLine() . PHP_EOL . PHP_EOL;
			$content .= \TYPO3\FLOW3\Error\Debugger::getBacktraceCode($exception->getTrace(), FALSE, TRUE) . PHP_EOL;

			$response->setStatus(500);
			$response->setContent($content);
			$response->setHeader('X-FLOW3-ExceptionCode', $exceptionCodeNumber);
			$response->setHeader('X-FLOW3-ExceptionMessage', $exception->getMessage());
		}
		return $response;
	}

	/**
	 * Returns the router used by this request engine
	 *
	 * @return \TYPO3\FLOW3\Mvc\Routing\Router
	 */
	public function getRouter() {
		return $this->router;
	}


}

?>