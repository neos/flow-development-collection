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
 * A URI Builder
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class URIBuilder {

	/**
	 * @var \F3\FLOW3\MVC\RequestInterface
	 */
	protected $request;

	/**
	 * @var \F3\FLOW3\MVC\Web\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * Sets the current request
	 * 
	 * @param \F3\FLOW3\MVC\RequestInterface $request
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function setRequest(\F3\FLOW3\MVC\RequestInterface $request) {
		$this->request = $request;
	}

	/**
	 * Injects the Router
	 *
	 * @param \F3\FLOW3\MVC\Web\Routing\RouterInterface $router
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function injectRouter(\F3\FLOW3\MVC\Web\Routing\RouterInterface $router) {
		$this->router = $router;
	}

	/**
	 * Creates an URI by making use of the Routers reverse routing mechanism.
	 *
	 * @param string $actionName Name of the action to be called
	 * @param array $arguments Additional arguments
	 * @param string $controllerName Name of the target controller. If not set, current controller is used
	 * @param string $packageKey Name of the target package. If not set, current package is used
	 * @param string $subpackageKey Name of the target subpackage. If not set, current subpackage is used
	 * @param string $section Anchor to be appended to the resulting URI
	 * @return string the resolved URI
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function URIFor($actionName = NULL, $arguments = array(), $controllerName = NULL, $packageKey = NULL, $subpackageKey = NULL, $section = '') {
		$routeValues = $arguments;
		if ($actionName !== NULL) {
			$routeValues['@action'] = $actionName;
		}
		if ($controllerName === NULL) {
			$controllerName = $this->request->getControllerName();
		}
		$routeValues['@controller'] = $controllerName;
		if ($packageKey === NULL) {
			$packageKey = $this->request->getControllerPackageKey();
		}
		$routeValues['@package'] = $packageKey;
		if (strlen($subpackageKey) === 0) {
			$subpackageKey = $this->request->getControllerSubpackageKey();
		}
		if (strlen($subpackageKey) > 0) {
			$routeValues['@subpackage'] = $subpackageKey;
		}
		$uri = $this->router->resolve($routeValues);
		if ($section !== '') {
			$uri .= '#' . $section;
		}
		return $uri;
	}
}

?>