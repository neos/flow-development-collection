<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\View;

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
 *
 * @version $Id$
 */

/**
 * An empty view - a special case.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
final class EmptyView implements \F3\FLOW3\MVC\View\ViewInterface {

	/**
	 * Dummy method to satisfy the ViewInterface
	 *
	 * @param \F3\FLOW3\MVC\Controller\ControllerContext $controllerContext
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setControllerContext(\F3\FLOW3\MVC\Controller\ControllerContext $controllerContext) {
	}

	/**
	 * Dummy method to satisfy the ViewInterface
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function assign($key, $value) {
	}

	/**
	 * Dummy method to satisfy the ViewInterface
	 *
	 * @param array $values
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function assignMultiple(array $values) {
	}

	/**
	 * Renders the empty view
	 *
	 * @return string An empty string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		return '';
	}

	/**
	 * A magic call method.
	 *
	 * Because this empty view is used as a Special Case in situations when no matching
	 * view is available, it must be able to handle method calls which originally were
	 * directed to another type of view. This magic method should prevent PHP from issuing
	 * a fatal error.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __call($methodName, array $arguments) {
	}
}
?>