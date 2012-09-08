<?php
namespace TYPO3\FLOW3\Mvc\Routing;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract for Dynamic Route Parts
 *
 * @api
 */
interface DynamicRoutePartInterface extends \TYPO3\FLOW3\Mvc\Routing\RoutePartInterface {

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
?>