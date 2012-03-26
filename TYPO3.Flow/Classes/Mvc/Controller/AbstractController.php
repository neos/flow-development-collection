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

use TYPO3\FLOW3\Mvc\Routing\UriBuilder;
use TYPO3\FLOW3\Mvc\ActionRequest;
use TYPO3\FLOW3\Error\Message;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An abstract base class for HTTP based controllers
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
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Validation\ValidatorResolver
	 */
	protected $validatorResolver;

	/**
	 * The current action request directed to this controller
	 * @var \TYPO3\FLOW3\Mvc\ActionRequest
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
	 * Initializes the controller
	 *
	 * This method should be called by the concrete processRequest() method.
	 *
	 * @param \TYPO3\FLOW3\Mvc\RequestInterface $request
	 * @param \TYPO3\FLOW3\Mvc\ResponseInterface $response
	 * @throws \TYPO3\FLOW3\Mvc\Exception\UnsupportedRequestTypeException
	 */
	protected function initializeController(\TYPO3\FLOW3\Mvc\RequestInterface $request, \TYPO3\FLOW3\Mvc\ResponseInterface $response) {
		if (!$request instanceof ActionRequest) {
			throw new \TYPO3\FLOW3\Mvc\Exception\UnsupportedRequestTypeException(get_class($this) . ' only supports action requests – requests of type "' . get_class($request) . '" given. ' , 1187701131);
		}

		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->uriBuilder = new UriBuilder();
		$this->uriBuilder->setRequest($this->request);

		$this->arguments = new Arguments(array());
		$this->controllerContext = new ControllerContext($this->request, $this->response, $this->arguments, $this->uriBuilder, $this->flashMessageContainer);
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
	 *
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
			throw new \InvalidArgumentException('The message body must be of type string, "' . gettype($messageBody) . '" given.', 1243258395);
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
	 *
	 * @param string $actionName Name of the action to forward to
	 * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
	 * @param string $packageKey Key of the package containing the controller to forward to. May also contain the sub package, concatenated with backslash (Vendor.Foo\Bar\Baz). If not specified, the current package is assumed.
	 * @param array $arguments Arguments to pass to the target action
	 * @return void
	 * @throws \TYPO3\FLOW3\Mvc\Exception\ForwardException
	 * @see redirect()
	 * @api
	 */
	protected function forward($actionName, $controllerName = NULL, $packageKey = NULL, array $arguments = array()) {
		$nextRequest = clone $this->request;
		$nextRequest->setControllerActionName($actionName);

		if ($controllerName !== NULL) {
			$nextRequest->setControllerName($controllerName);
		}
		if ($packageKey !== NULL && strpos($packageKey, '\\') !== FALSE) {
			list($packageKey, $subpackageKey) = explode('\\', $packageKey, 2);
		} else {
			$subpackageKey = NULL;
		}
		if ($packageKey !== NULL) {
			$nextRequest->setControllerPackageKey($packageKey);
		}
		if ($subpackageKey !== NULL) {
			$nextRequest->setControllerSubpackageKey($subpackageKey);
		}

		$regularArguments = array();
		foreach ($arguments as $argumentName => $argumentValue) {
			if (substr($argumentName, 0, 2) === '__') {
				$nextRequest->setArgument($argumentName, $argumentValue);
			} else {
				$regularArguments[$argumentName] = $argumentValue;
			}
		}
		$nextRequest->setArguments($this->persistenceManager->convertObjectsToIdentityArrays($regularArguments));
		$this->arguments->removeAll();

		$forwardException = new \TYPO3\FLOW3\Mvc\Exception\ForwardException();
		$forwardException->setNextRequest($nextRequest);
		throw $forwardException;
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

		$uri = $this->uriBuilder->setCreateAbsoluteUri(TRUE)->uriFor($actionName, $arguments, $controllerName, $packageKey, $subpackageKey);
		$this->redirectToUri($uri, $delay, $statusCode);
	}

	/**
	 * Redirects to another URI
	 *
	 * @param mixed $uri Either a string representation of a URI or a \TYPO3\FLOW3\Http\Uri object
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
	 * @throws \TYPO3\FLOW3\Mvc\Exception\UnsupportedRequestTypeException If the request is not a web request
	 * @throws \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 * @api
	 */
	protected function redirectToUri($uri, $delay = 0, $statusCode = 303) {
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
		$this->response->setStatus($statusCode, $statusMessage);
		if ($content === NULL) {
			$content = $this->response->getStatus();
		}
		$this->response->setContent($content);
		throw new \TYPO3\FLOW3\Mvc\Exception\StopActionException();
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @return void
	 * @api
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