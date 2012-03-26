<?php
namespace TYPO3\FLOW3\Mvc;

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
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 *
 * @FLOW3\Scope("singleton")
 */
class Dispatcher {

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\FLOW3\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\FLOW3\Package\PackageManagerInterface $packageManager A reference to the package manager
	 * @return void
	 */
	public function injectPackageManager(\TYPO3\FLOW3\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * @param \TYPO3\FLOW3\SignalSlot\Dispatcher $signalSlotDispatcher
	 * @return void
	 */
	public function injectSignalSlotDispatcher(\TYPO3\FLOW3\SignalSlot\Dispatcher $signalSlotDispatcher) {
		$this->signalSlotDispatcher = $signalSlotDispatcher;
	}

	/**
	 * Injects the FLOW3 settings
	 *
	 * @param array $settings The FLOW3 settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Dispatches a request to a controller and initializes the security framework.
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The request to dispatch
	 * @param \TYPO3\FLOW3\MVC\ResponseInterface $response The response, to be modified by the controller
	 * @return void
	 */
	public function dispatch(\TYPO3\FLOW3\MVC\RequestInterface $request, \TYPO3\FLOW3\MVC\ResponseInterface $response) {
		$dispatchLoopCount = 0;
		while (!$request->isDispatched()) {
			if ($dispatchLoopCount++ > 99) {
				throw new \TYPO3\FLOW3\MVC\Exception\InfiniteLoopException('Could not ultimately dispatch the request after '  . $dispatchLoopCount . ' iterations.', 1217839467);
			}
			$controller = $this->resolveController($request);
			try {
				$this->emitBeforeControllerInvocation($request, $controller);
				$controller->processRequest($request, $response);
				$this->emitAfterControllerInvocation($controller);
			} catch (\TYPO3\FLOW3\MVC\Exception\StopActionException $stopActionException) {
				$this->emitAfterControllerInvocation($controller);
				if ($request instanceof \TYPO3\FLOW3\MVC\Web\SubRequest && $request->isDispatched()) {
					throw $stopActionException;
				}
			}
		}
	}

	/**
	 * This signal is emitted directly before the request is been dispatched to a controller.
	 * It is mainly useful for collecting performance metrics.
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request
	 * @param \TYPO3\FLOW3\MVC\Controller\ControllerInterface $controller
	 * @return void
	 * @FLOW3\Signal
	 */
	protected function emitBeforeControllerInvocation(\TYPO3\FLOW3\MVC\RequestInterface $request, \TYPO3\FLOW3\MVC\Controller\ControllerInterface $controller) {
	}

	/**
	 * This signal is emitted directly after the request has been dispatched to a controller and the controller
	 * returned control back to the dispatcher.
	 *
	 * @param \TYPO3\FLOW3\MVC\Controller\ControllerInterface $controller
	 * @return void
	 * @FLOW3\Signal
	 */
	protected function emitAfterControllerInvocation(\TYPO3\FLOW3\MVC\Controller\ControllerInterface $controller) {
	}

	/**
	 * Finds and instantiates a controller that matches the current request.
	 * If no controller can be found, an instance of NotFoundControllerInterface is returned.
	 *
	 * @param \TYPO3\FLOW3\Mvc\RequestInterface $request The request to dispatch
	 * @return \TYPO3\FLOW3\Mvc\Controller\ControllerInterface
	 */
	protected function resolveController(\TYPO3\FLOW3\Mvc\RequestInterface $request) {
		$exception = NULL;
		$controllerObjectName = $request->getControllerObjectName();
		if ($controllerObjectName === '') {
			$exception = new \TYPO3\FLOW3\Mvc\Controller\Exception\InvalidControllerException('No controller could be resolved which would match your request', 1303209195, NULL, $request);
		}

		if ($exception !== NULL) {
			$controller = $this->objectManager->get($this->settings['mvc']['notFoundController']);
			if (!$controller instanceof \TYPO3\FLOW3\Mvc\Controller\NotFoundControllerInterface) {
				throw new \TYPO3\FLOW3\Mvc\Controller\Exception\InvalidControllerException('The NotFoundController must implement "\TYPO3\FLOW3\Mvc\Controller\NotFoundControllerInterface", ' . (is_object($controller) ? get_class($controller) : gettype($controller)) . ' given.', 1246714416, NULL, $request);
			}
			$controller->setException($exception);
		} else {
			$controller = $this->objectManager->get($controllerObjectName);
			if (!$controller instanceof \TYPO3\FLOW3\Mvc\Controller\ControllerInterface) {
				throw new \TYPO3\FLOW3\Mvc\Controller\Exception\InvalidControllerException('Invalid controller "' . $request->getControllerObjectName() . '". The controller must be a valid request handling controller, ' . (is_object($controller) ? get_class($controller) : gettype($controller)) . ' given.', 1202921619, NULL, $request);
			}
		}
		return $controller;
	}

}
?>