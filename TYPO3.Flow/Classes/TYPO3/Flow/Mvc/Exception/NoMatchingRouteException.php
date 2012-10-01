<?php
namespace TYPO3\FLOW3\Mvc\Exception;

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
 * An "no matching route" exception that is thrown if the router could not
 * find a route that matches/resolves the given uri pattern/route values
 *
 * @api
 */
class NoMatchingRouteException extends \TYPO3\FLOW3\Mvc\Exception {

	/**
	 * @var integer
	 */
	protected $statusCode = 404;

}

?>