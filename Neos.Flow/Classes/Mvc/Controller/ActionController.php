<?php
namespace Neos\Flow\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Result;
use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages as Error;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\InvalidActionVisibilityException;
use Neos\Flow\Mvc\Exception\InvalidArgumentTypeException;
use Neos\Flow\Mvc\Exception\NoSuchActionException;
use Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException;
use Neos\Flow\Mvc\Exception\ViewNotFoundException;
use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\Mvc\ResponseInterface;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Flow\Mvc\ViewConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\Exception\TargetNotFoundException;
use Neos\Flow\Property\TypeConverter\Error\TargetNotFoundError;
use Neos\Flow\Reflection\ReflectionService;

/**
 * An HTTP based multi-action controller.
 *
 * The action specified in the given ActionRequest is dispatched to a method in
 * the concrete controller whose name ends with "*Action". If no matching action
 * method is found, the action specified in $errorMethodName is invoked.
 *
 * This controller also takes care of mapping arguments found in the ActionRequest
 * to the corresponding method arguments of the action method. It also invokes
 * validation for these arguments by invoking the Property Mapper.
 *
 * By defining media types in $supportedMediaTypes, content negotiation based on
 * the browser's Accept header and additional routing configuration is used to
 * determine the output format the controller should return.
 *
 * Depending on the action being called, a fitting view - determined by configuration
 * - will be selected. By specifying patterns, custom view classes or an alternative
 * controller / action to template path mapping can be defined.
 *
 * @api
 */
