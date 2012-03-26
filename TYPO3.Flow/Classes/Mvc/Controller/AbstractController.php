<?php
namespace TYPO3\FLOW3\Mvc\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\FLOW3\Mvc\Routing\UriBuilder;
use \TYPO3\FLOW3\Error\Message;

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An abstract base class for Controllers
 *
 * @api
 * @FLOW3\Scope("singleton")
 */
abstract class AbstractController implements ControllerInterface {

	/**
	 * @var \TYPO3\FLOW3\Mvc\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * Contains the settings of the current package
	 * @var array
	 * @api
	 */
	protected $settings;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Validation\ValidatorResolver
	 */
	protected $validatorResolver;

	/**
	 * The current request
	 * @var \TYPO3\FLOW3\Mvc\RequestInterface
	 * @api
	 */
	protected $request;

	/**
	 * The response which will be returned by this action controller
	 * @var \TYPO3\FLOW3\Http\Response
	 * @api
	 */
	protected $response;

	/**
	 * Arguments passed to the controller
	 * @var \TYPO3\FLOW3\Mvc\Controller\Arguments
	 * @api
	 */
	protected $arguments;

	/**
	 * An array of supported request types. By default only web requests are supported.
	 * Modify or replace this array if your specific controller supports certain
	 * (additional) request types.
	 *
	 * @var array
	 * @api
	 */
	protected $supportedRequestTypes = array('TYPO3\FLOW3\MVC\Web\Request');

	/**
	 * @var \TYPO3\FLOW3\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * The flash messages. Use $this->flashMessageContainer->addMessage(...) to add a new Flash
	 * Message.
	 *
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Mvc\FlashMessageContainer
	 */
	protected $flashMessageContainer;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Constructs the controller
	 *
	 */
	public function __construct() {
		$this->arguments = new Arguments(array());
	}

	/**
	 * Injects the settings of the package this controller belongs to.
	 *
	 * @param array $settings Settings container of the current package
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Checks if the current request type is supported by the controller.
	 *
	 * If your controller only supports certain request types, either
	 * replace / modify the supporteRequestTypes property or override this
	 * method.
	 *
	 * @param \TYPO3\FLOW3\Mvc\RequestInterface $request The current request
	 * @return boolean TRUE if this request type is supported, otherwise FALSE
	 * @api
	 */
	public function canProcessRequest(\TYPO3\FLOW3\Mvc\RequestInterface $request) {
		foreach ($this->supportedRequestTypes as $supportedRequestType) {
			if ($request instanceof $supportedRequestType) return TRUE;
		}
		return FALSE;
	}

