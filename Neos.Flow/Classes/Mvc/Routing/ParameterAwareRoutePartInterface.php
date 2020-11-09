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

use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;

/**
 * Contract for Route parts that are aware of Routing RouteParameters
 *
 * This extends the RoutePartInterface by a new method that allows Routing RouteParameters to be passed in when
 * matching an incoming request.
 *
 * @api
 */
interface ParameterAwareRoutePartInterface extends RoutePartInterface
{

    /**
     * @param string &$routePath The request path to be matched - without query parameters, host and fragment.
     * @param RouteParameters $parameters The Routing RouteParameters that can be registered via HTTP middleware
     * @return boolean true if Route Part matched $routePath, otherwise false.
     */
    public function matchWithParameters(&$routePath, RouteParameters $parameters);

    /**
     * Checks whether this Route Part corresponds to the given $routeValues.
     * This method does not only check if the Route Part matches. It also
     * removes resolved elements from $routeValues-Array.
     * This is why $routeValues has to be passed by reference.
     *
     * @param array &$routeValues An array with key/value pairs to be resolved by Dynamic Route Parts.
     * @param RouteParameters $parameters The Routing RouteParameters that can be registered via HTTP middleware
     * @return bool|ResolveResult true or an instance of ResolveResult if Route Part can resolve one or more $routeValues elements, otherwise false.
     */
    public function resolveWithParameters(array &$routeValues, RouteParameters $parameters);
}
