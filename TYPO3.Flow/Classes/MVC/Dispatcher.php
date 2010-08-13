<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Dispatcher {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface A reference to the object manager
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Constructs the global dispatcher
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the package manager
	 *
	 * @param \F3\FLOW3\Package\PackageManagerInterface $packageManager A reference to the package manager
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function injectPackageManager(\F3\FLOW3\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * Injects the FLOW3 settings
	 *
	 * @param array $settings The FLOW3 settings
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Dispatches a request to a controller and initializes the security framework.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request to dispatch
	 * @param \F3\FLOW3\MVC\ResponseInterface $response The response, to be modified by the controller
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dispatch(\F3\FLOW3\MVC\RequestInterface $request, \F3\FLOW3\MVC\ResponseInterface $response) {
		$dispatchLoopCount = 0;
		while (!$request->isDispatched()) {
			if ($dispatchLoopCount++ > 99) throw new \F3\FLOW3\MVC\Exception\InfiniteLoopException('Could not ultimately dispatch the request after '  . $dispatchLoopCount . ' iterations.', 1217839467);
			$controller = $this->resolveController($request);
			try {
				$controller->processRequest($request, $response);
			} catch (\F3\FLOW3\MVC\Exception\StopActionException $ignoredException) {
			}
		}
	}

	/**
	 * Finds and instanciates a controller that matches the current request.
	 * If no controller can be found, an instance of NotFoundControllerInterface is returned.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request to dispatch
	 * @return \F3\FLOW3\MVC\Controller\ControllerInterface
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function resolveController(\F3\FLOW3\MVC\RequestInterface $request) {
		$exception = NULL;
		$packageKey = $request->getControllerPackageKey();
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			$exception = new \F3\FLOW3\MVC\Controller\Exception\InvalidPackageException($request, 'package "' . $packageKey . '" does not exist');
		} elseif (!$this->packageManager->isPackageActive($packageKey)) {
			$exception = new \F3\FLOW3\MVC\Controller\Exception\InactivePackageException($request, 'package "' . $packageKey . '" is not active');
		} else {
			$controllerObjectName = $request->getControllerObjectName();
			if ($controllerObjectName === '') {
				$exception = new \F3\FLOW3\MVC\Controller\Exception\InvalidControllerException($request, 'no controller could be resolved which would match your request');
			}
		}
		if ($exception !== NULL) {
			$controller = $this->objectManager->get($this->settings['mvc']['notFoundController']);
			if (!$controller instanceof \F3\FLOW3\MVC\Controller\NotFoundControllerInterface) throw new \F3\FLOW3\MVC\Exception\InvalidControllerException('The NotFoundController must implement "\F3\FLOW3\MVC\Controller\NotFoundControllerInterface", ' . (is_object($controller) ? get_class($controller) : gettype($controller)) . ' given.', 1246714416);
			$controller->setException($exception);
		} else {
			$controller = $this->objectManager->get($controllerObjectName);
			if (!$controller instanceof \F3\FLOW3\MVC\Controller\ControllerInterface) throw new \F3\FLOW3\MVC\Exception\InvalidControllerException('Invalid controller "' . $request->getControllerObjectName() . '". The controller must be a valid request handling controller, ' . (is_object($controller) ? get_class($controller) : gettype($controller)) . ' given.', 1202921619);
		}
		return $controller;
	}

}
?>