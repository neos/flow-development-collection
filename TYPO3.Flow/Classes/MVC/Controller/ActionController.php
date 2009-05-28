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
 * A multi action controller
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ActionController extends \F3\FLOW3\MVC\Controller\AbstractController {

	/**
	 * @var \F3\FLOW3\Reflection\Service
	 * @internal
	 */
	protected $reflectionService;

	/**
	 * By default a Fluid\TemplateView is provided, if a template is available,
	 * then a view with the same name as the current action will be looked up.
	 * If none is available the $defaultViewObjectName will be used and finally
	 * an EmptyView will be created.
	 * @var \F3\FLOW3\MVC\View\ViewInterface
	 */
	protected $view = NULL;

	/**
	 * Pattern after which the view object name is built if no Fluid template
	 * is found.
	 * @var string
	 */
	protected $viewObjectNamePattern = 'F3\@package\View\@controller\@action@format';

	/**
	 * The default view object to use if neither a Fluid template nor an action
	 * specific view object could be found.
	 * @var string
	 */
	protected $defaultViewObjectName = NULL;

	/**
	 * Name of the action method
	 * @var string
	 */
	protected $actionMethodName = 'indexAction';

	/**
	 * Name of the special error action method which is called in case of errors
	 * @var string
	 */
	protected $errorMethodName = 'errorAction';

	/**
	 * Injects the reflection service
	 *
	 * @param \F3\FLOW3\Reflection\Service $reflectionService
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Checks if the current request type is supported by the controller.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The current request
	 * @return boolean TRUE if this request type is supported, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canProcessRequest(\F3\FLOW3\MVC\RequestInterface $request) {
		return parent::canProcessRequest($request);
	}

	/**
	 * Handles a request. The result output is returned by altering the given response.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request object
	 * @param \F3\FLOW3\MVC\ResponseInterface $response The response, modified by this handler
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(\F3\FLOW3\MVC\RequestInterface $request, \F3\FLOW3\MVC\ResponseInterface $response) {
		if (!$this->canProcessRequest($request)) throw new \F3\FLOW3\MVC\Exception\UnsupportedRequestType(get_class($this) . ' does not support requests of type "' . get_class($request) . '". Supported types are: ' . implode(' ', $this->supportedRequestTypes) , 1187701131);

		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->URIBuilder = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\URIBuilder');
		$this->URIBuilder->setRequest($request);

		$this->actionMethodName = $this->resolveActionMethodName();

		$this->initializeActionMethodArguments();
		$this->initializeControllerArgumentsBaseValidators();
		$this->initializeActionMethodValidators();

		$this->initializeAction();
		$actionInitializationMethodName = 'initialize' . ucfirst($this->actionMethodName);
		if (method_exists($this, $actionInitializationMethodName)) {
			call_user_func(array($this, $actionInitializationMethodName));
		}

		$this->mapRequestArgumentsToControllerArguments();
		$this->view = $this->resolveView();
		if ($this->view !== NULL) $this->initializeView($this->view);
		$this->callActionMethod();
	}

	/**
	 * Implementation of the arguments initilization in the action controller:
	 * Automatically registers arguments of the current action
	 *
	 * Don't override this method - use initializeAction() instead.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initializeArguments()
	 * @internal
	 */
	protected function initializeActionMethodArguments() {
		$methodParameters = $this->reflectionService->getMethodParameters(get_class($this), $this->actionMethodName);
		foreach ($methodParameters as $parameterName => $parameterInfo) {
			$dataType = 'Text';
			if (isset($parameterInfo['type'])) {
				$dataType = $parameterInfo['type'];
			} elseif ($parameterInfo['array']) {
				$dataType = 'array';
			}
			$defaultValue = (isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : NULL);

			$this->arguments->addNewArgument($parameterName, $dataType, ($parameterInfo['optional'] === FALSE), $defaultValue);
		}
	}

	/**
	 * Detects and registers any additional validators for arguments which were
	 * specified in the @validate annotations of an action method
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function initializeActionMethodValidators() {
		$validatorConjunctions = $this->validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($this), $this->actionMethodName);
		foreach ($validatorConjunctions as $argumentName => $validatorConjunction) {
			if (!isset($this->arguments[$argumentName])) throw new \F3\FLOW3\MVC\Exception\NoSuchArgument('Found custom validation rule for non existing argument "' . $argumentName . '" in ' . get_class($this) . '->' . $this->actionMethodName . '().', 1239853108);
			$this->arguments[$argumentName]->setValidator($validatorConjunction);
		}
	}

	/**
	 * Determines the action method and assures that the method exists.
	 *
	 * @return string The action method name
	 * @throws \F3\FLOW3\MVC\Exception\NoSuchAction if the action specified in the request object does not exist (and if there's no default action either).
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveActionMethodName() {
		$actionMethodName = $this->request->getControllerActionName() . 'Action';
		foreach (get_class_methods($this) as $existingMethodName) {
			if (strtolower($existingMethodName) === strtolower($actionMethodName)) {
				$actionMethodName = $existingMethodName;
				break;
			}
		}
		if (!method_exists($this, $actionMethodName)) throw new \F3\FLOW3\MVC\Exception\NoSuchAction('An action "' . $actionMethodName . '" does not exist in controller "' . get_class($this) . '".', 1186669086);
		return $actionMethodName;
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
	 * @internal
	 */
	protected function callActionMethod() {
		$argumentsAreValid = TRUE;
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
		}
	}

	/**
	 * Prepares a view for the current action and stores it in $this->view.
	 * By default, this method tries to locate a view with a name matching
	 * the current action.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function resolveView() {
		$view = $this->objectManager->getObject('F3\Fluid\View\TemplateView');
		$controllerContext = $this->buildControllerContext();
		$view->setControllerContext($controllerContext);
		if ($view->hasTemplate() === FALSE) {
			$viewObjectName = $this->resolveViewObjectName();
			if ($viewObjectName === FALSE) $viewObjectName = 'F3\FLOW3\MVC\View\EmptyView';
			$view = $this->objectManager->getObject($viewObjectName);
			$view->setControllerContext($controllerContext);
		}
		$view->assign('flashMessages', $this->popFlashMessages());
		return $view;
	}

	/**
	 * Determines the fully qualified view object name.
	 *
	 * @return mixed The fully qualified view object name or FALSE if no matching view could be found.
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function resolveViewObjectName() {
		$possibleViewName = $this->viewObjectNamePattern;
		$packageKey = $this->request->getControllerPackageKey();
		$subpackageKey = $this->request->getControllerSubpackageKey();
		if ($subpackageKey !== NULL && $subpackageKey !== '') {
			$packageKey.= '\\' . $subpackageKey;
		}
		$possibleViewName = str_replace('@package', $packageKey, $possibleViewName);
		$possibleViewName = str_replace('@controller', $this->request->getControllerName(), $possibleViewName);
		$possibleViewName = str_replace('@action', $this->request->getControllerActionName(), $possibleViewName);

		$viewObjectName = $this->objectManager->getCaseSensitiveObjectName(strtolower(str_replace('@format', $this->request->getFormat(), $possibleViewName)));
		if ($viewObjectName === FALSE) {
			$viewObjectName = $this->objectManager->getCaseSensitiveObjectName(strtolower(str_replace('@format', '', $possibleViewName)));
		}
		if ($viewObjectName === FALSE && $this->defaultViewObjectName !== NULL) {
			$viewObjectName = $this->defaultViewObjectName;
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
	 */
	protected function initializeView(\F3\FLOW3\MVC\View\ViewInterface $view) {
	}

	/**
	 * Initializes the controller before invoking an action method.
	 *
	 * Override this method to solve tasks which all actions have in
	 * common.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeAction() {
	}

	/**
	 * A special action which is called if the originally intended action could
	 * not be called, for example if the arguments were not valid.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function errorAction() {
		$message = 'An error occurred while trying to call ' . get_class($this) . '->' . $this->actionMethodName . '().' . PHP_EOL;
		foreach ($this->argumentsMappingResults->getErrors() as $error) {
			$message .= 'Error:   ' . $error->getMessage() . PHP_EOL;
		}
		foreach ($this->argumentsMappingResults->getWarnings() as $warning) {
			$message .= 'Warning: ' . $warning->getMessage() . PHP_EOL;
		}
		return $message;
	}
}
?>