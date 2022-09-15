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

#[Flow\Proxy(false)]
final class ActionUriSpecification
{
    private function __construct(
        private readonly Action $action,
        private readonly array $additionalArguments,
        private readonly string $format,
    ) {}

    public static function for(Action $action): self
    {
        return new self($action, [], 'html');
    }

    public function withAdditionalArguments(array $additionalArguments): self
    {
        if ($additionalArguments === $this->additionalArguments) {
            return $this;
        }
        return new self($this->action, $additionalArguments, $this->format);
    }

    public function withFormat(string $format): self
    {
        if ($format === $this->format) {
            return $this;
        }
        return new self($this->action, $this->additionalArguments, $this->format);
    }

    public function toRouteValues(): array
    {
        $routeValues = $this->additionalArguments;
        $routeValues = array_merge($routeValues, $this->action->toRouteValues());
        if ($this->format !== '') {
            $routeValues['@format'] = $this->format;
        }
        return $routeValues;
    }
}
