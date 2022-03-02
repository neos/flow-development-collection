<?php
declare(strict_types=1);
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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\BaseUriProvider;
use Neos\Flow\Http\Exception as HttpException;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Utility\Environment;
use Neos\Utility\Arrays;

/**
 * An URI Builder
 *
 * @api
 */
final class UriBuilder
{
    /**
     * @Flow\Inject
     * @var RouterInterface
     */
    protected $router;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @Flow\Inject
     * @var BaseUriProvider
     */
    protected $baseUriProvider;

    private ActionRequest $request;
    private array $arguments = [];
    private array $lastArguments = [];
    private string $section = '';
    private bool $createAbsoluteUri = false;
    private bool $addQueryString = false;
    private array $argumentsToBeExcludedFromQueryString = [];
    private ?string $format = null;

    public function __construct(ActionRequest $request)
    {
        $this->request = $request;
    }


    /**
     * Sets the current request and resets the UriBuilder
     *
     * @see reset()
     * @deprecated with Flow 8.0 – create a new instance instead: new UriBuilder($request);
     */
    public function setRequest(ActionRequest $request): void
    {
        $this->request = $request;
        $this->reset();
    }

    /**
     * Gets the current request
     *
     * @return ActionRequest
     */
    public function getRequest(): ActionRequest
    {
        return $this->request;
    }

    /**
     * @param array $arguments
     * @return UriBuilder the current UriBuilder to allow method chaining
     * @deprecated with Flow 8.0 – use {@see withArguments()} instead
     */
    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Additional query parameters.
     * If you want to "prefix" arguments, you can pass in multidimensional arrays:
     * array('prefix1' => array('foo' => 'bar')) gets "&prefix1[foo]=bar"
     *
     * @api
     */
    public function withArguments(array $arguments): self
    {
        if ($arguments === $this->arguments) {
            return $this;
        }
        $newInstance = clone $this;
        $newInstance->arguments = $arguments;
        return $newInstance;
    }

    /**
     * @return array
     * @api
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param string $section
     * @return UriBuilder the current UriBuilder to allow method chaining
     * @deprecated with Flow 8.0 – use {@see withSection()} instead
     */
    public function setSection(string $section): self
    {
        $this->section = $section;
        return $this;
    }

    /**
     * Adds a given HTML anchor to the URI (#...)
     *
     * @api
     */
    public function withSection(string $section): self
    {
        if ($section === $this->section) {
            return $this;
        }
        $newInstance = clone $this;
        $newInstance->section = $section;
        return $newInstance;
    }

    /**
     * @return string
     * @api
     */
    public function getSection(): string
    {
        return $this->section;
    }

    /**
     * @param string $format (e.g. "html" or "xml"), will be transformed to lowercase!
     * @return UriBuilder the current UriBuilder to allow method chaining
     * @deprecated with Flow 8.0 – use {@see withFormat()} instead
     */
    public function setFormat(string $format): self
    {
        $this->format = strtolower($format);
        return $this;
    }

    /**
     * Specifies the format of the target (e.g. "html" or "xml")
     *
     * @api
     */
    public function withFormat(string $format): self
    {
        if ($format === $this->format) {
            return $this;
        }
        $newInstance = clone $this;
        $newInstance->format = $format;
        return $newInstance;
    }

    /**
     * @return string
     * @api
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * @param bool $createAbsoluteUri
     * @return UriBuilder the current UriBuilder to allow method chaining
     * @deprecated with Flow 8.0 – use {@see withCreateAbsoluteUri()} instead
     */
    public function setCreateAbsoluteUri(bool $createAbsoluteUri): self
    {
        $this->createAbsoluteUri = $createAbsoluteUri;
        return $this;
    }

