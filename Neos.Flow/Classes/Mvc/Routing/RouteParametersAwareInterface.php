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
 * TODO document
 */
interface RouteParametersAwareInterface
{

    /**
     * @param Parameters $routeParameters
     * @return void
     */
    public function setRouteParameters(Parameters $routeParameters);
}
