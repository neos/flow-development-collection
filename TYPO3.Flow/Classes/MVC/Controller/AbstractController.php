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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * An abstract base class for Controllers
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractController implements \F3\FLOW3\MVC\Controller\ControllerInterface {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * Key of the package this controller belongs to
	 * @var string
	 */
	protected $packageKey;

	/**
	 * Contains the settings of the current package
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \F3\FLOW3\Property\Mapper
	 */
	protected $propertyMapper;

	/**
	 * The current request
	 * @var \F3\FLOW3\MVC\Request
	 */
	protected $request;

	/**
	 * The response which will be returned by this action controller
	 * @var \F3\FLOW3\MVC\Response
	 */
	protected $response;

	/**
	 * Arguments passed to the controller
	 * @var \F3\FLOW3\MVC\Controller\Arguments
	 */
	protected $arguments;

	/**
	 * The results of the mapping of request arguments to controller arguments
	 * @var \F3\FLOW3\Property\MappingResults
	 */
	protected $argumentsMappingResults;

	/**
	 * An array of supported request types. By default only web requests are supported.
	 * Modify or replace this array if your specific controller supports certain
	 * (additional) request types.
	 * @var array
	 */
	protected $supportedRequestTypes = array('F3\FLOW3\MVC\Web\Request');

	/**
	 * Constructs the controller.
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory A reference to the Object Factory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->arguments = $objectFactory->create('F3\FLOW3\MVC\Controller\Arguments');
		$this->objectFactory = $objectFactory;
		list(, $this->packageKey) = explode('\\', get_class($this));
	}

	/**
	 * Injects the settings of the package this controller belongs to.
	 *
	 * @param array $settings Settings container of the current package
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Injects the property mapper
	 *
	 * @param \F3\FLOW3\Property\Mapper $propertyMapper The property mapper
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPropertyMapper(\F3\FLOW3\Property\Mapper $propertyMapper) {
		$this->propertyMapper = $propertyMapper;
	}

	/**
	 * Checks if the current request type is supported by the controller.
	 *
	 * If your controller only supports certain request types, either
	 * replace / modify the supporteRequestTypes property or override this
	 * method.
	 *
	 * @param \F3\FLOW3\MVC\Request $request The current request
	 * @return boolean TRUE if this request type is supported, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canProcessRequest(\F3\FLOW3\MVC\Request $request) {
		foreach ($this->supportedRequestTypes as $supportedRequestType) {
			if ($request instanceof $supportedRequestType) return TRUE;
		}
		return FALSE;
	}

	/**
	 * Processes a general request. The result can be returned by altering the given response.
	 *
	 * @param \F3\FLOW3\MVC\Request $request The request object
	 * @param \F3\FLOW3\MVC\Response $response The response, modified by this handler
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\UnsupportedRequestType if the controller doesn't support the current request type
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(\F3\FLOW3\MVC\Request $request, \F3\FLOW3\MVC\Response $response) {
		if (!$this->canProcessRequest($request)) throw new \F3\FLOW3\MVC\Exception\UnsupportedRequestType(get_class($this) . ' does not support requests of type "' . get_class($request) . '". Supported types are: ' . implode(' ', $this->supportedRequestTypes) , 1187701131);

		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->initializeArguments();
		$this->mapRequestArgumentsToControllerArguments();
	}

	/**
	 * Initializes (registers / defines) arguments of this controller.
	 *
	 * Override this method to add arguments which can later be accessed
	 * by the action methods.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeArguments() {
	}

	/**
	 * Forwards the request to another action and / or controller.
	 *
	 * @param string $actionName Name of the action to forward to
	 * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
	 * @param string $packageKey Key of the package containing the controller to forward to. If not specified, the current package is assumed.
	 * @param \F3\FLOW3\MVC\Controller\Arguments $arguments Arguments to pass to the target action
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function forward($actionName, $controllerName = NULL, $packageKey = NULL, \F3\FLOW3\MVC\Controller\Arguments $arguments = NULL) {
		$this->request->setDispatched(FALSE);
		$this->request->setControllerActionName($actionName);
		if ($controllerName !== NULL) $this->request->setControllerName($controllerName);
		if ($packageKey !== NULL) $this->request->setControllerPackageKey($packageKey);
		if ($arguments !== NULL) $this->request->setArguments($arguments);
		throw new \F3\FLOW3\MVC\Exception\StopAction();
	}

	/**
	 * Redirects the web request to another uri.
	 *
	 * NOTE: This method only supports web requests and will thrown an exception if used with other request types.
	 *
	 * @param mixed $uri Either a string representation of a URI or a \F3\FLOW3\Property\DataType\URI object
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
	 * @throws \F3\FLOW3\MVC\Exception\UnsupportedRequestType If the request is not a web request
	 * @throws \F3\FLOW3\MVC\Exception\StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function redirect($uri, $delay = 0, $statusCode = 303) {
		if (!$this->request instanceof \F3\FLOW3\MVC\Web\Request) throw new \F3\FLOW3\MVC\Exception\UnsupportedRequestType('redirect() only supports web requests.', 1220539734);

		$escapedUri = htmlentities($uri, ENT_QUOTES, 'utf-8');
		$this->response->setContent('<html><head><meta http-equiv="refresh" content="' . intval($delay) . ';url=' . $escapedUri . '"/></head></html>');
		$this->response->setStatus($statusCode);
		$this->response->setHeader('Location', (string)$uri);
		throw new \F3\FLOW3\MVC\Exception\StopAction();
	}

	/**
	 * Sends the specified HTTP status immediately.
	 *
	 * NOTE: This method only supports web requests and will thrown an exception if used with other request types.
	 *
	 * @param integer $statusCode The HTTP status code
	 * @param string $statusMessage A custom HTTP status message
	 * @param string $content Body content which further explains the status
	 * @throws \F3\FLOW3\MVC\Exception\UnsupportedRequestType If the request is not a web request
	 * @throws \F3\FLOW3\MVC\Exception\StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function throwStatus($statusCode, $statusMessage = NULL, $content = NULL) {
		if (!$this->request instanceof \F3\FLOW3\MVC\Web\Request) throw new \F3\FLOW3\MVC\Exception\UnsupportedRequestType('throwStatus() only supports web requests.', 1220539739);

		$this->response->setStatus($statusCode, $statusMessage);
		if ($content === NULL) $content = $this->response->getStatus();
		$this->response->setContent($content);
		throw new \F3\FLOW3\MVC\Exception\StopAction();
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function mapRequestArgumentsToControllerArguments() {
		$optionalPropertyNames = array();
		$allPropertyNames = $this->arguments->getArgumentNames();
		foreach ($allPropertyNames as $propertyName) {
			if ($this->arguments[$propertyName]->isRequired() === FALSE) $optionalPropertyNames[] = $propertyName;
		}
		$this->propertyMapper->mapAndValidate($allPropertyNames, $this->request->getArguments(), $this->arguments, $optionalPropertyNames);
		$this->argumentsMappingResults = $this->propertyMapper->getMappingResults();
	}
}

?>