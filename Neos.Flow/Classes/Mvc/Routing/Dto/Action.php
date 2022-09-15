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

#[Flow\Proxy(false)]
final class Action
{
    private function __construct(
        public readonly string $actionName,
        public readonly string $controllerName,
        public readonly string $packageKey,
        /** @deprecated with Flow 8.2 - The use of subpackage keys is discouraged and only supported for backwards compatibility */
        public readonly ?string $subpackageKey,
    ) {}

    public static function create(string $packageKey, string $controllerName, string $actionName): self
    {
        return new self($actionName, $controllerName, $packageKey, null);
    }

    public static function fromActionRequest(ActionRequest $request): self
    {
        return new self($request->getControllerActionName(), $request->getControllerName(), $request->getControllerPackageKey(), $request->getControllerSubpackageKey());
    }

    /**
     * @deprecated with Flow 8.2 - The use of subpackage keys is discouraged and only supported for backwards compatibility
     */
    public function withSubpackageKey(string $subpackageKey): self
    {
        if ($subpackageKey === $this->subpackageKey) {
            return $this;
        }
        return new self($this->actionName, $this->controllerName, $this->packageKey, $subpackageKey);
    }

    public function withActionName(string $actionName): self
    {
        if ($actionName === $this->actionName) {
            return $this;
        }
        return new self($actionName, $this->controllerName, $this->packageKey, $this->subpackageKey);
    }

    public function toRouteValues(): array
    {
        $routeValues['@action'] = strtolower($this->actionName);
        $routeValues['@controller'] = strtolower($this->controllerName);
        $routeValues['@package'] = strtolower($this->packageKey);
        if ($this->subpackageKey !== null) {
            $routeValues['@subpackage'] = strtolower($this->subpackageKey);
        }
        return $routeValues;
    }
}
