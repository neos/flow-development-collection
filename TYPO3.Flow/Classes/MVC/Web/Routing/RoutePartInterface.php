<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web\Routing;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Contract for all Route Parts
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
interface RoutePartInterface {

	/**
	 * Checks whether this Route Part corresponds to the given $requestPath.
	 * This method does not only check if the Route Part matches. It can also
	 * shorten the $requestPath by the matching substring when matching is successful.
	 * This is why $requestPath has to be passed by reference.
	 *
	 * @param string $requestPath The request path to be matched - without query parameters, host and fragment.
	 * @return boolean TRUE if Route Part matched $requestPath, otherwise FALSE.
	 */
	public function match(&$requestPath);

	/**
	 * Checks whether this Route Part corresponds to the given $routeValues.
	 * This method does not only check if the Route Part matches. It also
	 * removes resolved elements from $routeValues-Array.
	 * This is why $routeValues has to be passed by reference.
	 *
	 * @param array $routeValues An array with key/value pairs to be resolved by Dynamic Route Parts.
	 * @return boolean TRUE if Route Part can resolve one or more $routeValues elements, otherwise FALSE.
	 */
	public function resolve(array &$routeValues);
}
?>