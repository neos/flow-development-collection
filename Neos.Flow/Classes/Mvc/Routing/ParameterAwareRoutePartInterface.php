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
     * @param RouteParameters $parameters The Routing RouteParameters that can be registered via HTTP components
     * @return boolean true if Route Part matched $routePath, otherwise false.
     */
    public function matchWithParameters(&$routePath, RouteParameters $parameters);
}
