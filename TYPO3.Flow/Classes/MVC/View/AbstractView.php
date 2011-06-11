<?php
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
 * An abstract View
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
abstract class AbstractView implements \F3\FLOW3\MVC\View\ViewInterface {

	/**
	 * View variables and their values
	 * @var array
	 * @see assign()
	 */
	protected $variables = array();

	/**
	 * @var \F3\FLOW3\MVC\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * Add a variable to $this->variables.
	 * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
	 *
	 * @param string $key Key of variable
	 * @param object $value Value of object
	 * @return \F3\FLOW3\MVC\View\AbstractView an instance of $this, to enable chaining
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function assign($key, $value) {
		$this->variables[$key] = $value;
		return $this;
	}

	/**
	 * Add multiple variables to $this->variables.
	 *
	 * @param array $values array in the format array(key1 => value1, key2 => value2)
	 * @return \F3\FLOW3\MVC\View\AbstractView an instance of $this, to enable chaining
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function assignMultiple(array $values) {
		foreach($values as $key => $value) {
			$this->assign($key, $value);
		}
		return $this;
	}

	/**
	 * Sets the current controller context
	 *
	 * @param \F3\FLOW3\MVC\Controller\ControllerContext $controllerContext
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setControllerContext(\F3\FLOW3\MVC\Controller\ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	/**
	 * Tells if the view implementation can render the view for the given context.
	 *
	 * By default we assume that the view implementation can handle all kinds of
	 * contexts. Override this method if that is not the case.
	 *
	 * @param \F3\FLOW3\MVC\Controller\ControllerContext $controllerContext
	 * @return boolean TRUE if the view has something useful to display, otherwise FALSE
	 * @api
	 */
	public function canRender(\F3\FLOW3\MVC\Controller\ControllerContext $controllerContext) {
		return TRUE;
	}

}

?>
