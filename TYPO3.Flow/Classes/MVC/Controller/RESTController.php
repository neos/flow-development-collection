<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

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
 * An action controller for RESTful web services
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RESTController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * The current request
	 * @var \F3\FLOW3\MVC\Web\Request
	 */
	protected $request;

	/**
	 * The response which will be returned by this action controller
	 * @var \F3\FLOW3\MVC\Web\Response
	 */
	protected $response;

	/**
	 * Handles a web request. The result output is returned by altering the given response.
	 *
	 * Note that this REST controller only supports web requests!
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request object
	 * @param \F3\FLOW3\MVC\ResponseInterface $response The response, modified by this handler
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function processRequest(\F3\FLOW3\MVC\RequestInterface $request, \F3\FLOW3\MVC\ResponseInterface $response) {
		$this->arguments->addNewArgument('id', 'UUID', FALSE);
		parent::processRequest($request, $response);
	}

	/**
	 * Determines the action method and assures that the method exists.
	 *
	 * @return string The action method name
	 * @throws \F3\FLOW3\MVC\Exception\NoSuchAction if the action specified in the request object does not exist (and if there's no default action either).
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveActionMethodName() {
		if ($this->request->getControllerActionName() === 'index') {
			$actionName = 'index';
			switch ($this->request->getMethod()) {
				case 'GET' :
					$actionName = ($this->request->hasArgument('id')) ? 'show' : 'list';
				break;
				case 'POST' :
					$actionName = 'create';
				break;
				case 'PUT' :
					if (!$this->request->hasArgument('id')) $this->throwStatus(400, NULL, 'Missing identifier');
					$actionName = 'update';
				break;
				case 'DELETE' :
					if (!$this->request->hasArgument('id')) $this->throwStatus(400, NULL, 'Missing identifier');
					$actionName = 'delete';
				break;
			}
			$this->request->setControllerActionName($actionName);
		}
		return parent::resolveActionMethodName();
	}
}
?>