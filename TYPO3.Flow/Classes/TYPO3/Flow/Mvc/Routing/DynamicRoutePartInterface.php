<?php
namespace TYPO3\Flow\Mvc\Routing;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Contract for Dynamic Route Parts
 *
 * @api
 */
interface DynamicRoutePartInterface extends \TYPO3\Flow\Mvc\Routing\RoutePartInterface
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
