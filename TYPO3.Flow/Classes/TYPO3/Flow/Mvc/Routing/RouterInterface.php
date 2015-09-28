<?php
namespace TYPO3\Flow\Mvc\Routing;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Http\Request;

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
