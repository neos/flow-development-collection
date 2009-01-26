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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Dispatcher {

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface A reference to the object manager
	 */
	protected $objectManager;

	/**
	 * Constructs the global dispatcher
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager A reference to the object manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Dispatches a request to a controller and initializes the security framework.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request to dispatch
	 * @param \F3\FLOW3\MVC\ResponseInterface $response The response, to be modified by the controller
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatch(\F3\FLOW3\MVC\Request $request, \F3\FLOW3\MVC\Response $response) {
		$dispatchLoopCount = 0;
		while (!$request->isDispatched()) {
			$dispatchLoopCount ++;
			if ($dispatchLoopCount > 99) throw new \F3\FLOW3\MVC\Exception\InfiniteLoop('Could not ultimately dispatch the request after '  . $dispatchLoopCount . ' iterations.', 1217839467);
			try {
				$controller = $this->getPreparedController($request, $response);
				$controller->processRequest($request, $response);
			} catch (\F3\FLOW3\MVC\Exception\StopAction $ignoredException) {
			}
		}
	}

	/**
	 * Resolves, prepares and returns the controller which is specified in the request object.
	 *
	 * @param \F3\FLOW3\MVC\Request $request The current request
	 * @param \F3\FLOW3\MVC\Response $response The current response
	 * @return \F3\FLOW3\MVC\Controller\RequestHandlingController The controller
	 * @throws \F3\FLOW3\MVC\Exception\NoSuchController, \F3\FLOW3\MVC\Exception\InvalidController
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getPreparedController(\F3\FLOW3\MVC\Request $request, \F3\FLOW3\MVC\Response $response) {
		$controllerObjectName = $request->getControllerObjectName();
		$controller = $this->objectManager->getObject($controllerObjectName);
		if (!$controller instanceof \F3\FLOW3\MVC\Controller\RequestHandlingController) throw new \F3\FLOW3\MVC\Exception\InvalidController('Invalid controller "' . $controllerObjectName . '". The controller must be a valid request handling controller.', 1202921619);
		return $controller;
	}

}
?>