    /**
     * If set, the URI is prepended with the current base URI. Defaults to false.
     *
     * @api
     */
    public function withCreateAbsoluteUri(bool $createAbsoluteUri): self
    {
        if ($createAbsoluteUri === $this->createAbsoluteUri) {
            return $this;
        }
        $newInstance = clone $this;
        $newInstance->createAbsoluteUri = $createAbsoluteUri;
        return $newInstance;
    }

    /**
     * @return bool
     * @api
     */
    public function getCreateAbsoluteUri(): bool
    {
        return $this->createAbsoluteUri;
    }

    /**
     * @deprecated with Flow 8.0 – use {@see withAddQueryString()} instead
     */
    public function setAddQueryString($addQueryString): self
    {
        $this->addQueryString = (bool)$addQueryString;
        return $this;
    }

    /**
     * If set, the current query parameters will be merged with $this->arguments. Defaults to false.
     *
     * @api
     */
    public function withAddQueryString(bool $addQueryString): self
    {
        if ($addQueryString === $this->addQueryString) {
            return $this;
        }
        $newInstance = clone $this;
        $newInstance->addQueryString = $addQueryString;
        return $newInstance;
    }

    /**
     * @return bool
     * @api
     */
    public function getAddQueryString(): bool
    {
        return $this->addQueryString;
    }

    /**
     * @deprecated with Flow 8.0 - use {@see withArgumentsToBeExcludedFromQueryString()} instead
     */
    public function setArgumentsToBeExcludedFromQueryString(array $argumentsToBeExcludedFromQueryString): self
    {
        $this->argumentsToBeExcludedFromQueryString = $argumentsToBeExcludedFromQueryString;
        return $this;
    }

    /**
     * A list of arguments to be excluded from the query parameters
     * Only active if addQueryString is set
     *
     * @api
     */
    public function withArgumentsToBeExcludedFromQueryString(array $argumentsToBeExcludedFromQueryString): self
    {
        if ($argumentsToBeExcludedFromQueryString === $this->argumentsToBeExcludedFromQueryString) {
            return $this;
        }
        $newInstance = clone $this;
        $newInstance->argumentsToBeExcludedFromQueryString = $argumentsToBeExcludedFromQueryString;
        return $newInstance;
    }

    /**
     * @return array
     * @api
     */
    public function getArgumentsToBeExcludedFromQueryString(): array
    {
        return $this->argumentsToBeExcludedFromQueryString;
    }

    /**
     * Returns the arguments being used for the last URI being built.
     * This is only set after build() / uriFor() has been called.
     *
     * @return array The last arguments
     */
    public function getLastArguments(): array
    {
        return $this->lastArguments;
    }

