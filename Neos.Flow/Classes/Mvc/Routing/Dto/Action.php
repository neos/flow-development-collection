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

/**
 * @Flow\Proxy(false)
 */
final class Action
{
    private function __construct(
        private string $actionName,
        private string $controllerName,
        private string $packageKey,
        private string $format,
        private array $additionalArguments,
    ) {
    }

    public static function create(string $packageKey, string $controllerName, string $actionName): self
    {
        return new self($actionName, $controllerName, $packageKey, '', []);
    }

    public static function fromActionRequest(ActionRequest $request): self
    {
        return new self($request->getControllerActionName(), $request->getControllerName(), $request->getControllerPackageKey(), $request->getFormat(), []);
    }

    public function withAdditionalArguments(array $additionalArguments): self
    {
        if ($additionalArguments === $this->additionalArguments) {
            return $this;
        }
        return new self($this->actionName, $this->controllerName, $this->packageKey, $this->format, $additionalArguments);
    }

    public function withFormat(string $format): self
    {
        if ($format === $this->format) {
            return $this;
        }
        return new self($this->actionName, $this->controllerName, $this->packageKey, $format, $this->additionalArguments);
    }

    public function getActionName(): string
    {
        return $this->actionName;
    }

    public function getControllerName(): string
    {
        return $this->controllerName;
    }

    public function getPackageKey(): string
    {
        return $this->packageKey;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getAdditionalArguments(): array
    {
        return $this->additionalArguments;
    }

    public function toRouteValues(): array
    {
        $routeValues = $this->additionalArguments;
        $routeValues['@action'] = strtolower($this->actionName);
        $routeValues['@controller'] = strtolower($this->controllerName);
        $routeValues['@package'] = strtolower($this->packageKey);
        if ($this->format !== '') {
            $routeValues['@format'] = $this->format;
        }
        return $routeValues;
    }
}
