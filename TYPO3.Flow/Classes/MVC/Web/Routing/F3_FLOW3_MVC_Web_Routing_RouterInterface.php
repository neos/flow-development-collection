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
 * @version $Id:F3_FLOW3_MVC_Web_RequestHandler.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Contract for a Web Router
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Web_RequestHandler.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_MVC_Web_Routing_RouterInterface {

	/**
	 * Walks through all configured routes and calls their respective matches-method.
	 * When a corresponding route is found, package, controller, action and possible parameters
	 * are set on the $request object
	 *
	 * @param F3_FLOW3_MVC_Web_Request $request
	 * @return boolean
	 */
	public function route(F3_FLOW3_MVC_Web_Request $request);

	/**
	 * Walks through all configured routes and calls their respective resolves-method.
	 * When a matching route is found, the corresponding url path is returned.
	 *
	 * @param array $routeValues
	 * @return string url path
	 */
	public function resolve(array $routeValues);
}
?>