class ActionController extends AbstractController
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var MvcPropertyMappingConfigurationService
     */
    protected $mvcPropertyMappingConfigurationService;

    /**
     * @Flow\Inject
     * @var ViewConfigurationManager
     */
    protected $viewConfigurationManager;

    /**
     * The current view, as resolved by resolveView()
     *
     * @var ViewInterface
     * @api
     */
    protected $view = null;

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
    protected $viewFormatToObjectNameMap = [];

    /**
     * The default view object to use if none of the resolved views can render
     * a response for the current request.
     *
     * @var string
     * @api
     */
    protected $defaultViewObjectName = null;

    /**
     * @Flow\InjectConfiguration(package="Neos.Flow", path="mvc.view.defaultImplementation")
     * @var string
     */
    protected $defaultViewImplementation;

    /**
     * Name of the action method
     *
     * @var string
     */
    protected $actionMethodName;

    /**
     * Name of the special error action method which is called in case of errors
     *
     * @var string
     * @api
     */
    protected $errorMethodName = 'errorAction';

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $systemLogger;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Handles a request. The result output is returned by altering the given response.
     *
     * @param RequestInterface $request The request object
     * @param ResponseInterface $response The response, modified by this handler
     * @return void
     * @throws UnsupportedRequestTypeException
     * @api
     */
    public function processRequest(RequestInterface $request, ResponseInterface $response)
    {
        $this->initializeController($request, $response);

        $this->actionMethodName = $this->resolveActionMethodName();

        $this->initializeActionMethodArguments();
        $this->initializeActionMethodValidators();

        $this->initializeAction();
        $actionInitializationMethodName = 'initialize' . ucfirst($this->actionMethodName);
        if (method_exists($this, $actionInitializationMethodName)) {
            call_user_func([$this, $actionInitializationMethodName]);
        }
        $this->mvcPropertyMappingConfigurationService->initializePropertyMappingConfigurationFromRequest($this->request, $this->arguments);

        $this->mapRequestArgumentsToControllerArguments();

        if ($this->view === null) {
            $this->view = $this->resolveView();
        }
        if ($this->view !== null) {
            $this->view->assign('settings', $this->settings);
            $this->view->setControllerContext($this->controllerContext);
            $this->initializeView($this->view);
        }

        $this->callActionMethod();
    }

    /**
     * Resolves and checks the current action method name
     *
     * @return string Method name of the current action
     * @throws NoSuchActionException
     * @throws InvalidActionVisibilityException
     */
    protected function resolveActionMethodName()
    {
        $actionMethodName = $this->request->getControllerActionName() . 'Action';
        if (!is_callable([$this, $actionMethodName])) {
            throw new NoSuchActionException(sprintf('An action "%s" does not exist in controller "%s".', $actionMethodName, get_class($this)), 1186669086);
        }
        $publicActionMethods = static::getPublicActionMethods($this->objectManager);
        if (!isset($publicActionMethods[$actionMethodName])) {
            throw new InvalidActionVisibilityException(sprintf('The action "%s" in controller "%s" is not public!', $actionMethodName, get_class($this)), 1186669086);
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
     * @throws InvalidArgumentTypeException
     * @see initializeArguments()
     */
    protected function initializeActionMethodArguments()
    {
        $actionMethodParameters = static::getActionMethodParameters($this->objectManager);
        if (isset($actionMethodParameters[$this->actionMethodName])) {
            $methodParameters = $actionMethodParameters[$this->actionMethodName];
        } else {
            $methodParameters = [];
        }

        $this->arguments->removeAll();
        foreach ($methodParameters as $parameterName => $parameterInfo) {
            $dataType = null;
            if (isset($parameterInfo['type'])) {
                $dataType = $parameterInfo['type'];
            } elseif ($parameterInfo['array']) {
                $dataType = 'array';
            }
            if ($dataType === null) {
                throw new InvalidArgumentTypeException('The argument type for parameter $' . $parameterName . ' of method ' . get_class($this) . '->' . $this->actionMethodName . '() could not be detected.', 1253175643);
            }
            $defaultValue = (isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : null);
            $this->arguments->addNewArgument($parameterName, $dataType, ($parameterInfo['optional'] === false), $defaultValue);
        }
    }

    /**
     * Returns a map of action method names and their parameters.
     *
     * @param ObjectManagerInterface $objectManager
     * @return array Array of method parameters by action name
     * @Flow\CompileStatic
     */
    public static function getActionMethodParameters($objectManager)
    {
        $reflectionService = $objectManager->get(ReflectionService::class);

        $result = [];

        $className = get_called_class();
        $methodNames = get_class_methods($className);
        foreach ($methodNames as $methodName) {
            if (strlen($methodName) > 6 && strpos($methodName, 'Action', strlen($methodName) - 6) !== false) {
                $result[$methodName] = $reflectionService->getMethodParameters($className, $methodName);
            }
        }

        return $result;
    }

    /**
     * This is a helper method purely used to make initializeActionMethodValidators()
     * testable without mocking static methods.
     *
     * @return array
     */
    protected function getInformationNeededForInitializeActionMethodValidators()
    {
        return [
            static::getActionValidationGroups($this->objectManager),
            static::getActionMethodParameters($this->objectManager),
            static::getActionValidateAnnotationData($this->objectManager),
            static::getActionIgnoredValidationArguments($this->objectManager)
        ];
    }

    /**
     * Adds the needed validators to the Arguments:
     *
     * - Validators checking the data type from the @param annotation
     * - Custom validators specified with validate annotations.
     * - Model-based validators (validate annotations in the model)
     * - Custom model validator classes
     *
     * @return void
     */
    protected function initializeActionMethodValidators()
    {
        list($validateGroupAnnotations, $actionMethodParameters, $actionValidateAnnotations, $actionIgnoredArguments) = $this->getInformationNeededForInitializeActionMethodValidators();

        if (isset($validateGroupAnnotations[$this->actionMethodName])) {
            $validationGroups = $validateGroupAnnotations[$this->actionMethodName];
        } else {
            $validationGroups = ['Default', 'Controller'];
        }

        if (isset($actionMethodParameters[$this->actionMethodName])) {
            $methodParameters = $actionMethodParameters[$this->actionMethodName];
        } else {
            $methodParameters = [];
        }

        if (isset($actionValidateAnnotations[$this->actionMethodName])) {
            $validateAnnotations = $actionValidateAnnotations[$this->actionMethodName];
        } else {
            $validateAnnotations = [];
        }
        $parameterValidators = $this->validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($this), $this->actionMethodName, $methodParameters, $validateAnnotations);

        if (isset($actionIgnoredArguments[$this->actionMethodName])) {
            $ignoredArguments = $actionIgnoredArguments[$this->actionMethodName];
        } else {
            $ignoredArguments = [];
        }

        foreach ($this->arguments as $argument) {
            $argumentName = $argument->getName();
            if (isset($ignoredArguments[$argumentName]) && !$ignoredArguments[$argumentName]['evaluate']) {
                continue;
            }

            $validator = $parameterValidators[$argumentName];

            $baseValidatorConjunction = $this->validatorResolver->getBaseValidatorConjunction($argument->getDataType(), $validationGroups);
            if (count($baseValidatorConjunction) > 0) {
                $validator->addValidator($baseValidatorConjunction);
            }
            $argument->setValidator($validator);
        }
    }

    /**
     * Returns a map of action method names and their validation groups.
     *
     * @param ObjectManagerInterface $objectManager
     * @return array Array of validation groups by action method name
     * @Flow\CompileStatic
     */
    public static function getActionValidationGroups($objectManager)
    {
        $reflectionService = $objectManager->get(ReflectionService::class);

        $result = [];

        $className = get_called_class();
        $methodNames = get_class_methods($className);
        foreach ($methodNames as $methodName) {
            if (strlen($methodName) > 6 && strpos($methodName, 'Action', strlen($methodName) - 6) !== false) {
                $validationGroupsAnnotation = $reflectionService->getMethodAnnotation($className, $methodName, Flow\ValidationGroups::class);
                if ($validationGroupsAnnotation !== null) {
                    $result[$methodName] = $validationGroupsAnnotation->validationGroups;
                }
            }
        }

        return $result;
    }

    /**
     * Returns a map of action method names and their validation parameters.
     *
     * @param ObjectManagerInterface $objectManager
     * @return array Array of validate annotation parameters by action method name
     * @Flow\CompileStatic
     */
    public static function getActionValidateAnnotationData($objectManager)
    {
        $reflectionService = $objectManager->get(ReflectionService::class);

        $result = [];

        $className = get_called_class();
        $methodNames = get_class_methods($className);
        foreach ($methodNames as $methodName) {
            if (strlen($methodName) > 6 && strpos($methodName, 'Action', strlen($methodName) - 6) !== false) {
                $validateAnnotations = $reflectionService->getMethodAnnotations($className, $methodName, Flow\Validate::class);
                $result[$methodName] = array_map(function ($validateAnnotation) {
                    return [
                        'type' => $validateAnnotation->type,
                        'options' => $validateAnnotation->options,
                        'argumentName' => $validateAnnotation->argumentName,
                    ];
                }, $validateAnnotations);
            }
        }

        return $result;
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
    protected function initializeAction()
    {
    }

    /**
     * Calls the specified action method and passes the arguments.
     *
     * If the action returns a string, it is appended to the content in the
     * response object. If the action doesn't return anything and a valid
     * view exists, the view is rendered automatically.
     *
     * @return void
     */
    protected function callActionMethod()
    {
        $preparedArguments = [];
        foreach ($this->arguments as $argument) {
            $preparedArguments[] = $argument->getValue();
        }

        $validationResult = $this->arguments->getValidationResults();

        if (!$validationResult->hasErrors()) {
            $actionResult = call_user_func_array([$this, $this->actionMethodName], $preparedArguments);
        } else {
            $actionIgnoredArguments = static::getActionIgnoredValidationArguments($this->objectManager);
            if (isset($actionIgnoredArguments[$this->actionMethodName])) {
                $ignoredArguments = $actionIgnoredArguments[$this->actionMethodName];
            } else {
                $ignoredArguments = [];
            }

            // if there exists more errors than in ignoreValidationAnnotations => call error method
            // else => call action method
            $shouldCallActionMethod = true;
            /** @var Result $subValidationResult */
            foreach ($validationResult->getSubResults() as $argumentName => $subValidationResult) {
                if (!$subValidationResult->hasErrors()) {
                    continue;
                }
                if (isset($ignoredArguments[$argumentName]) && $subValidationResult->getErrors(TargetNotFoundError::class) === []) {
                    continue;
                }
                $shouldCallActionMethod = false;
                break;
            }

            if ($shouldCallActionMethod) {
                $actionResult = call_user_func_array([$this, $this->actionMethodName], $preparedArguments);
            } else {
                $actionResult = call_user_func([$this, $this->errorMethodName]);
            }
        }

        if ($actionResult === null && $this->view instanceof ViewInterface) {
            $this->response->appendContent($this->view->render());
        } elseif (is_string($actionResult) && strlen($actionResult) > 0) {
            $this->response->appendContent($actionResult);
        } elseif (is_object($actionResult) && method_exists($actionResult, '__toString')) {
            $this->response->appendContent((string)$actionResult);
        }
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @return array Array of argument names as key by action method name
     * @Flow\CompileStatic
     */
    public static function getActionIgnoredValidationArguments($objectManager)
    {
        $reflectionService = $objectManager->get(ReflectionService::class);

        $result = [];

        $className = get_called_class();
        $methodNames = get_class_methods($className);
        foreach ($methodNames as $methodName) {
            if (strlen($methodName) > 6 && strpos($methodName, 'Action', strlen($methodName) - 6) !== false) {
                $ignoreValidationAnnotations = $reflectionService->getMethodAnnotations($className, $methodName, Flow\IgnoreValidation::class);
                /** @var Flow\IgnoreValidation $ignoreValidationAnnotation */
                foreach ($ignoreValidationAnnotations as $ignoreValidationAnnotation) {
                    if (!isset($ignoreValidationAnnotation->argumentName)) {
                        throw new \InvalidArgumentException('An IgnoreValidation annotation on a method must be given an argument name.', 1318456607);
                    }
                    $result[$methodName][$ignoreValidationAnnotation->argumentName] = [
                        'evaluate' => $ignoreValidationAnnotation->evaluate
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @return array Array of all public action method names, indexed by method name
     * @Flow\CompileStatic
     */
    public static function getPublicActionMethods($objectManager)
    {
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);

        $result = [];

        $className = get_called_class();
        $methodNames = get_class_methods($className);
        foreach ($methodNames as $methodName) {
            if (strlen($methodName) > 6 && strpos($methodName, 'Action', strlen($methodName) - 6) !== false) {
                if ($reflectionService->isMethodPublic($className, $methodName)) {
                    $result[$methodName] = true;
                }
            }
        }

        return $result;
    }

    /**
     * Prepares a view for the current action and stores it in $this->view.
     * By default, this method tries to locate a view with a name matching
     * the current action.
     *
     * @return ViewInterface the resolved view
     * @throws ViewNotFoundException if no view can be resolved
     */
    protected function resolveView()
    {
        $viewsConfiguration = $this->viewConfigurationManager->getViewConfiguration($this->request);
        $viewObjectName = $this->defaultViewImplementation;
        if (!empty($this->defaultViewObjectName)) {
            $viewObjectName = $this->defaultViewObjectName;
        }
        $viewObjectName = $this->resolveViewObjectName() ?: $viewObjectName;
        if (isset($viewsConfiguration['viewObjectName'])) {
            $viewObjectName = $viewsConfiguration['viewObjectName'];
        }

        if (!is_a($viewObjectName, ViewInterface::class, true)) {
            throw new ViewNotFoundException(sprintf('View class has to implement ViewInterface but "%s" in action "%s" of controller "%s" does not.',
                $viewObjectName, $this->request->getControllerActionName(), get_class($this)), 1355153188);
        }

        $viewOptions = isset($viewsConfiguration['options']) ? $viewsConfiguration['options'] : [];
        $view = $viewObjectName::createWithOptions($viewOptions);

        return $view;
    }

    /**
     * Determines the fully qualified view object name.
     *
     * @return mixed The fully qualified view object name or FALSE if no matching view could be found.
     * @api
     */
    protected function resolveViewObjectName()
    {
        $possibleViewObjectName = $this->viewObjectNamePattern;
        $packageKey = $this->request->getControllerPackageKey();
        $subpackageKey = $this->request->getControllerSubpackageKey();
        $format = $this->request->getFormat();

        if ($subpackageKey !== null && $subpackageKey !== '') {
            $packageKey .= '\\' . $subpackageKey;
        }
        $possibleViewObjectName = str_replace('@package', str_replace('.', '\\', $packageKey), $possibleViewObjectName);
        $possibleViewObjectName = str_replace('@controller', $this->request->getControllerName(), $possibleViewObjectName);
        $possibleViewObjectName = str_replace('@action', $this->request->getControllerActionName(), $possibleViewObjectName);

        $viewObjectName = $this->objectManager->getCaseSensitiveObjectName(strtolower(str_replace('@format', $format, $possibleViewObjectName)));
        if ($viewObjectName === false) {
            $viewObjectName = $this->objectManager->getCaseSensitiveObjectName(strtolower(str_replace('@format', '', $possibleViewObjectName)));
        }
        if ($viewObjectName === false && isset($this->viewFormatToObjectNameMap[$format])) {
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
     * @param ViewInterface $view The view to be initialized
     * @return void
     * @api
     */
    protected function initializeView(ViewInterface $view)
    {
    }

    /**
     * A special action which is called if the originally intended action could
     * not be called, for example if the arguments were not valid.
     *
     * The default implementation checks for TargetNotFoundErrors, sets a flash message, request errors and forwards back
     * to the originating action. This is suitable for most actions dealing with form input.
     *
     * @return string
     * @api
     */
    protected function errorAction()
    {
        $this->handleTargetNotFoundError();
        $this->addErrorFlashMessage();
        $this->forwardToReferringRequest();

        return $this->getFlattenedValidationErrorMessage();
    }

    /**
     * Checks if the arguments validation result contain errors of type TargetNotFoundError and throws a TargetNotFoundException if that's the case for a top-level object.
     * You can override this method (or the errorAction()) if you need a different behavior
     *
     * @return void
     * @throws TargetNotFoundException
     * @api
     */
    protected function handleTargetNotFoundError()
    {
        foreach (array_keys($this->request->getArguments()) as $argumentName) {
            /** @var TargetNotFoundError $targetNotFoundError */
            $targetNotFoundError = $this->arguments->getValidationResults()->forProperty($argumentName)->getFirstError(TargetNotFoundError::class);
            if ($targetNotFoundError !== false) {
                throw new TargetNotFoundException($targetNotFoundError->getMessage(), $targetNotFoundError->getCode());
            }
        }
    }

    /**
     * If an error occurred during this request, this adds a flash message describing the error to the flash
     * message container.
     *
     * @return void
     */
    protected function addErrorFlashMessage()
    {
        $errorFlashMessage = $this->getErrorFlashMessage();
        if ($errorFlashMessage !== false) {
            $this->flashMessageContainer->addMessage($errorFlashMessage);
        }
    }

    /**
     * If information on the request before the current request was sent, this method forwards back
     * to the originating request. This effectively ends processing of the current request, so do not
     * call this method before you have finished the necessary business logic!
     *
     * @return void
     * @throws ForwardException
     */
    protected function forwardToReferringRequest()
    {
        $referringRequest = $this->request->getReferringRequest();
        if ($referringRequest === null) {
            return;
        }
        $packageKey = $referringRequest->getControllerPackageKey();
        $subpackageKey = $referringRequest->getControllerSubpackageKey();
        if ($subpackageKey !== null) {
            $packageKey .= '\\' . $subpackageKey;
        }
        $argumentsForNextController = $referringRequest->getArguments();
        $argumentsForNextController['__submittedArguments'] = $this->request->getArguments();
        $argumentsForNextController['__submittedArgumentValidationResults'] = $this->arguments->getValidationResults();

        $this->forward($referringRequest->getControllerActionName(), $referringRequest->getControllerName(), $packageKey, $argumentsForNextController);
    }

    /**
     * Returns a string containing all validation errors separated by PHP_EOL.
     *
     * @return string
     */
    protected function getFlattenedValidationErrorMessage()
    {
        $outputMessage = 'Validation failed while trying to call ' . get_class($this) . '->' . $this->actionMethodName . '().' . PHP_EOL;
        $logMessage = $outputMessage;

        foreach ($this->arguments->getValidationResults()->getFlattenedErrors() as $propertyPath => $errors) {
            foreach ($errors as $error) {
                $logMessage .= 'Error for ' . $propertyPath . ':  ' . $error->render() . PHP_EOL;
            }
        }
        $this->systemLogger->log($logMessage, LOG_ERR);

        return $outputMessage;
    }

    /**
     * A template method for displaying custom error flash messages, or to
     * display no flash message at all on errors. Override this to customize
     * the flash message in your action controller.
     *
     * @return \Neos\Error\Messages\Message The flash message or FALSE if no flash message should be set
     * @api
     */
    protected function getErrorFlashMessage()
    {
        return new Error\Error('An error occurred while trying to call %1$s->%2$s()', null, [get_class($this), $this->actionMethodName]);
    }
}