	/**
	 * Processes a general request. The result can be returned by altering the given response.
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The request object
	 * @param \TYPO3\FLOW3\MVC\ResponseInterface $response The response, modified by this handler
	 * @return void
	 * @throws \TYPO3\FLOW3\MVC\Exception\UnsupportedRequestTypeException if the controller doesn't support the current request type
	 * @api
	 */
	public function processRequest(\TYPO3\FLOW3\MVC\RequestInterface $request, \TYPO3\FLOW3\MVC\ResponseInterface $response) {
		if (!$this->canProcessRequest($request)) throw new \TYPO3\FLOW3\MVC\Exception\UnsupportedRequestTypeException(get_class($this) . ' does not support requests of type "' . get_class($request) . '". Supported types are: ' . implode(' ', $this->supportedRequestTypes) , 1187701131);

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
	 */
	protected function initializeUriBuilder() {
		$this->uriBuilder = new UriBuilder();
		$this->uriBuilder->setRequest($this->request);
	}

	/**
	 * Returns this controller's context.
	 * Note that the context is only available after processRequest() has been called.
	 *
	 * @return \TYPO3\FLOW3\Mvc\Controller\ControllerContext The current controller context
	 * @api
	 */
	public function getControllerContext() {
		return $this->controllerContext;
	}

	/**
	 * Creates a Message object and adds it to the FlashMessageContainer.
	 * This method should be used to add FlashMessages rather than interacting with the container directly.
	 *
	 * @param string $messageBody text of the FlashMessage
	 * @param string $messageTitle optional header of the FlashMessage
	 * @param string $severity severity of the FlashMessage (one of the \TYPO3\FLOW3\Error\Message::SEVERITY_* constants)
	 * @param array $messageArguments arguments to be passed to the FlashMessage
	 * @param integer $messageCode
	 * @return void
	 * @throws \InvalidArgumentException if the message body is no string
	 * @see \TYPO3\FLOW3\Error\Message
	 * @api
	 */
	public function addFlashMessage($messageBody, $messageTitle = '', $severity = Message::SEVERITY_OK, array $messageArguments = array(), $messageCode = NULL) {
		if (!is_string($messageBody)) {
			throw new \InvalidArgumentException('The message body must be of type string but "' . gettype($messageBody) . '" given.', 1243258395);
		}
		switch ($severity) {
			case Message::SEVERITY_NOTICE:
				$message = new \TYPO3\FLOW3\Error\Notice($messageBody, $messageCode, $messageArguments, $messageTitle);
				break;
			case Message::SEVERITY_WARNING:
				$message = new \TYPO3\FLOW3\Error\Warning($messageBody, $messageCode, $messageArguments, $messageTitle);
				break;
			case Message::SEVERITY_ERROR:
				$message = new \TYPO3\FLOW3\Error\Error($messageBody, $messageCode, $messageArguments, $messageTitle);
				break;
			default:
				$message = new Message($messageBody, $messageCode, $messageArguments, $messageTitle);
			break;
		}
		$this->flashMessageContainer->addMessage($message);
	}

	/**
	 * Forwards the request to another action and / or controller.
	 *
	 * Request is directly transfered to the other action / controller
	 * without the need for a new request.
	 *
	 * @param string $actionName Name of the action to forward to
	 * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
	 * @param string $packageKey Key of the package containing the controller to forward to. If not specified, the current package is assumed.
	 * @param array $arguments Arguments to pass to the target action
	 * @return void
	 * @throws \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 * @see redirect()
	 * @api
	 */
	protected function forward($actionName, $controllerName = NULL, $packageKey = NULL, array $arguments = array()) {
		$this->request->setDispatched(FALSE);
		$this->request->setControllerActionName($actionName);
		if ($controllerName !== NULL) $this->request->setControllerName($controllerName);
		if ($packageKey !== NULL && strpos($packageKey, '\\') !== FALSE) {
			list($packageKey, $subpackageKey) = explode('\\', $packageKey, 2);
		} else {
			$subpackageKey = NULL;
		}
		if ($packageKey !== NULL) $this->request->setControllerPackageKey($packageKey);
		$this->request->setControllerSubpackageKey($subpackageKey);
		$arguments = $this->persistenceManager->convertObjectsToIdentityArrays($arguments);
		$this->request->setArguments($arguments);

		$this->arguments->removeAll();
		throw new \TYPO3\FLOW3\Mvc\Exception\StopActionException();
	}

	/**
	 * Redirects the request to another action and / or controller.
	 *
	 * Redirect will be sent to the client which then performs another request to the new URI.
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
	 * @throws \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 * @see forward()
	 * @api
	 */
	protected function redirect($actionName, $controllerName = NULL, $packageKey = NULL, array $arguments = NULL, $delay = 0, $statusCode = 303, $format = NULL) {
		if (!$this->request instanceof \TYPO3\FLOW3\Mvc\ActionRequest) throw new \TYPO3\FLOW3\Mvc\Exception\UnsupportedRequestTypeException('redirect() only supports web requests.', 1238101344);

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
	 * @param mixed $uri Either a string representation of a URI or a \TYPO3\FLOW3\Http\Uri object
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
	 * @throws \TYPO3\FLOW3\Mvc\Exception\UnsupportedRequestTypeException If the request is not a web request
	 * @throws \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 * @api
	 */
	protected function redirectToUri($uri, $delay = 0, $statusCode = 303) {
		if (!$this->request instanceof \TYPO3\FLOW3\Mvc\ActionRequest) throw new \TYPO3\FLOW3\Mvc\Exception\UnsupportedRequestTypeException('redirect() only supports web requests.', 1220539734);

		$escapedUri = htmlentities($uri, ENT_QUOTES, 'utf-8');
		$this->response->setContent('<html><head><meta http-equiv="refresh" content="' . intval($delay) . ';url=' . $escapedUri . '"/></head></html>');
		$this->response->setStatus($statusCode);
		if ($delay === 0) {
			$this->response->setHeader('Location', (string)$uri);
		}
		throw new \TYPO3\FLOW3\Mvc\Exception\StopActionException();
	}

	/**
	 * Sends the specified HTTP status immediately.
	 *
	 * NOTE: This method only supports web requests and will throw an exception if used with other request types.
	 *
	 * @param integer $statusCode The HTTP status code
	 * @param string $statusMessage A custom HTTP status message
	 * @param string $content Body content which further explains the status
	 * @throws \TYPO3\FLOW3\Mvc\Exception\UnsupportedRequestTypeException If the request is not a web request
	 * @throws \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 * @api
	 */
	protected function throwStatus($statusCode, $statusMessage = NULL, $content = NULL) {
		if (!$this->request instanceof \TYPO3\FLOW3\Mvc\ActionRequest) throw new \TYPO3\FLOW3\Mvc\Exception\UnsupportedRequestTypeException('throwStatus() only supports web requests.', 1220539739);

		$this->response->setStatus($statusCode, $statusMessage);
		if ($content === NULL) $content = $this->response->getStatus();
		$this->response->setContent($content);
		throw new \TYPO3\FLOW3\Mvc\Exception\StopActionException();
	}

	/**
	 * Collects the base validators which were defined for the data type of each
	 * controller argument and adds them to the argument's validator conjunction.
	 *
	 * @return void
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
	 */
	protected function mapRequestArgumentsToControllerArguments() {
		foreach ($this->arguments as $argument) {
			$argumentName = $argument->getName();

			if ($this->request->hasArgument($argumentName)) {
				$argument->setValue($this->request->getArgument($argumentName));
			} elseif ($argument->isRequired()) {
				throw new \TYPO3\FLOW3\Mvc\Exception\RequiredArgumentMissingException('Required argument "' . $argumentName  . '" is not set.', 1298012500);
			}
		}
	}
}

?>