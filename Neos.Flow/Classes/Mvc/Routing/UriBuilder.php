<?php
namespace Neos\Flow\Mvc\Routing;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Request;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\RequestInterface;
use Neos\Utility\Arrays;
use Neos\Flow\Annotations as Flow;

/**
 * An URI Builder
 *
 * @api
 */
class UriBuilder
{
    /**
     * @Flow\Inject
     * @var \Neos\Flow\Mvc\Routing\RouterInterface
     */
    protected $router;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Utility\Environment
     */
    protected $environment;

    /**
     * @var ActionRequest
     */
    protected $request;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Arguments which have been used for building the last URI
     * @var array
     */
    protected $lastArguments = [];

    /**
     * @var string
     */
    protected $section = '';

    /**
     * @var boolean
     */
    protected $createAbsoluteUri = false;

    /**
     * @var boolean
     */
    protected $addQueryString = false;

    /**
     * @var array
     */
    protected $argumentsToBeExcludedFromQueryString = [];

    /**
     * @var string
     */
    protected $format = null;

    /**
     * Sets the current request and resets the UriBuilder
     *
     * @param ActionRequest $request
     * @return void
     * @api
     * @see reset()
     */
    public function setRequest(ActionRequest $request)
    {
        $this->request = $request;
        $this->reset();
    }

    /**
     * Gets the current request
     *
     * @return ActionRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Additional query parameters.
     * If you want to "prefix" arguments, you can pass in multidimensional arrays:
     * array('prefix1' => array('foo' => 'bar')) gets "&prefix1[foo]=bar"
     *
     * @param array $arguments
     * @return UriBuilder the current UriBuilder to allow method chaining
     * @api
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return array
     * @api
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * If specified, adds a given HTML anchor to the URI (#...)
     *
     * @param string $section
     * @return UriBuilder the current UriBuilder to allow method chaining
     * @api
     */
    public function setSection($section)
    {
        $this->section = $section;
        return $this;
    }

    /**
     * @return string
     * @api
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * Specifies the format of the target (e.g. "html" or "xml")
     *
     * @param string $format (e.g. "html" or "xml"), will be transformed to lowercase!
     * @return UriBuilder the current UriBuilder to allow method chaining
     * @api
     */
    public function setFormat($format)
    {
        $this->format = strtolower($format);
        return $this;
    }

    /**
     * @return string
     * @api
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * If set, the URI is prepended with the current base URI. Defaults to FALSE.
     *
     * @param boolean $createAbsoluteUri
     * @return UriBuilder the current UriBuilder to allow method chaining
     * @api
     */
    public function setCreateAbsoluteUri($createAbsoluteUri)
    {
        $this->createAbsoluteUri = (boolean)$createAbsoluteUri;
        return $this;
    }

    /**
     * @return boolean
     * @api
     */
    public function getCreateAbsoluteUri()
    {
        return $this->createAbsoluteUri;
    }

    /**
     * If set, the current query parameters will be merged with $this->arguments. Defaults to FALSE.
     *
     * @param boolean $addQueryString
     * @return UriBuilder the current UriBuilder to allow method chaining
     * @api
     */
    public function setAddQueryString($addQueryString)
    {
        $this->addQueryString = (boolean)$addQueryString;
        return $this;
    }

    /**
     * @return boolean
     * @api
     */
    public function getAddQueryString()
    {
        return $this->addQueryString;
    }

    /**
     * A list of arguments to be excluded from the query parameters
     * Only active if addQueryString is set
     *
     * @param array $argumentsToBeExcludedFromQueryString
     * @return UriBuilder the current UriBuilder to allow method chaining
     * @api
     */
    public function setArgumentsToBeExcludedFromQueryString(array $argumentsToBeExcludedFromQueryString)
    {
        $this->argumentsToBeExcludedFromQueryString = $argumentsToBeExcludedFromQueryString;
        return $this;
    }

    /**
     * @return array
     * @api
     */
    public function getArgumentsToBeExcludedFromQueryString()
    {
        return $this->argumentsToBeExcludedFromQueryString;
    }

