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
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\MVC\Web\Routing\URIBuilder
	 */
	protected $URIBuilder;

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
	 * @var \F3\FLOW3\Validation\ValidatorResolver
	 * @internal
	 */
	protected $validatorResolver;

	/**
	 * The current request
	 * @var \F3\FLOW3\MVC\RequestInterface
	 */
	protected $request;

	/**
	 * The response which will be returned by this action controller
	 * @var \F3\FLOW3\MVC\ResponseInterface
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
	 * @var \F3\FLOW3\Session\SessionInterface
	 * @internal
	 */
	protected $session;

	/**
	 * Constructs the controller.
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory A reference to the Object Factory
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function __construct(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->arguments = $objectFactory->create('F3\FLOW3\MVC\Controller\Arguments');
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the settings of the package this controller belongs to.
	 *
	 * @param array $settings Settings container of the current package
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the property mapper
	 *
	 * @param \F3\FLOW3\Property\Mapper $propertyMapper The property mapper
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectPropertyMapper(\F3\FLOW3\Property\Mapper $propertyMapper) {
		$this->propertyMapper = $propertyMapper;
	}

	/**
	 * Injects the validator resolver
	 *
	 * @param \F3\FLOW3\Validation\ValidatorResolver $validatorResolver
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectValidatorResolver(\F3\FLOW3\Validation\ValidatorResolver $validatorResolver) {
		$this->validatorResolver = $validatorResolver;
	}

	/**
	 * Injects the session
	 *
	 * @param \F3\FLOW3\Session\SessionInterface $session
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @internal
	 */
	public function injectSession(\F3\FLOW3\Session\SessionInterface $session) {
		$this->session = $session;
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
	 * @throws \F3\FLOW3\MVC\Exception\UnsupportedRequestType if the controller doesn't support the current request type
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(\F3\FLOW3\MVC\RequestInterface $request, \F3\FLOW3\MVC\ResponseInterface $response) {
		if (!$this->canProcessRequest($request)) throw new \F3\FLOW3\MVC\Exception\UnsupportedRequestType(get_class($this) . ' does not support requests of type "' . get_class($request) . '". Supported types are: ' . implode(' ', $this->supportedRequestTypes) , 1187701131);

		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->URIBuilder = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\URIBuilder');
		$this->URIBuilder->setRequest($request);

		$this->initializeControllerArgumentsBaseValidators();
		$this->mapRequestArgumentsToControllerArguments();
	}

	/**
	 * Initialize the controller context
	 *
	 * @return \F3\FLOW3\MVC\Controller\ControllerContext ControllerContext to be passed to the view
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @internal
	 */
	protected function buildControllerContext() {
		$controllerContext = $this->objectFactory->create('F3\FLOW3\MVC\Controller\ControllerContext');
		$controllerContext->setRequest($this->request);
		$controllerContext->setResponse($this->response);
		if ($this->arguments !== NULL) {
			$controllerContext->setArguments($this->arguments);
		}
		if ($this->argumentsMappingResults !== NULL) {
			$controllerContext->setArgumentsMappingResults($this->argumentsMappingResults);
		}
		$controllerContext->setURIBuilder($this->URIBuilder);
		return $controllerContext;
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
	protected function forward($actionName, $controllerName = NULL, $packageKey = NULL, array $arguments = NULL) {
		$this->request->setDispatched(FALSE);
		$this->request->setControllerActionName($actionName);
		if ($controllerName !== NULL) $this->request->setControllerName($controllerName);
		if ($packageKey !== NULL) $this->request->setControllerPackageKey($packageKey);
		if ($arguments !== NULL) $this->request->setArguments($arguments);
		throw new \F3\FLOW3\MVC\Exception\StopAction();
	}

	/**
	 * Forwards the request to another action and / or controller.
	 *
	 * NOTE: This method only supports web requests and will throw an exception
	 * if used with other request types.
	 *
	 * @param string $actionName Name of the action to forward to
	 * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
	 * @param string $packageKey Key of the package containing the controller to forward to. If not specified, the current package is assumed.
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
	 * @param \F3\FLOW3\MVC\Controller\Arguments $arguments Arguments to pass to the target action
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\StopAction
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function redirect($actionName, $controllerName = NULL, $packageKey = NULL, array $arguments = NULL, $delay = 0, $statusCode = 303) {
		if (!$this->request instanceof \F3\FLOW3\MVC\Web\Request) throw new \F3\FLOW3\MVC\Exception\UnsupportedRequestType('redirect() only supports web requests.', 1238101344);

		if ($packageKey !== NULL && strpos($packageKey, '\\') !== FALSE) {
			list($packageKey, $subpackageKey) = explode('\\', $packageKey, 2);
		} else {
			$subpackageKey = NULL;
		}
		$uri = $this->URIBuilder->URIFor($actionName, $arguments, $controllerName, $packageKey, $subpackageKey);
		$this->redirectToURI($uri, $delay, $statusCode);
	}

	/**
	 * Redirects the web request to another uri.
	 *
	 * NOTE: This method only supports web requests and will throw an exception if used with other request types.
	 *
	 * @param mixed $uri Either a string representation of a URI or a \F3\FLOW3\Property\DataType\URI object
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
	 * @throws \F3\FLOW3\MVC\Exception\UnsupportedRequestType If the request is not a web request
	 * @throws \F3\FLOW3\MVC\Exception\StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function redirectToURI($uri, $delay = 0, $statusCode = 303) {
		if (!$this->request instanceof \F3\FLOW3\MVC\Web\Request) throw new \F3\FLOW3\MVC\Exception\UnsupportedRequestType('redirect() only supports web requests.', 1220539734);

		$uri = $this->request->getBaseURI() . (string)$uri;
		$escapedUri = htmlentities($uri, ENT_QUOTES, 'utf-8');
		$this->response->setContent('<html><head><meta http-equiv="refresh" content="' . intval($delay) . ';url=' . $escapedUri . '"/></head></html>');
		$this->response->setStatus($statusCode);
		$this->response->setHeader('Location', (string)$uri);
		throw new \F3\FLOW3\MVC\Exception\StopAction();
	}

	/**
	 * Sends the specified HTTP status immediately.
	 *
	 * NOTE: This method only supports web requests and will throw an exception if used with other request types.
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
	 * Collects the base validators which were defined for the data type of each
	 * controller argument and adds them to the argument's validator conjunction.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function initializeControllerArgumentsBaseValidators() {
		foreach ($this->arguments as $argument) {
			$validator = $this->validatorResolver->getBaseValidatorConjunction($argument->getDataType());
			if ($validator !== NULL) $argument->setValidator($validator);
		}
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function mapRequestArgumentsToControllerArguments() {
		$optionalPropertyNames = array();
		$allPropertyNames = $this->arguments->getArgumentNames();
		foreach ($allPropertyNames as $propertyName) {
			if ($this->arguments[$propertyName]->isRequired() === FALSE) $optionalPropertyNames[] = $propertyName;
		}

		$validator = $this->objectManager->getObject('F3\FLOW3\MVC\Controller\ArgumentsValidator');
		$this->propertyMapper->mapAndValidate($allPropertyNames, $this->request->getArguments(), $this->arguments, $optionalPropertyNames, $validator);
		$this->mapIdentityUUIDsToRealObjects($this->arguments);

		$this->argumentsMappingResults = $this->propertyMapper->getMappingResults();
	}

	/**
	 * Checks if the current value of a controller argument is a UUID. If that is the case
	 * and if the argument's data type is a class, this function tries to retrieve the real
	 * object from the data type's repository. If no object was found, the argument's value
	 * is left untouched.
	 *
	 * @param \F3\FLOW3\MVC\Controller\Arguments $arguments The arguments to check and map
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function mapIdentityUUIDsToRealObjects(\F3\FLOW3\MVC\Controller\Arguments $arguments) {
		foreach ($arguments as $argument) {
			$currentValue = $argument->getValue();
			$dataType = $argument->getDataType();
			if (is_string($currentValue)
				&& strpos($dataType, '\\') !== FALSE
				&& preg_match('/([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}/', $currentValue) === 1) {
					$argument->setValue(array('__identity' => $currentValue));
			}
		}
	}

	/**
	 * Add a flash message to the queue. It will live until the next call to
	 * popFlashMessages() in the current session.
	 *
	 * @param mixed $message anything serializable, should be "stringy"
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function pushFlashMessage($message) {
		if (!is_string($message)) throw new \InvalidArgumentException('The flash message must be string, ' . gettype($message) . ' given.', 1243258395);

		$queuedFlashMessages = $this->getFlashMessagesFromSession();
		$queuedFlashMessages[] = $message;
		$this->session->putData('FLOW3_AbstractController_flashMessages', $queuedFlashMessages);
	}

	/**
	 * Returns queued flash messages and clear queue.
	 *
	 * @return array an array with flash messages or NULL if none available
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function popFlashMessages() {
		$queuedFlashMessages = $this->getFlashMessagesFromSession();
		$this->session->putData('FLOW3_AbstractController_flashMessages', NULL);
		return $queuedFlashMessages;
	}

	/**
	 * Returns current flash messages from the seesion, making sure to always
	 * return an array.
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @internal
	 */
	protected function getFlashMessagesFromSession() {
		$flashMessages = $this->session->getData('FLOW3_AbstractController_flashMessages');
		return is_array($flashMessages) ? $flashMessages : array();
	}

}

?>