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
use Neos\Flow\Mvc\Routing\Route;

/**
 * @Flow\Proxy(false)
 */
final class RouteResult
{

    /**
     * @var Route
     */
    private $matchedRoute;

    private function __construct(Route $matchedRoute)
    {
        $this->matchedRoute = $matchedRoute;
    }

    public static function fromMatchedRoute(Route $matchedRoute): self
    {
        return new static($matchedRoute);
    }

    public function getRouteValues(): array
    {
        return $this->matchedRoute->getMatchResults();
    }
}
