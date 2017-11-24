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

use Neos\Flow\Mvc\Routing\Dto\Parameters;

/**
 * Contract for Route parts that are aware of routing parameters
 *
 * @api
 */
interface ParameterAwareRoutePartInterface extends RoutePartInterface
{

    /**
     * @param string &$routePath The request path to be matched - without query parameters, host and fragment.
     * @param Parameters $parameters
     * @return boolean TRUE if Route Part matched $routePath, otherwise FALSE.
     */
    public function matchWithParameters(&$routePath, Parameters $parameters);
}
