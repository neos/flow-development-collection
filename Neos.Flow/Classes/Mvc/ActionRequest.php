<?php
namespace Neos\Flow\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Http\Factories\FlowUploadedFile;
use Psr\Http\Message\ServerRequestInterface as HttpRequestInterface;
use Neos\Flow\ObjectManagement\Exception\UnknownObjectException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\SignalSlot\Dispatcher as SignalSlotDispatcher;
use Neos\Utility\Arrays;

/**
 * Represents an internal request targeted to a controller action
 *
 * @api
 */
class ActionRequest implements RequestInterface
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * Package key of the controller which is supposed to handle this request.
     * @var string
     */
    protected $controllerPackageKey = '';

    /**
     * Subpackage key of the controller which is supposed to handle this request.
     * @var string|null
     */
    protected $controllerSubpackageKey = null;

    /**
     * Object name of the controller which is supposed to handle this request.
     * @var string
     */
    protected $controllerName = '';

    /**
     * Name of the action the controller is supposed to take.
     * @var string
     */
    protected $controllerActionName = '';

    /**
     * The arguments for this request. They must be only simple types, no
     * objects allowed.
     * @var array
     */
    protected $arguments = [];

    /**
     * Framework-internal arguments for this request, such as __referrer.
     * All framework-internal arguments start with double underscore (__),
     * and are only used from within the framework. Not for user consumption.
     * Internal Arguments can be objects, in contrast to public arguments.
     * @var array
     */
    protected $internalArguments = [];

    /**
     * Arguments and configuration for plugins – including widgets – which are
     * sub controllers to the controller referred to by this request.
     * @var array
     */
    protected $pluginArguments = [];

    /**
     * An optional namespace for arguments of this request. Used, for example, in
     * plugins and widgets.
     * @var string
     */
    protected $argumentNamespace = '';

    /**
     * The requested representation format
     * @var string
     */
    protected $format = '';

    /**
     * If this request has been changed and needs to be dispatched again
     * @var boolean
     */
    protected $dispatched = false;

    /**
     * The parent request – either another sub ActionRequest a main ActionRequest or null
     * @var ?ActionRequest
     */
    protected $parentRequest;

    /**
     * Cached pointer to the http request
     * @var HttpRequestInterface
     */
    protected $httpRequest;

    /**
     * Cached pointer to a request referring to this one (if any)
     * @var ActionRequest
     */
    protected $referringRequest;

    /**
     * Constructs this action request
     * @see fromHttpRequest
     * @see createSubRequest
     */
    protected function __construct()
    {
    }

    /**
     * @param HttpRequestInterface $request
     * @return ActionRequest
     */
    public static function fromHttpRequest(HttpRequestInterface $request): ActionRequest
    {
        $mainActionRequest = new ActionRequest();
        $mainActionRequest->httpRequest = $request;
        return $mainActionRequest;
    }

    /**
     * Create a sub request from this action request.
     *
     * @return ActionRequest
     */
    public function createSubRequest(): ActionRequest
    {
        $subActionRequest = new ActionRequest();
        $subActionRequest->parentRequest = $this;
        return $subActionRequest;
    }

    /**
     * Returns the parent request
     *
     * @return ActionRequest
     * @api
     */
    public function getParentRequest(): ?ActionRequest
    {
        if ($this->isMainRequest()) {
            return null;
        }

        return $this->parentRequest;
    }

    /**
     * Returns the top level request: the HTTP request object
     *
     * @return HttpRequestInterface
     * @api
     */
    public function getHttpRequest(): HttpRequestInterface
    {
        if ($this->httpRequest === null && $this->isMainRequest() === false) {
            $this->httpRequest = $this->getMainRequest()->getHttpRequest();
        }

        return $this->httpRequest;
    }

    /**
     * Returns the top level ActionRequest: the one just below the HTTP request
     *
     * @return ActionRequest
     * @api
     */
    public function getMainRequest(): ActionRequest
    {
        $parentRequest = $this->getParentRequest();
        if ($parentRequest instanceof ActionRequest) {
            return $parentRequest->getMainRequest();
        }

        return $this;
    }

    /**
     * Checks if this request is the uppermost ActionRequest, just one below the
     * HTTP request.
     *
     * @return boolean
     * @api
     */
    public function isMainRequest(): bool
    {
        return ($this->parentRequest === null);
    }

    /**
     * Returns an ActionRequest which referred to this request, if any.
     *
     * The referring request is not set or determined automatically but must be
     * explicitly set through the corresponding internal argument "__referrer".
     * This mechanism is used by Flow's form and validation mechanisms.
     *
     * @return ActionRequest|null the referring request, or NULL if no referrer found
     * @throws Exception\InvalidActionNameException
     * @throws Exception\InvalidArgumentNameException
     * @throws Exception\InvalidArgumentTypeException
     * @throws Exception\InvalidControllerNameException
     * @throws \Neos\Flow\Security\Exception\InvalidArgumentForHashGenerationException
     * @throws \Neos\Flow\Security\Exception\InvalidHashException
     */
    public function getReferringRequest(): ?ActionRequest
    {
        if ($this->referringRequest !== null) {
            return $this->referringRequest;
        }
        if (!isset($this->internalArguments['__referrer'])) {
            return null;
        }
        if (is_array($this->internalArguments['__referrer'])) {
            $referrerArray = $this->internalArguments['__referrer'];

            $referringRequest = ActionRequest::fromHttpRequest($this->getHttpRequest());

            $arguments = [];
            if (isset($referrerArray['arguments'])) {
                $serializedArgumentsWithHmac = $referrerArray['arguments'];
                $serializedArguments = $this->hashService->validateAndStripHmac($serializedArgumentsWithHmac);
                $arguments = unserialize(base64_decode($serializedArguments));
                unset($referrerArray['arguments']);
            }

            $referringRequest->setArguments(Arrays::arrayMergeRecursiveOverrule($arguments, $referrerArray));
            return $referringRequest;
        }
        $this->referringRequest = $this->internalArguments['__referrer'];
        return $this->referringRequest;
    }

    /**
     * Sets the dispatched flag
     *
     * @param boolean $flag If this request has been dispatched
     * @return void
     * @throws \Neos\Flow\SignalSlot\Exception\InvalidSlotException
     * @api
     */
    public function setDispatched($flag): void
    {
        $this->dispatched = (bool)$flag;

        if ($flag) {
            $this->emitRequestDispatched($this);
        }
    }

    /**
     * If this request has been dispatched and addressed by the responsible
     * controller and the response is ready to be sent.
     *
     * The dispatcher will try to dispatch the request again if it has not been
     * addressed yet.
     *
     * @return boolean true if this request has been dispatched successfully
     * @api
     */
    public function isDispatched(): bool
    {
        return $this->dispatched;
    }

    /**
     * Returns the object name of the controller defined by the package key and
     * controller name
     *
     * @return string The controller's Object Name
     * @api
     */
    public function getControllerObjectName(): string
    {
        $possibleObjectName = '@package\@subpackage\Controller\@controllerController';

        $possibleObjectName = str_replace([
            '@package',
            '@subpackage',
            '@controller',
            '\\\\'
        ], [
            str_replace('.', '\\', $this->controllerPackageKey),
            $this->controllerSubpackageKey ?? '',
            $this->controllerName,
            '\\'
        ], $possibleObjectName);

        $controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($possibleObjectName);
        return $controllerObjectName ?: '';
    }

    /**
     * Explicitly sets the object name of the controller
     *
     * @param string $unknownCasedControllerObjectName The fully qualified controller object name
     * @return void
     * @throws UnknownObjectException
     * @api
     */
    public function setControllerObjectName(string $unknownCasedControllerObjectName): void
    {
        $controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($unknownCasedControllerObjectName);

        if ($controllerObjectName === null) {
            throw new UnknownObjectException('The object "' . $unknownCasedControllerObjectName . '" is not registered.', 1268844071);
        }

        $this->controllerPackageKey = $this->objectManager->getPackageKeyByObjectName($controllerObjectName);

        $matches = [];
        $subject = substr($controllerObjectName, strlen($this->controllerPackageKey) + 1);
        preg_match(
            '/
			^(
				Controller
			|
				(?P<subpackageKey>.+)\\\\Controller
			)
			\\\\(?P<controllerName>[a-z\\\\]+)Controller
			$/ix',
            $subject,
            $matches
        );

        $this->controllerSubpackageKey = $matches['subpackageKey'] ?? null;
        $this->controllerName = $matches['controllerName'];
    }

    /**
     * Sets the package key of the controller.
     *
     * This function tries to determine the correct case for the given package key.
     * If the Package Manager does not know the specified package, the package key
     * cannot be verified or corrected and is stored as is.
     *
     * @param string $packageKey The package key
     * @return void
     * @api
     */
    public function setControllerPackageKey(string $packageKey): void
    {
        $correctlyCasedPackageKey = $this->packageManager->getCaseSensitivePackageKey($packageKey);
        $this->controllerPackageKey = ($correctlyCasedPackageKey !== false) ? $correctlyCasedPackageKey : $packageKey;
    }

    /**
     * Returns the package key of the specified controller.
     *
     * @return string The package key
     * @api
     */
    public function getControllerPackageKey(): string
    {
        return $this->controllerPackageKey;
    }

    /**
     * Sets the subpackage key of the controller.
     *
     * @param string|null $subpackageKey The subpackage key.
     * @return void
     */
    public function setControllerSubpackageKey(?string $subpackageKey): void
    {
        $this->controllerSubpackageKey = (empty($subpackageKey) ? null : $subpackageKey);
    }

    /**
     * Returns the subpackage key of the specified controller.
     * If there is no subpackage key set, the method returns NULL.
     *
     * @return string|null The subpackage key
     * @api
     */
    public function getControllerSubpackageKey(): ?string
    {
        $controllerObjectName = $this->getControllerObjectName();
        if ($this->controllerSubpackageKey !== null && $controllerObjectName !== '') {
            // Extract the subpackage key from the controller object name to assure that the case is correct.
            return substr($controllerObjectName, strlen($this->controllerPackageKey) + 1, strlen((string)$this->controllerSubpackageKey));
        }
        return $this->controllerSubpackageKey;
    }

    /**
     * Sets the name of the controller which is supposed to handle the request.
     * Note: This is not the object name of the controller!
     *
     * Examples: "Standard", "Account", ...
     *
     * @param string $controllerName Name of the controller
     * @return void
     * @throws Exception\InvalidControllerNameException
     */
    public function setControllerName(string $controllerName): void
    {
        if (strpos($controllerName, '_') !== false) {
            throw new Exception\InvalidControllerNameException('The controller name must not contain underscores.', 1217846412);
        }
        $this->controllerName = $controllerName;
    }

    /**
     * Returns the object name of the controller supposed to handle this request, if one
     * was set already (if not, the name of the default controller is returned)
     *
     * @return string Name of the controller
     * @api
     */
    public function getControllerName(): string
    {
        $controllerObjectName = $this->getControllerObjectName();
        if ($controllerObjectName !== '') {

            // Extract the controller name from the controller object name to assure that the case is correct.
            // Note: Controller name can also contain sub structure like "Foo\Bar\Baz"
            return substr($controllerObjectName, -(strlen($this->controllerName) + 10), - 10);
        }
        return $this->controllerName;
    }

    /**
     * Sets the name of the action contained in this request.
     *
     * Note that the action name must start with a lower case letter and is case sensitive.
     *
     * @param string $actionName Name of the action to execute by the controller
     * @return void
     * @throws Exception\InvalidActionNameException if the action name is not valid
     */
    public function setControllerActionName(string $actionName): void
    {
        if ($actionName === '') {
            throw new Exception\InvalidActionNameException('The action name must not be an empty string.', 1289472991);
        }
        if ($actionName[0] !== strtolower($actionName[0])) {
            throw new Exception\InvalidActionNameException('The action name must start with a lower case letter, "' . $actionName . '" does not match this criteria.', 1218473352);
        }
        $this->controllerActionName = $actionName;
    }

    /**
     * Returns the name of the action the controller is supposed to execute.
     *
     * @return string Action name
     * @api
     */
    public function getControllerActionName(): string
    {
        $controllerObjectName = $this->getControllerObjectName();
        if ($controllerObjectName !== '' && ($this->controllerActionName === strtolower($this->controllerActionName))) {
            $controllerClassName = $this->objectManager->getClassNameByObjectName($controllerObjectName);
            $lowercaseActionMethodName = $this->controllerActionName . 'action';
            foreach (get_class_methods($controllerClassName) as $existingMethodName) {
                if (strtolower($existingMethodName) === $lowercaseActionMethodName) {
                    $this->controllerActionName = substr($existingMethodName, 0, -6);
                    break;
                }
            }
        }
        return $this->controllerActionName;
    }

    /**
     * Sets the value of the specified argument
     *
     * @param string $argumentName Name of the argument to set
     * @param mixed $value The new value
     * @return void
     * @throws Exception\InvalidArgumentNameException if the given argument name is no string
     * @throws Exception\InvalidArgumentTypeException if the given argument value is an object
     * @throws Exception\InvalidControllerNameException
     * @throws Exception\InvalidActionNameException
     */
    public function setArgument(string $argumentName, $value): void
    {
        if ($argumentName === '') {
            throw new Exception\InvalidArgumentNameException('Invalid argument name (must be a non-empty string).', 1210858767);
        }

        if (strpos($argumentName, '__') === 0) {
            $this->internalArguments[$argumentName] = $value;
            return;
        }

        // Allowing FlowUploadedFile because that already comes from the HTTP request.
        if (is_object($value) && !($value instanceof FlowUploadedFile)) {
            throw new Exception\InvalidArgumentTypeException('You are not allowed to store objects in the request arguments. Please convert the object of type "' . get_class($value) . '" given for argument "' . $argumentName . '" to a simple type first.', 1302783022);
        }

        if (strpos($argumentName, '--') === 0) {
            $this->pluginArguments[substr($argumentName, 2)] = $value;
            return;
        }

        switch ($argumentName) {
            case '@package':
                $this->setControllerPackageKey($value);
                break;
            case '@subpackage':
                $this->setControllerSubpackageKey($value);
                break;
            case '@controller':
                $this->setControllerName($value);
                break;
            case '@action':
                $this->setControllerActionName($value);
                break;
            case '@format':
                $this->setFormat($value);
                break;
            default:
                $this->arguments[$argumentName] = $value;
        }
    }

    /**
     * Returns the value of the specified argument
     *
     * @param string $argumentName Name of the argument
     * @return string|array Value of the argument
     * @throws Exception\NoSuchArgumentException if such an argument does not exist
     * @api
     */
    public function getArgument(string $argumentName)
    {
        if (!isset($this->arguments[$argumentName])) {
            throw new Exception\NoSuchArgumentException('An argument "' . $argumentName . '" does not exist for this request.', 1176558158);
        }
        return $this->arguments[$argumentName];
    }

    /**
     * Checks if an argument of the given name exists (is set)
     *
     * @param string $argumentName Name of the argument to check
     * @return boolean true if the argument is set, otherwise false
     * @api
     */
    public function hasArgument(string $argumentName): bool
    {
        return isset($this->arguments[$argumentName]);
    }

    /**
     * Sets the specified arguments.
     *
     * The arguments array will be reset therefore any arguments
     * which existed before will be overwritten!
     *
     * @param array $arguments An array of argument names and their values
     * @return void
     * @throws Exception\InvalidArgumentNameException if an argument name is not a string
     * @throws Exception\InvalidArgumentTypeException if an argument value is an object
     * @throws Exception\InvalidControllerNameException
     * @throws Exception\InvalidActionNameException
     */
    public function setArguments(array $arguments): void
    {
        $this->arguments = [];
        foreach ($arguments as $key => $value) {
            $this->setArgument($key, $value);
        }
    }

    /**
     * Returns an Array of arguments and their values
     *
     * @return array Array of arguments and their values (which may be arguments and values as well)
     * @api
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Returns the value of the specified internal argument.
     *
     * Internal arguments are set via setArgument(). In order to be handled as an
     * internal argument, its name must start with two underscores.
     *
     * @param string $argumentName Name of the argument, for example "__fooBar"
     * @return string|object Value of the argument, or NULL if not set.
     */
    public function getInternalArgument(string $argumentName)
    {
        return ($this->internalArguments[$argumentName] ?? null);
    }

    /**
     * Returns the internal arguments of the request, that is, all arguments whose
     * name starts with two underscores.
     *
     * @return array
     */
    public function getInternalArguments(): array
    {
        return $this->internalArguments;
    }

    /**
     * Sets a namespace for the arguments of this request.
     *
     * This doesn't affect the actual behavior of argument handling within this
     * classes' methods but is used in other parts of Flow and its libraries to
     * render argument names which don't conflict with each other.
     *
     * @param string $namespace Argument namespace
     * @return void
     */
    public function setArgumentNamespace(string $namespace): void
    {
        $this->argumentNamespace = $namespace;
    }

    /**
     * Returns the argument namespace, if any.
     *
     * @return string
     */
    public function getArgumentNamespace(): string
    {
        return $this->argumentNamespace;
    }

    /**
     * Returns an array of plugin argument configurations
     *
     * @return array
     */
    public function getPluginArguments(): array
    {
        return $this->pluginArguments;
    }

    /**
     * Sets the requested representation format
     *
     * @param string $format The desired format, something like "html", "xml", "png", "json" or the like. Can even be something like "rss.xml".
     * @return void
     */
    public function setFormat(string $format): void
    {
        $this->format = strtolower($format);
    }

    /**
     * Returns the requested representation format
     *
     * @return string The desired format, something like "html", "xml", "png", "json" or the like.
     * @api
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Emits a signal when a Request has been dispatched
     *
     * The action request is not proxyable, so the signal is dispatched manually here.
     * The safeguard allows unit tests without the dispatcher dependency.
     *
     * @param ActionRequest $request
     * @return void
     * @Flow\Signal
     * @throws \Neos\Flow\SignalSlot\Exception\InvalidSlotException
     */
    protected function emitRequestDispatched($request): void
    {
        if ($this->objectManager !== null) {
            $dispatcher = $this->objectManager->get(SignalSlotDispatcher::class);
            if ($dispatcher !== null) {
                $dispatcher->dispatch(ActionRequest::class, 'requestDispatched', [$request]);
            }
        }
    }

    /**
     * Resets the dispatched status to false
     */
    public function __clone()
    {
        $this->dispatched = false;
    }

    /**
     * We provide our own __sleep method, where we serialize all properties *except* the parentRequest if it is
     * a HTTP request -- as this one contains $_SERVER etc.
     *
     * @return array
     */
    public function __sleep()
    {
        $properties = ['controllerPackageKey', 'controllerSubpackageKey', 'controllerName', 'controllerActionName', 'arguments', 'internalArguments', 'pluginArguments', 'argumentNamespace', 'format', 'dispatched'];
        if ($this->parentRequest instanceof ActionRequest) {
            $properties[] = 'parentRequest';
        }
        return $properties;
    }
}
