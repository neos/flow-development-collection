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

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A multi action controller
 *
 * @FLOW3\Scope("singleton")
 * @api
 */
class ActionController extends \TYPO3\FLOW3\Mvc\Controller\AbstractController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * An array of formats (such as "html", "txt", "json" ...) which are supported
	 * by this controller. If none is specified, this controller will default to
	 * "html" for web requests and "txt" for command line requests.
	 *
	 * @var array
	 */
	protected $supportedFormats = array();

	/**
	 * The current view, as resolved by resolveView()
	 *
	 * @var \TYPO3\FLOW3\Mvc\View\ViewInterface
	 * @api
	 */
	protected $view = NULL;

	/**
	 * Pattern after which the view object name is built if no format-specific
	 * view could be resolved.
	 *
	 * @var string
	 * @api
	 */
	protected $viewObjectNamePattern = '@package\View\@controller\@action@format';

	/**
	 * A list of formats and object names of the views which should render them.
	 *
	 * Example:
	 *
	 * array('html' => 'MyCompany\MyApp\MyHtmlView', 'json' => 'MyCompany\...
	 *
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array();

	/**
	 * The default view object to use if none of the resolved views can render
	 * a response for the current request.
	 *
	 * @var string
	 * @api
	 */
	protected $defaultViewObjectName = 'TYPO3\Fluid\View\TemplateView';

	/**
	 * Name of the action method
	 *
	 * @var string
	 */
	protected $actionMethodName = 'indexAction';

	/**
	 * Name of the special error action method which is called in case of errors
	 *
	 * @var string
	 * @api
	 */
	protected $errorMethodName = 'errorAction';

	/**
	 * Handles a request. The result output is returned by altering the given response.
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The request object
	 * @param \TYPO3\FLOW3\MVC\ResponseInterface $response The response, modified by this handler
	 * @return void
	 * @api
	 */
	public function processRequest(\TYPO3\FLOW3\MVC\RequestInterface $request, \TYPO3\FLOW3\MVC\ResponseInterface $response) {
		if ($this->canProcessRequest($request) === FALSE) {
			throw new \TYPO3\FLOW3\MVC\Exception\UnsupportedRequestTypeException(get_class($this) . ' does not support requests of type "' . get_class($request) . '". Supported types are: ' . implode(' ', $this->supportedRequestTypes) , 1187701131);
		}

		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->initializeUriBuilder();

		$this->actionMethodName = $this->resolveActionMethodName();

		$this->initializeActionMethodArguments();
		$this->initializeActionMethodValidators();

		$this->initializeAction();
		$actionInitializationMethodName = 'initialize' . ucfirst($this->actionMethodName);
		if (method_exists($this, $actionInitializationMethodName)) {
			call_user_func(array($this, $actionInitializationMethodName));
		}

		$this->mapRequestArgumentsToControllerArguments();
		$this->controllerContext = new ControllerContext($this->request, $this->response, $this->arguments, $this->uriBuilder, $this->flashMessageContainer);

		if ($this->request->getFormat() === NULL) {
			$this->request->setFormat($this->detectFormat());
		}

		$this->view = $this->resolveView();
		if ($this->view !== NULL) {
			$this->view->assign('settings', $this->settings);
			$this->initializeView($this->view);
		}
		$this->callActionMethod();
	}

	/**
	 * Resolves and checks the current action method name
	 *
	 * @return string Method name of the current action
	 */
	protected function resolveActionMethodName() {
		$actionMethodName = $this->request->getControllerActionName() . 'Action';
		if (!is_callable(array($this, $actionMethodName))) {
			throw new \TYPO3\FLOW3\Mvc\Exception\NoSuchActionException('An action "' . $actionMethodName . '" does not exist in controller "' . get_class($this) . '".', 1186669086);
		}
		return $actionMethodName;
	}

	/**
	 * Implementation of the arguments initialization in the action controller:
	 * Automatically registers arguments of the current action
	 *
	 * Don't override this method - use initializeAction() instead.
	 *
	 * @return void
	 * @see initializeArguments()
	 */
	protected function initializeActionMethodArguments() {
		$methodParameters = $this->reflectionService->getMethodParameters(get_class($this), $this->actionMethodName);

		$this->arguments->removeAll();
		foreach ($methodParameters as $parameterName => $parameterInfo) {
			$dataType = NULL;
			if (isset($parameterInfo['type'])) {
				$dataType = $parameterInfo['type'];
			} elseif ($parameterInfo['array']) {
				$dataType = 'array';
			}
			if ($dataType === NULL) throw new \TYPO3\FLOW3\Mvc\Exception\InvalidArgumentTypeException('The argument type for parameter $' . $parameterName . ' of method ' . get_class($this) . '->' . $this->actionMethodName . '() could not be detected.' , 1253175643);
			$defaultValue = (isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : NULL);
			$this->arguments->addNewArgument($parameterName, $dataType, ($parameterInfo['optional'] === FALSE), $defaultValue);
		}
	}

	/**
	 * Adds the needed valiators to the Arguments:
	 * - Validators checking the data type from the @param annotation
	 * - Custom validators specified with validate annotations.
	 * - Model-based validators (validate annotations in the model)
	 * - Custom model validator classes
	 *
	 * @return void
	 */
	protected function initializeActionMethodValidators() {
		$parameterValidators = $this->validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($this), $this->actionMethodName);

		foreach ($this->arguments as $argument) {
			$validator = $parameterValidators[$argument->getName()];

			$baseValidatorConjunction = $this->validatorResolver->getBaseValidatorConjunction($argument->getDataType());
			if (count($baseValidatorConjunction) > 0) {
				$validator->addValidator($baseValidatorConjunction);
			}
			$argument->setValidator($validator);
		}
	}

	/**
	 * Initializes the controller before invoking an action method.
	 *
	 * Override this method to solve tasks which all actions have in
	 * common.
	 *
	 * @return void
	 * @api
	 */
	protected function initializeAction() {
	}

	/**
	 * Calls the specified action method and passes the arguments.
	 *
	 * If the action returns a string, it is appended to the content in the
	 * response object. If the action doesn't return anything and a valid
	 * view exists, the view is rendered automatically.
	 *
	 * @param string $actionMethodName Name of the action method to call
	 * @return void
	 */
	protected function callActionMethod() {
		$preparedArguments = array();
		foreach ($this->arguments as $argument) {
			$preparedArguments[] = $argument->getValue();
		}

		$validationResult = $this->arguments->getValidationResults();

		if (!$validationResult->hasErrors()) {
			$actionResult = call_user_func_array(array($this, $this->actionMethodName), $preparedArguments);
		} else {
			$ignoreValidationAnnotations = $this->reflectionService->getMethodAnnotations(get_class($this), $this->actionMethodName, 'TYPO3\FLOW3\Annotations\IgnoreValidation');
			$ignoredArguments = array_map(function($annotation) { return $annotation->argumentName; }, $ignoreValidationAnnotations);

				// if there exists more errors than in ignoreValidationAnnotations_=> call error method
				// else => call action method
			$shouldCallActionMethod = TRUE;
			foreach ($validationResult->getSubResults() as $argumentName => $subValidationResult) {
				if (!$subValidationResult->hasErrors()) continue;

				if (array_search('$' . $argumentName, $ignoredArguments) !== FALSE) continue;

				$shouldCallActionMethod = FALSE;
			}

			if ($shouldCallActionMethod) {
				$actionResult = call_user_func_array(array($this, $this->actionMethodName), $preparedArguments);
			} else {
				$actionResult = call_user_func(array($this, $this->errorMethodName));
			}
		}

		if ($actionResult === NULL && $this->view instanceof \TYPO3\FLOW3\Mvc\View\ViewInterface) {
			$this->response->appendContent($this->view->render());
		} elseif (is_string($actionResult) && strlen($actionResult) > 0) {
			$this->response->appendContent($actionResult);
		} elseif (is_object($actionResult) && method_exists($actionResult, '__toString')) {
			$this->response->appendContent((string)$actionResult);
		}
	}

	/**
	 * Sets the format for this request if none has been explicitly defined
	 * elswhere.
	 *
	 * @return string The detected format
	 */
	protected function detectFormat() {
		if ($this->request instanceof \TYPO3\FLOW3\MVC\Web\Request) {
			switch ($this->request->getMethod()) {
				case 'GET' :
				case 'POST' :
				case 'HEAD' :

						// If the client supports any format besides HTML which is also supported
						// by this controller, use that instead of HTML:
					foreach ($this->environment->getAcceptedFormats() as $acceptedFormat) {
						if ($acceptedFormat !== 'html' && array_search($acceptedFormat, $this->supportedFormats) !== FALSE) {
							return $acceptedFormat;
						}
					}
					return 'html';
				break;

				case 'PUT' :
				case 'DELETE' :
					// @TODO Parse "Content-Type" header
					return 'html';
			}
		}
		return 'txt';
	}

	/**
	 * Prepares a view for the current action and stores it in $this->view.
	 * By default, this method tries to locate a view with a name matching
	 * the current action.
	 *
	 * @return \TYPO3\FLOW3\Mvc\View\ViewInterface the resolved view
	 * @api
	 */
	protected function resolveView() {
		$viewObjectName = $this->resolveViewObjectName();
		if ($viewObjectName !== FALSE) {
			$view = $this->objectManager->get($viewObjectName);
			if ($view->canRender($this->controllerContext) === FALSE) {
				unset($view);
			}
		}
		if (!isset($view) && $this->defaultViewObjectName != '') {
			$view = $this->objectManager->get($this->defaultViewObjectName);
			if ($view->canRender($this->controllerContext) === FALSE) {
				unset($view);
			}
		}
		if (!isset($view)) {
			$view = $this->objectManager->get('TYPO3\FLOW3\Mvc\View\NotFoundView');
			$view->assign('errorMessage', 'No template was found. View could not be resolved for action "' . $this->request->getControllerActionName() . '"');
		}
		$view->setControllerContext($this->controllerContext);
		return $view;
	}

	/**
	 * Determines the fully qualified view object name.
	 *
	 * @return mixed The fully qualified view object name or FALSE if no matching view could be found.
	 * @api
	 */
	protected function resolveViewObjectName() {
		$possibleViewObjectName = $this->viewObjectNamePattern;
		$packageKey = $this->request->getControllerPackageKey();
		$subpackageKey = $this->request->getControllerSubpackageKey();
		$format = $this->request->getFormat();

		if ($subpackageKey !== NULL && $subpackageKey !== '') {
			$packageKey.= '\\' . $subpackageKey;
		}
		$possibleViewObjectName = str_replace('@package', str_replace('.', '\\', $packageKey), $possibleViewObjectName);
		$possibleViewObjectName = str_replace('@controller', $this->request->getControllerName(), $possibleViewObjectName);
		$possibleViewObjectName = str_replace('@action', $this->request->getControllerActionName(), $possibleViewObjectName);

		$viewObjectName = $this->objectManager->getCaseSensitiveObjectName(strtolower(str_replace('@format', $format, $possibleViewObjectName)));
		if ($viewObjectName === FALSE) {
			$viewObjectName = $this->objectManager->getCaseSensitiveObjectName(strtolower(str_replace('@format', '', $possibleViewObjectName)));
		}
		if ($viewObjectName === FALSE && isset($this->viewFormatToObjectNameMap[$format])) {
			$viewObjectName = $this->viewFormatToObjectNameMap[$format];
		}
		return $viewObjectName;
	}

	/**
	 * Initializes the view before invoking an action method.
	 *
	 * Override this method to solve assign variables common for all actions
	 * or prepare the view in another way before the action is called.
	 *
	 * @param \TYPO3\FLOW3\Mvc\View\ViewInterface $view The view to be initialized
	 * @return void
	 * @api
	 */
	protected function initializeView(\TYPO3\FLOW3\Mvc\View\ViewInterface $view) {
	}

	/**
	 * A special action which is called if the originally intended action could
	 * not be called, for example if the arguments were not valid.
	 *
	 * The default implementation sets a flash message, request errors and forwards back
	 * to the originating action. This is suitable for most actions dealing with form input.
	 *
	 * @return string
	 * @api
	 */
	protected function errorAction() {
		$errorFlashMessage = $this->getErrorFlashMessage();
		if ($errorFlashMessage !== FALSE) {
			$this->flashMessageContainer->addMessage($errorFlashMessage);
		}

		$referringRequest = $this->request->getReferringRequest();

		if ($referringRequest !== NULL) {
			$originalRequest = clone $this->request;
			$this->request->setOriginalRequest($originalRequest);
			$this->request->setOriginalRequestMappingResults($this->arguments->getValidationResults());

			if ($referringRequest->getControllerSubpackageKey() !== NULL) {
				$packageAndSubpackageKey = $referringRequest->getControllerPackageKey() . '\\' . $referringRequest->getControllerSubpackageKey();
			} else {
				$packageAndSubpackageKey = $referringRequest->getControllerPackageKey();
			}

			$this->forward($referringRequest->getControllerActionName(), $referringRequest->getControllerName(), $packageAndSubpackageKey, $referringRequest->getArguments());
		}

		$message = 'An error occurred while trying to call ' . get_class($this) . '->' . $this->actionMethodName . '().' . PHP_EOL;
		foreach ($this->arguments->getValidationResults()->getFlattenedErrors() as $propertyPath => $errors) {
			foreach ($errors as $error) {
				$message .= 'Error for ' . $propertyPath . ':  ' . $error->render() . PHP_EOL;
			}
		}

		return $message;
	}

	/**
	 * A template method for displaying custom error flash messages, or to
	 * display no flash message at all on errors. Override this to customize
	 * the flash message in your action controller.
	 *
	 * @return \TYPO3\FLOW3\Error\Message The flash message or FALSE if no flash message should be set
	 * @api
	 */
	protected function getErrorFlashMessage() {
		return new \TYPO3\FLOW3\Error\Error('An error occurred while trying to call %1$s->%2$s()', NULL, array(get_class($this), $this->actionMethodName));
	}
}
?>