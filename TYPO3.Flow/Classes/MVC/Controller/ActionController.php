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
 * A multi action controller
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class ActionController extends \F3\FLOW3\MVC\Controller\AbstractController {

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * The current view, as resolved by resolveView()
	 *
	 * @var \F3\FLOW3\MVC\View\ViewInterface
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
	protected $viewObjectNamePattern = 'F3\@package\View\@controller\@action@format';

	/**
	 * A list of formats and object names of the views which should render them.
	 *
	 * Example:
	 *
	 * array('html' => 'F3\MyApp\MyHtmlView', 'json' => 'F3...
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
	protected $defaultViewObjectName = 'F3\Fluid\View\TemplateView';

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
	 * Injects the reflection service
	 *
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Handles a request. The result output is returned by altering the given response.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request object
	 * @param \F3\FLOW3\MVC\ResponseInterface $response The response, modified by this handler
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function processRequest(\F3\FLOW3\MVC\RequestInterface $request, \F3\FLOW3\MVC\ResponseInterface $response) {
		if ($this->canProcessRequest($request) === FALSE) {
			throw new \F3\FLOW3\MVC\Exception\UnsupportedRequestTypeException(get_class($this) . ' does not support requests of type "' . get_class($request) . '". Supported types are: ' . implode(' ', $this->supportedRequestTypes) , 1187701131);
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
		$this->controllerContext = $this->objectManager->create('F3\FLOW3\MVC\Controller\ControllerContext', $this->request, $this->response, $this->arguments, $this->argumentsMappingResults, $this->uriBuilder, $this->flashMessageContainer);
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveActionMethodName() {
		$actionMethodName = $this->request->getControllerActionName() . 'Action';
		if (!is_callable(array($this, $actionMethodName))) {
			throw new \F3\FLOW3\MVC\Exception\NoSuchActionException('An action "' . $actionMethodName . '" does not exist in controller "' . get_class($this) . '".', 1186669086);
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initializeArguments()
	 */
	protected function initializeActionMethodArguments() {
		$methodParameters = $this->reflectionService->getMethodParameters(get_class($this), $this->actionMethodName);

		foreach ($methodParameters as $parameterName => $parameterInfo) {
			$dataType = NULL;
			if (isset($parameterInfo['type'])) {
				$dataType = $parameterInfo['type'];
			} elseif ($parameterInfo['array']) {
				$dataType = 'array';
			}
			if ($dataType === NULL) throw new \F3\FLOW3\MVC\Exception\InvalidArgumentTypeException('The argument type for parameter $' . $parameterName . ' of method ' . get_class($this) . '->' . $this->actionMethodName . '() could not be detected.' , 1253175643);
			$defaultValue = (isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : NULL);
			$this->arguments->addNewArgument($parameterName, $dataType, ($parameterInfo['optional'] === FALSE), $defaultValue);
		}
	}

	/**
	 * Adds the needed valiators to the Arguments:
	 * - Validators checking the data type from the @param annotation
	 * - Custom validators specified with @validate.
	 *
	 * In case @dontvalidate is NOT set for an argument, the following two
	 * validators are also added:
	 * - Model-based validators (@validate annotations in the model)
	 * - Custom model validator classes
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function initializeActionMethodValidators() {
		$parameterValidators = $this->validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($this), $this->actionMethodName);

		$dontValidateAnnotations = array();
		$methodTagsValues = $this->reflectionService->getMethodTagsValues(get_class($this), $this->actionMethodName);
		if (isset($methodTagsValues['dontvalidate'])) {
			$dontValidateAnnotations = $methodTagsValues['dontvalidate'];
		}

		foreach ($this->arguments as $argument) {
			$validator = $parameterValidators[$argument->getName()];

			if (array_search('$' . $argument->getName(), $dontValidateAnnotations) === FALSE) {
				$baseValidatorConjunction = $this->validatorResolver->getBaseValidatorConjunction($argument->getDataType());
				if (count($baseValidatorConjunction) > 0) {
					$validator->addValidator($baseValidatorConjunction);
				}
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function callActionMethod() {
		$preparedArguments = array();
		foreach ($this->arguments as $argument) {
			$preparedArguments[] = $argument->getValue();
		}

		if ($this->argumentsMappingResults->hasErrors()) {
			$actionResult = call_user_func(array($this, $this->errorMethodName));
		} else {
			$actionResult = call_user_func_array(array($this, $this->actionMethodName), $preparedArguments);
		}
		if ($actionResult === NULL && $this->view instanceof \F3\FLOW3\MVC\View\ViewInterface) {
			$this->response->appendContent($this->view->render());
		} elseif (is_string($actionResult) && strlen($actionResult) > 0) {
			$this->response->appendContent($actionResult);
		} elseif (is_object($actionResult) && method_exists($actionResult, '__toString')) {
			$this->response->appendContent((string)$actionResult);
		}
	}

	/**
	 * Prepares a view for the current action and stores it in $this->view.
	 * By default, this method tries to locate a view with a name matching
	 * the current action.
	 *
	 * @return \F3\Fluid\View\ViewInterface the resolved view
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	protected function resolveView() {
		$viewObjectName = $this->resolveViewObjectName();
		if ($viewObjectName !== FALSE) {
			$view = $this->objectManager->create($viewObjectName);
			if ($view->canRender($this->controllerContext) === FALSE) {
				unset($view);
			}
		}
		if (!isset($view) && $this->defaultViewObjectName != '') {
			$view = $this->objectManager->create($this->defaultViewObjectName);
			if ($view->canRender($this->controllerContext) === FALSE) {
				unset($view);
			}
		}
		if (!isset($view)) {
			$view = $this->objectManager->create('F3\FLOW3\MVC\View\NotFoundView');
			$view->assign('errorMessage', 'No template was found. View could not be resolved for action "' . $this->request->getControllerActionName() . '"');
		}
		$view->setControllerContext($this->controllerContext);
		return $view;
	}

	/**
	 * Determines the fully qualified view object name.
	 *
	 * @return mixed The fully qualified view object name or FALSE if no matching view could be found.
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	protected function resolveViewObjectName() {
		$possibleViewName = $this->viewObjectNamePattern;
		$packageKey = $this->request->getControllerPackageKey();
		$subpackageKey = $this->request->getControllerSubpackageKey();
		$format = $this->request->getFormat();

		if ($subpackageKey !== NULL && $subpackageKey !== '') {
			$packageKey.= '\\' . $subpackageKey;
		}
		$possibleViewName = str_replace('@package', $packageKey, $possibleViewName);
		$possibleViewName = str_replace('@controller', $this->request->getControllerName(), $possibleViewName);
		$possibleViewName = str_replace('@action', $this->request->getControllerActionName(), $possibleViewName);

		$viewObjectName = $this->objectManager->getCaseSensitiveObjectName(strtolower(str_replace('@format', $format, $possibleViewName)));
		if ($viewObjectName === FALSE) {
			$viewObjectName = $this->objectManager->getCaseSensitiveObjectName(strtolower(str_replace('@format', '', $possibleViewName)));
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
	 * @param \F3\FLOW3\MVC\View\ViewInterface $view The view to be initialized
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	protected function initializeView(\F3\FLOW3\MVC\View\ViewInterface $view) {
	}

	/**
	 * A special action which is called if the originally intended action could
	 * not be called, for example if the arguments were not valid.
	 *
	 * The default implementation sets a flash message, request errors and forwards back
	 * to the originating action. This is suitable for most actions dealing with form input.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	protected function errorAction() {
		$this->request->setErrors($this->argumentsMappingResults->getErrors());

		$errorFlashMessage = $this->getErrorFlashMessage();
		if ($errorFlashMessage !== FALSE) {
			$this->flashMessageContainer->add($errorFlashMessage);
		}

		if ($this->request->hasArgument('__referrer')) {
			$referrer = $this->request->getArgument('__referrer');
			$this->forward($referrer['actionName'], $referrer['controllerName'], $referrer['packageKey'], $this->request->getArguments());
		}

		$message = 'An error occurred while trying to call ' . get_class($this) . '->' . $this->actionMethodName . '().' . PHP_EOL;
		foreach ($this->argumentsMappingResults->getErrors() as $error) {
			$message .= 'Error:   ' . $error->getMessage() . PHP_EOL;
		}
		foreach ($this->argumentsMappingResults->getWarnings() as $warning) {
			$message .= 'Warning: ' . $warning->getMessage() . PHP_EOL;
		}
		return $message;
	}

	/**
	 * A template method for displaying custom error flash messages, or to
	 * display no flash message at all on errors. Override this to customize
	 * the flash message in your action controller.
	 *
	 * @return string|boolean The flash message or FALSE if no flash message should be set
	 * @api
	 */
	protected function getErrorFlashMessage() {
		return 'An error occurred while trying to call ' . get_class($this) . '->' . $this->actionMethodName . '()';
	}
}
?>