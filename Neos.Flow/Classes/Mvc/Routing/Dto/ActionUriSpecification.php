<?php
declare(strict_types=1);
namespace Neos\Flow\Mvc\Routing\Dto;

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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Utility\Arrays;
use Psr\Http\Message\UriInterface;

/**
 * @Flow\Proxy(false)
 */
final class ActionUriSpecification
{
    private function __construct(
        private string $actionName,
        private string $controllerName,
        private string $packageKey,
        /** @deprecated with Flow 8.3 - The use of subpackage keys is discouraged and only supported for backwards compatibility */
        private ?string $subpackageKey,
        private string $format,
        private array $routingArguments,
        private array $queryParameters,
    ) {
    }

    public static function create(string $packageKey, string $controllerName, string $actionName): self
    {
        return new self($actionName, $controllerName, $packageKey, null, '', [], []);
    }

    public static function fromActionRequest(ActionRequest $request): self
    {
        return new self(
            $request->getControllerActionName(),
            $request->getControllerName(),
            $request->getControllerPackageKey(),
            $request->getControllerSubpackageKey(),
            $request->getFormat(),
            [],
            []
        );
    }

    /**
     * @deprecated with Flow 8.3 - The use of subpackage keys is discouraged and only supported for backwards compatibility
     */
    public function withSubpackageKey(string $subpackageKey): self
    {
        if ($subpackageKey === $this->subpackageKey) {
            return $this;
        }
        return new self(
            $this->actionName,
            $this->controllerName,
            $this->packageKey,
            $subpackageKey,
            $this->format,
            $this->routingArguments,
            $this->queryParameters
        );
    }

    public function withRoutingArguments(array $routingArguments): self
    {
        if ($routingArguments === $this->routingArguments) {
            return $this;
        }
        return new self(
            $this->actionName,
            $this->controllerName,
            $this->packageKey,
            $this->subpackageKey,
            $this->format,
            $routingArguments,
            $this->queryParameters
        );
    }

    public function withQueryParameters(array $queryParameters): self
    {
        if ($queryParameters === $this->queryParameters) {
            return $this;
        }
        return new self(
            $this->actionName,
            $this->controllerName,
            $this->packageKey,
            $this->subpackageKey,
            $this->format,
            $this->routingArguments,
            $queryParameters
        );
    }

    public function withActionName(string $actionName): self
    {
        if ($actionName === $this->actionName) {
            return $this;
        }
        return new self(
            $actionName,
            $this->controllerName,
            $this->packageKey,
            $this->subpackageKey,
            $this->format,
            $this->routingArguments,
            $this->queryParameters
        );
    }


    public function withFormat(string $format): self
    {
        if ($format === $this->format) {
            return $this;
        }
        return new self(
            $this->actionName,
            $this->controllerName,
            $this->packageKey,
            $this->subpackageKey,
            $format,
            $this->routingArguments,
            $this->queryParameters
        );
    }

    /** @internal */
    public function mergeQueryParametersIntoUri(UriInterface $uri): UriInterface
    {
        if ($this->queryParameters === []) {
            return $uri;
        }
        if ($uri->getQuery() === "") {
            $mergedQuery = $this->queryParameters;
        } else {
            parse_str($uri->getQuery(), $queryParametersFromUri);
            $mergedQuery = Arrays::arrayMergeRecursiveOverrule($queryParametersFromUri, $this->queryParameters);
        }
        return $uri->withQuery(http_build_query($mergedQuery, '', '&'));
    }

    /** @internal */
    public function toRouteValues(): array
    {
        $routeValues = $this->routingArguments;
        $routeValues['@action'] = strtolower($this->actionName);
        $routeValues['@controller'] = strtolower($this->controllerName);
        $routeValues['@package'] = strtolower($this->packageKey);
        if ($this->subpackageKey !== null) {
            $routeValues['@subpackage'] = strtolower($this->subpackageKey);
        }
        if ($this->format !== '') {
            $routeValues['@format'] = $this->format;
        }
        return $routeValues;
    }
}
