<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Contract for a Route
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_MVC_Web_Routing_RouteInterface {

	/**
	 * Sets default values for a Route.
	 * 
	 * @param array $defaults
	 * @return void
	 */
	public function setDefaults($defaults);

	/**
	 * Sets the URL pattern for this Route.
	 * e.g. "static/[dynamic]/[[subRoute]]"
	 * 
	 * @param string $urlPattern
	 * @return void
	 */
	public function setUrlPattern($urlPattern);

	/**
	 * Returns an array with the Route values.
	 * 
	 * @return array
	 */
	public function getValues();

	/**
	 * Checks whether a rout corresponds to the given $requestPath
	 * 
	 * @param string $requestPath
	 * @return boolean
	 */
	public function match($requestPath);

}
?>