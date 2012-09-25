<?php
namespace TYPO3\Flow\Mvc\Routing;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Request;

/**
 * Contract for a Web Router
 *
 */
interface RouterInterface {

	/**
	 * Walks through all configured routes and calls their respective matches-method.
	 * When a corresponding route is found, package, controller, action and possible parameters
	 * are set on the $request object
	 *
	 * @param \TYPO3\Flow\Http\Request $httpRequest
	 * @return \TYPO3\Flow\Mvc\ActionRequest
	 */
	public function route(Request $httpRequest);

	/**
	 * Walks through all configured routes and calls their respective resolves-method.
	 * When a matching route is found, the corresponding URI is returned.
	 *
	 * @param array $routeValues
	 * @return string URI
	 */
	public function resolve(array $routeValues);

	/**
	 * Returns the object name of the controller defined by the package, subpackage key and
	 * controller name
	 *
	 * @param string $packageKey the package key of the controller
	 * @param string $subPackageKey the subpackage key of the controller
	 * @param string $controllerName the controller name excluding the "Controller" suffix
	 * @return string The controller's Object Name or NULL if the controller does not exist
	 */
	public function getControllerObjectName($packageKey, $subPackageKey, $controllerName);
}
?>