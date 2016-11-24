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

/**
 * Contract for a Web Router
 */
interface RouterInterface
{
    /**
     * Iterates through all configured routes and calls matches() on them.
     * Returns the matchResults of the matching route or NULL if no matching
     * route could be found.
     *
     * @param Request $httpRequest
     * @return array The results of the matching route or NULL if no route matched
     */
    public function route(Request $httpRequest);

    /**
     * Walks through all configured routes and calls their respective resolves-method.
     * When a matching route is found, the corresponding URI is returned.
     *
     * @param array $routeValues
     * @return string URI
     */
    public function resolve(array $routeValues);
}
