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

use \F3\FLOW3\MVC\Web\Routing\UriBuilder;

/**
 * An abstract base class for Controllers
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope singleton
 */
abstract class AbstractController implements ControllerInterface {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\MVC\Web\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * Contains the settings of the current package
	 * @var array
	 * @api
	 */
	protected $settings;

	/**
	 * @var \F3\FLOW3\Validation\ValidatorResolver
	 */
	protected $validatorResolver;

	/**
	 * The current request
	 * @var \F3\FLOW3\MVC\RequestInterface
	 * @api
	 */
	protected $request;

	/**
	 * The response which will be returned by this action controller
	 * @var \F3\FLOW3\MVC\ResponseInterface
	 * @api
	 */
	protected $response;

	/**
	 * Arguments passed to the controller
	 * @var \F3\FLOW3\MVC\Controller\Arguments
	 * @api
	 */
	protected $arguments;

	/**
	 * An array of supported request types. By default only web requests are supported.
	 * Modify or replace this array if your specific controller supports certain
	 * (additional) request types.
	 * @var array
	 * @api
	 */
	protected $supportedRequestTypes = array('F3\FLOW3\MVC\Request');

	/**
	 * Contains the controller context
	 * @var \F3\FLOW3\MVC\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * The flash messages. Use $this->flashMessageContainer->add(...) to add a new Flash
	 * Message.
	 *
	 * @var \F3\FLOW3\MVC\Controller\FlashMessageContainer
	 * @api
	 */
	protected $flashMessageContainer;

