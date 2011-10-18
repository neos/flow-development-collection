<?php
namespace TYPO3\FLOW3\MVC;

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

/**
 * Analyzes the raw request and delivers a request handler which can handle it.
 *
 * @FLOW3\Scope("singleton")
 */
class RequestHandlerResolver {

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\FLOW3\MVC\RequestHandlerInterface
	 */
	protected $preselectedRequestHandler;

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param \TYPO3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Sets a specific request handler as the one which is returned by resolveRequestHandler()
	 *
	 * This resolver won't test if the given request handler is capable of handling the
	 * current request. The purpose is for functional tests to inject a mock request handler
	 * which simulates different kinds of requests.
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestHandlerInterface $requestHandler
	 * @return void
	 */
	public function setPreselectedRequestHandler(\TYPO3\FLOW3\MVC\RequestHandlerInterface $requestHandler) {
		$this->preselectedRequestHandler = $requestHandler;
	}

	/**
	 * Analyzes the raw request and tries to find a request handler which can handle
	 * it. If none is found, an exception is thrown.
	 *
	 * @return \TYPO3\FLOW3\MVC\RequestHandler A request handler
	 * @throws \TYPO3\FLOW3\MVC\Exception
	 */
	public function resolveRequestHandler() {
		if (isset($this->preselectedRequestHandler)) {
			return $this->preselectedRequestHandler;
		}

		$availableRequestHandlerClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface('TYPO3\FLOW3\MVC\RequestHandlerInterface');

		$suitableRequestHandlers = array();
		foreach ($availableRequestHandlerClassNames as $requestHandlerClassName) {
			if (!$this->objectManager->isRegistered($requestHandlerClassName)) {
				continue;
			}

			$requestHandler = $this->objectManager->get($requestHandlerClassName);
			if ($requestHandler->canHandleRequest() > 0) {
				$priority = $requestHandler->getPriority();
				if (isset($suitableRequestHandlers[$priority])) {
					throw new \TYPO3\FLOW3\MVC\Exception('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176475350);
				}
				$suitableRequestHandlers[$priority] = $requestHandler;
			}
		}
		if (count($suitableRequestHandlers) === 0) throw new \TYPO3\FLOW3\MVC\Exception('No suitable request handler found.', 1205414233);
		ksort($suitableRequestHandlers);
		return array_pop($suitableRequestHandlers);
	}
}

?>