    /**
     * Returns the arguments being used for the last URI being built.
     * This is only set after build() / uriFor() has been called.
     *
     * @return array The last arguments
     */
    public function getLastArguments()
    {
        return $this->lastArguments;
    }

    /**
     * Resets all UriBuilder options to their default value.
     * Note: This won't reset the Request that is attached to this UriBuilder (@see setRequest())
     *
     * @return UriBuilder the current UriBuilder to allow method chaining
     * @api
     */
    public function reset()
    {
        $this->arguments = [];
        $this->section = '';
        $this->format = null;
        $this->createAbsoluteUri = false;
        $this->addQueryString = false;
        $this->argumentsToBeExcludedFromQueryString = [];

        return $this;
    }

    /**
     * Creates an URI used for linking to an Controller action.
     *
     * @param string $actionName Name of the action to be called
     * @param array $controllerArguments Additional query parameters. Will be merged with $this->arguments.
     * @param string $controllerName Name of the target controller. If not set, current ControllerName is used.
     * @param string $packageKey Name of the target package. If not set, current Package is used.
     * @param string $subPackageKey Name of the target SubPackage. If not set, current SubPackage is used.
     * @return string the rendered URI
     * @api
     * @see build()
     * @throws Exception\MissingActionNameException if $actionName parameter is empty
     */
    public function uriFor($actionName, $controllerArguments = [], $controllerName = null, $packageKey = null, $subPackageKey = null)
    {
        if ($actionName === null || $actionName === '') {
            throw new Exception\MissingActionNameException('The URI Builder could not build a URI linking to an action controller because no action name was specified. Please check the stack trace to see which code or template was requesting the link and check the arguments passed to the URI Builder.', 1354629891);
        }
        $controllerArguments['@action'] = strtolower($actionName);
        if ($controllerName !== null) {
            $controllerArguments['@controller'] = strtolower($controllerName);
        } else {
            $controllerArguments['@controller'] = strtolower($this->request->getControllerName());
        }
        if ($packageKey === null && $subPackageKey === null) {
            $subPackageKey = $this->request->getControllerSubpackageKey();
        }
        if ($packageKey === null) {
            $packageKey = $this->request->getControllerPackageKey();
        }
        $controllerArguments['@package'] = strtolower($packageKey);
        if ($subPackageKey !== null) {
            $controllerArguments['@subpackage'] = strtolower($subPackageKey);
        }
        if ($this->format !== null && $this->format !== '') {
            $controllerArguments['@format'] = $this->format;
        }

        $controllerArguments = $this->addNamespaceToArguments($controllerArguments, $this->request);
        return $this->build($controllerArguments);
    }

    /**
     * Adds the argument namespace of the current request to the specified arguments.
     * This happens recursively iterating through the nested requests in case of a subrequest.
     * For example if this is executed inside a widget sub request in a plugin sub request, the result would be:
     * array(
     *   'pluginRequestNamespace' => array(
     *     'widgetRequestNamespace => $arguments
     *    )
     * )
     *
     * @param array $arguments arguments
     * @param RequestInterface $currentRequest
     * @return array arguments with namespace
     */
    protected function addNamespaceToArguments(array $arguments, RequestInterface $currentRequest)
    {
        while (!$currentRequest->isMainRequest()) {
            $argumentNamespace = $currentRequest->getArgumentNamespace();
            if ($argumentNamespace !== '') {
                $arguments = [$argumentNamespace => $arguments];
            }
            $currentRequest = $currentRequest->getParentRequest();
        }
        return $arguments;
    }

    /**
     * Builds the URI
     *
     * @param array $arguments optional URI arguments. Will be merged with $this->arguments with precedence to $arguments
     * @return string The URI
     * @api
     */
    public function build(array $arguments = [])
    {
        $arguments = Arrays::arrayMergeRecursiveOverrule($this->arguments, $arguments);
        $arguments = $this->mergeArgumentsWithRequestArguments($arguments);

        $uri = $this->router->resolve($arguments);
        $this->lastArguments = $arguments;
        if (!$this->environment->isRewriteEnabled()) {
            $uri = 'index.php/' . $uri;
        }
        $httpRequest = $this->request->getHttpRequest();
        if ($this->createAbsoluteUri === true) {
            $uri = $httpRequest->getBaseUri() . $uri;
        } else {
            $uri = $httpRequest->getScriptRequestPath() . $uri;
        }
        if ($this->section !== '') {
            $uri .= '#' . $this->section;
        }
        return $uri;
    }