	/**
	 * Constructs the controller
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->arguments = new Arguments(array());
	}

	/**
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
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
	 * Injects the validator resolver
	 *
	 * @param \F3\FLOW3\Validation\ValidatorResolver $validatorResolver
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectValidatorResolver(\F3\FLOW3\Validation\ValidatorResolver $validatorResolver) {
		$this->validatorResolver = $validatorResolver;
	}

	/**
	 * Injects the flash message container
	 *
	 * @param \F3\FLOW3\MVC\Controller\FlashMessageContainer $flashMessageContainer the flash message container
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectFlashMessageContainer(\F3\FLOW3\MVC\Controller\FlashMessageContainer $flashMessageContainer) {
		$this->flashMessageContainer = $flashMessageContainer;
	}

	/**
	 * Checks if the current request type is supported by the controller.
	 *
	 * If your controller only supports certain request types, either
	 * replace / modify the supporteRequestTypes property or override this
	 * method.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The current request
	 * @return boolean TRUE if this request type is supported, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function canProcessRequest(\F3\FLOW3\MVC\RequestInterface $request) {
		foreach ($this->supportedRequestTypes as $supportedRequestType) {
			if ($request instanceof $supportedRequestType) return TRUE;
		}
		return FALSE;
	}

	/**
	 * Processes a general request. The result can be returned by altering the given response.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request object
	 * @param \F3\FLOW3\MVC\ResponseInterface $response The response, modified by this handler
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\UnsupportedRequestTypeException if the controller doesn't support the current request type
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function processRequest(\F3\FLOW3\MVC\RequestInterface $request, \F3\FLOW3\MVC\ResponseInterface $response) {
		if (!$this->canProcessRequest($request)) throw new \F3\FLOW3\MVC\Exception\UnsupportedRequestTypeException(get_class($this) . ' does not support requests of type "' . get_class($request) . '". Supported types are: ' . implode(' ', $this->supportedRequestTypes) , 1187701131);

		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->initializeUriBuilder();

		$this->initializeControllerArgumentsBaseValidators();
		$this->mapRequestArgumentsToControllerArguments();
		$this->controllerContext = new ControllerContext($this->request, $this->response, $this->arguments, $this->uriBuilder, $this->flashMessageContainer);
	}

	/**
	 * Initialize the URI builder in $this->uriBuilder
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function initializeUriBuilder() {
		$this->uriBuilder = new UriBuilder();
		$this->uriBuilder->setRequest($this->request);
	}

	/**
	 * Returns this controller's context.
	 * Note that the context is only available after processRequest() has been called.
	 *
	 * @return \F3\FLOW3\MVC\Controller\ControllerContext The current controller context
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getControllerContext() {
		return $this->controllerContext;
	}

	/**
	 * Forwards the request to another action and / or controller.
	 *
	 * @param string $actionName Name of the action to forward to
	 * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
	 * @param string $packageKey Key of the package containing the controller to forward to. If not specified, the current package is assumed.
	 * @param array $arguments Arguments to pass to the target action
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\StopActionException
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	protected function forward($actionName, $controllerName = NULL, $packageKey = NULL, array $arguments = array()) {
		$originalRequest = clone $this->request;

		$this->request->setDispatched(FALSE);
		$this->request->setControllerActionName($actionName);
		if ($controllerName !== NULL) $this->request->setControllerName($controllerName);
		if ($packageKey !== NULL) $this->request->setControllerPackageKey($packageKey);
		$this->request->setArguments($arguments);

		$this->request->setOriginalRequest($originalRequest);
		$this->request->setOriginalRequestMappingResults($this->arguments->getValidationResults());

		$this->arguments->removeAll();
		throw new \F3\FLOW3\MVC\Exception\StopActionException();
	}

	/**
	 * Redirects the request to another action and / or controller.
	 *
	 * NOTE: This method only supports web requests and will throw an exception
	 * if used with other request types.
	 *
	 * @param string $actionName Name of the action to forward to
	 * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
	 * @param string $packageKey Key of the package containing the controller to forward to. If not specified, the current package is assumed.
	 * @param array $arguments Array of arguments for the target action
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
	 * @param string $format The format to use for the redirect URI
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\StopActionException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	protected function redirect($actionName, $controllerName = NULL, $packageKey = NULL, array $arguments = NULL, $delay = 0, $statusCode = 303, $format = NULL) {
		if (!$this->request instanceof \F3\FLOW3\MVC\Web\Request) throw new \F3\FLOW3\MVC\Exception\UnsupportedRequestTypeException('redirect() only supports web requests.', 1238101344);

		if ($packageKey !== NULL && strpos($packageKey, '\\') !== FALSE) {
			list($packageKey, $subpackageKey) = explode('\\', $packageKey, 2);
		} else {
			$subpackageKey = NULL;
		}
		$this->uriBuilder->reset();
		if ($format === NULL) {
			$this->uriBuilder->setFormat($this->request->getFormat());
		} else {
			$this->uriBuilder->setFormat($format);
		}

		$uri = $this->uriBuilder->uriFor($actionName, $arguments, $controllerName, $packageKey, $subpackageKey);
		$this->redirectToUri($this->request->getBaseUri() . $uri, $delay, $statusCode);
	}

	/**
	 * Redirects the web request to another uri.
	 *
	 * NOTE: This method only supports web requests and will throw an exception
	 * if used with other request types.
	 *
	 * @param mixed $uri Either a string representation of a URI or a \F3\FLOW3\Property\DataType\Uri object
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
	 * @throws \F3\FLOW3\MVC\Exception\UnsupportedRequestTypeException If the request is not a web request
	 * @throws \F3\FLOW3\MVC\Exception\StopActionException
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	protected function redirectToUri($uri, $delay = 0, $statusCode = 303) {
		if (!$this->request instanceof \F3\FLOW3\MVC\Web\Request) throw new \F3\FLOW3\MVC\Exception\UnsupportedRequestTypeException('redirect() only supports web requests.', 1220539734);

		$escapedUri = htmlentities($uri, ENT_QUOTES, 'utf-8');
		$this->response->setContent('<html><head><meta http-equiv="refresh" content="' . intval($delay) . ';url=' . $escapedUri . '"/></head></html>');
		$this->response->setStatus($statusCode);
		if ($delay === 0) {
			$this->response->setHeader('Location', (string)$uri);
		}
		throw new \F3\FLOW3\MVC\Exception\StopActionException();
	}

	/**
	 * Sends the specified HTTP status immediately.
	 *
	 * NOTE: This method only supports web requests and will throw an exception if used with other request types.
	 *
	 * @param integer $statusCode The HTTP status code
	 * @param string $statusMessage A custom HTTP status message
	 * @param string $content Body content which further explains the status
	 * @throws \F3\FLOW3\MVC\Exception\UnsupportedRequestTypeException If the request is not a web request
	 * @throws \F3\FLOW3\MVC\Exception\StopActionException
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	protected function throwStatus($statusCode, $statusMessage = NULL, $content = NULL) {
		if (!$this->request instanceof \F3\FLOW3\MVC\Web\Request) throw new \F3\FLOW3\MVC\Exception\UnsupportedRequestTypeException('throwStatus() only supports web requests.', 1220539739);

		$this->response->setStatus($statusCode, $statusMessage);
		if ($content === NULL) $content = $this->response->getStatus();
		$this->response->setContent($content);
		throw new \F3\FLOW3\MVC\Exception\StopActionException();
	}

	/**
	 * Collects the base validators which were defined for the data type of each
	 * controller argument and adds them to the argument's validator conjunction.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeControllerArgumentsBaseValidators() {
		foreach ($this->arguments as $argument) {
			$validator = $this->validatorResolver->getBaseValidatorConjunction($argument->getDataType());
			if (count($validator) > 0) $argument->setValidator($validator);
		}
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function mapRequestArgumentsToControllerArguments() {
		foreach ($this->arguments as $argument) {
			$argumentName = $argument->getName();

			if ($this->request->hasArgument($argumentName)) {
				$argument->setValue($this->request->getArgument($argumentName));
			} elseif ($argument->isRequired()) {
				throw new \F3\FLOW3\MVC\Exception\RequiredArgumentMissingException('Required argument "' . $argumentName  . '" is not set.', 1298012500);
			}
		}
	}
}

?>