    /**
     * Resets all UriBuilder options to their default value.
     * Note: This won't reset the Request that is attached to this UriBuilder (@see setRequest())
     *
     * @return UriBuilder the current UriBuilder to allow method chaining
     * @deprecated with Flow 8.0 - create a new instance instead
     */
    public function reset(): self
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
     * Creates a URI used for linking to a Controller action.
     *
     * @param string $actionName Name of the action to be called
     * @param array $controllerArguments Additional query parameters. Will be merged with $this->arguments.
     * @param string|null $controllerName Name of the target controller. If not set, current ControllerName is used.
     * @param string|null $packageKey Name of the target package. If not set, current Package is used.
     * @param string|null $subPackageKey Name of the target SubPackage. If not set, current SubPackage is used.
     * @return string the rendered URI
     * @api
     * @see build()
     * @throws Exception\MissingActionNameException if $actionName parameter is empty
     * @throws NoMatchingRouteException
     */
    public function uriFor(string $actionName, array $controllerArguments = [], string $controllerName = null, string $packageKey = null, string $subPackageKey = null): string
    {
        if (empty($actionName)) {
            throw new Exception\MissingActionNameException('The URI Builder could not build a URI linking to an action controller because no action name was specified. Please check the stack trace to see which code or template was requesting the link and check the arguments passed to the URI Builder.', 1354629891);
        }
        if (empty($controllerName)) {
            $controllerName = $this->request->getControllerName();
        }
        if (empty($packageKey) && empty($subPackageKey)) {
            $subPackageKey = $this->request->getControllerSubpackageKey();
        }
        if (empty($packageKey)) {
            $packageKey = $this->request->getControllerPackageKey();
        }

        $controllerArguments['@action'] = strtolower($actionName);
        $controllerArguments['@controller'] = strtolower($controllerName);
        $controllerArguments['@package'] = strtolower($packageKey);
        if ($subPackageKey !== null) {
            $controllerArguments['@subpackage'] = strtolower($subPackageKey);
        }
        if (!empty($this->format)) {
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
     * @param ActionRequest $currentRequest
     * @return array arguments with namespace
     */
    private function addNamespaceToArguments(array $arguments, ActionRequest $currentRequest): array
    {
        while ($currentRequest instanceof ActionRequest && !$currentRequest->isMainRequest()) {
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
     * @return string the (absolute or relative) URI as string
     * @throws NoMatchingRouteException
     * @api
     */
    public function build(array $arguments = []): string
    {
        $arguments = Arrays::arrayMergeRecursiveOverrule($this->arguments, $arguments);
        $arguments = $this->mergeArgumentsWithRequestArguments($arguments);

        $httpRequest = $this->request->getHttpRequest();

        $uriPathPrefix = $this->environment->isRewriteEnabled() ? '' : 'index.php/';
        $uriPathPrefix = RequestInformationHelper::getScriptRequestPath($httpRequest) . $uriPathPrefix;
        $uriPathPrefix = ltrim($uriPathPrefix, '/');

        $routeParameters = $httpRequest->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS) ?? RouteParameters::createEmpty();
        try {
            $resolveContext = new ResolveContext($this->baseUriProvider->getConfiguredBaseUriOrFallbackToCurrentRequest($httpRequest), $arguments, $this->createAbsoluteUri, $uriPathPrefix, $routeParameters);
        } catch (HttpException $e) {
            throw new \RuntimeException(sprintf('Failed to determine base URI: %s', $e->getMessage()), 1645455082, $e);
        }
        $resolvedUri = $this->router->resolve($resolveContext);
        if ($this->section !== '') {
            $resolvedUri = $resolvedUri->withFragment($this->section);
        }

        $this->lastArguments = $arguments;
        return (string)$resolvedUri;
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
     */
    private function mergeArgumentsWithRequestArguments(array $arguments): array
    {
        if ($this->request !== $this->request->getMainRequest()) {
            $subRequest = $this->request;
            while ($subRequest instanceof ActionRequest) {
                $requestArguments = $subRequest->getArguments();

                // Reset arguments for the request that is bound to this UriBuilder instance
                if ($subRequest === $this->request) {
                    if ($this->addQueryString === false) {
                        $requestArguments = [];
                    } else {
                        foreach ($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
                            unset($requestArguments[$argumentToBeExcluded]);
                        }
                    }
                } else if ($this->request->getArgumentNamespace() !== '') {
                    $requestNamespace = $this->getRequestNamespacePath($this->request);
                    if ($this->addQueryString === false) {
                        $requestArguments = Arrays::unsetValueByPath($requestArguments, $requestNamespace);
                    } else {
                        foreach ($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
                            $requestArguments = Arrays::unsetValueByPath($requestArguments, $requestNamespace . '.' . $argumentToBeExcluded);
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
                $requestFormat = $subRequest->getFormat();
                if (!empty($requestFormat)) {
                    $requestArguments['@format'] = $requestFormat;
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
    private function getRequestNamespacePath(ActionRequest $request): string
    {
        $namespaceParts = [];
        while ($request !== null && $request->isMainRequest() === false) {
            $namespaceParts[] = $request->getArgumentNamespace();
            $request = $request->getParentRequest();
        }

        return implode('.', array_reverse($namespaceParts));
    }
}