    /**
     * Merges specified arguments with arguments from request.
     *
     * If $this->request is no sub request, request arguments will only be merged if $this->addQueryString is set.
     * Otherwise all request arguments except for the ones prefixed with the current request argument namespace will
     * be merged. Additionally special arguments (PackageKey, SubpackageKey, ControllerName & Action) are merged.
     *
     * The argument provided through the $arguments parameter always overrule the request
     * arguments.
     *
     * The request hierarchy is structured as follows:
     * root (HTTP) > main (Action) > sub (Action) > sub sub (Action)
     *
     * @param array $arguments
     * @return array
     */
    protected function mergeArgumentsWithRequestArguments(array $arguments)
    {
        if ($this->request !== $this->request->getMainRequest()) {
            $subRequest = $this->request;
            while ($subRequest instanceof ActionRequest) {
                $requestArguments = (array)$subRequest->getArguments();

                // Reset arguments for the request that is bound to this UriBuilder instance
                if ($subRequest === $this->request) {
                    if ($this->addQueryString === false) {
                        $requestArguments = [];
                    } else {
                        foreach ($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
                            unset($requestArguments[$argumentToBeExcluded]);
                        }
                    }
                } else {
                    // Remove all arguments of the current sub request if it's namespaced
                    if ($this->request->getArgumentNamespace() !== '') {
                        $requestNamespace = $this->getRequestNamespacePath($this->request);
                        if ($this->addQueryString === false) {
                            $requestArguments = Arrays::unsetValueByPath($requestArguments, $requestNamespace);
                        } else {
                            foreach ($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
                                $requestArguments = Arrays::unsetValueByPath($requestArguments, $requestNamespace . '.' . $argumentToBeExcluded);
                            }
                        }
                    }
                }

                // Merge special arguments (package, subpackage, controller & action) from main request
                $requestPackageKey = $subRequest->getControllerPackageKey();
                if (!empty($requestPackageKey)) {
                    $requestArguments['@package'] = $requestPackageKey;
                }
                $requestSubpackageKey = $subRequest->getControllerSubpackageKey();
                if (!empty($requestSubpackageKey)) {
                    $requestArguments['@subpackage'] = $requestSubpackageKey;
                }
                $requestControllerName = $subRequest->getControllerName();
                if (!empty($requestControllerName)) {
                    $requestArguments['@controller'] = $requestControllerName;
                }
                $requestActionName = $subRequest->getControllerActionName();
                if (!empty($requestActionName)) {
                    $requestArguments['@action'] = $requestActionName;
                }

                if (count($requestArguments) > 0) {
                    $requestArguments = $this->addNamespaceToArguments($requestArguments, $subRequest);
                    $arguments = Arrays::arrayMergeRecursiveOverrule($requestArguments, $arguments);
                }

                $subRequest = $subRequest->getParentRequest();
            }
        } elseif ($this->addQueryString === true) {
            $requestArguments = $this->request->getArguments();
            foreach ($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
                unset($requestArguments[$argumentToBeExcluded]);
            }

            if ($requestArguments !== []) {
                $arguments = Arrays::arrayMergeRecursiveOverrule($requestArguments, $arguments);
            }
        }

        return $arguments;
    }

    /**
     * Get the path of the argument namespaces of all parent requests.
     * Example: mainrequest.subrequest.subsubrequest
     *
     * @param ActionRequest $request
     * @return string
     */
    protected function getRequestNamespacePath($request)
    {
        if (!$request instanceof Request) {
            $parentPath = $this->getRequestNamespacePath($request->getParentRequest());
            return $parentPath . ($parentPath !== '' && $request->getArgumentNamespace() !== '' ? '.' : '') . $request->getArgumentNamespace();
        }
        return '';
    }
}
