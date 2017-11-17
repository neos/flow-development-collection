<?php
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
use Neos\Flow\Http\Request as HttpRequest;

/**
 * @Flow\Proxy(false)
 */
final class ResolveContext
{

    /**
     * @var HttpRequest
     */
    private $httpRequest;

    /**
     * @var array
     */
    private $routeValues;

    /**
     * @var bool
     */
    private $forceAbsoluteUri;

    /**
     * @var string
     */
    private $section;

    /**
     * @var string
     */
    private $uriPrefix;

    public function __construct(HttpRequest $httpRequest, array $routeValues, bool $forceAbsoluteUri = false, string $section = '', string $uriPrefix = '')
    {
        $this->httpRequest = $httpRequest;
        $this->routeValues = $routeValues;
        $this->forceAbsoluteUri = $forceAbsoluteUri;
        $this->section = $section;
        $this->uriPrefix = $uriPrefix;
    }

    public function getHttpRequest(): HttpRequest
    {
        return $this->httpRequest;
    }

    public function getRouteValues(): array
    {
        return $this->routeValues;
    }

    public function isForceAbsoluteUri(): bool
    {
        return $this->forceAbsoluteUri;
    }

    public function hasSection(): bool
    {
        return $this->section !== '';
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function hasUriPrefix(): bool
    {
        return $this->uriPrefix !== '';
    }

    public function getUriPrefix(): string
    {
        return $this->uriPrefix;
    }

}
