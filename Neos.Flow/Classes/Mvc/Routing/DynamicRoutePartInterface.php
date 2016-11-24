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

/**
 * Contract for Dynamic Route Parts
 *
 * @api
 */
interface DynamicRoutePartInterface extends RoutePartInterface
{
    /**
     * Sets split string of the Route Part.
     * The split string represents the border of a Dynamic Route Part.
     * If it is empty, Route Part will be equal to the remaining request path.
     *
     * @param string $splitString
     * @return void
     * @api
     */
    public function setSplitString($splitString);
}
