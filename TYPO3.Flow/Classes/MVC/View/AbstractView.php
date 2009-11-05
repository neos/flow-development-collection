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
 * An abstract View
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
abstract class AbstractView implements \F3\FLOW3\MVC\View\ViewInterface {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface A reference to the Object Factory
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Package\ManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var \F3\FLOW3\Resource\ManagerInterface
	 */
	protected $resourceManager;

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\MVC\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var array view data collection.
	 * @see assign()
	 */
	protected $viewData = array();

	/**
	 * Constructs the view.
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory A reference to the Object Factory
	 * @param \F3\FLOW3\Package\ManagerInterface $packageManager A reference to the Package Manager
	 * @param \F3\FLOW3\Resource\Manager $resourceManager A reference to the Resource Manager
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager A reference to the Object Manager
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\FactoryInterface $objectFactory, \F3\FLOW3\Package\ManagerInterface $packageManager, \F3\FLOW3\Resource\Manager $resourceManager, \F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectFactory = $objectFactory;
		$this->objectManager = $objectManager;
		$this->packageManager = $packageManager;
		$this->resourceManager = $resourceManager;
	}

	/**
	 * Sets the current controller context
	 *
	 * @param \F3\FLOW3\MVC\Controller\ControllerContext $controllerContext
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerContext(\F3\FLOW3\MVC\Controller\ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	/**
	 * Initializes the view after all dependencies have been injected
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->initializeView();
	}

	/**
	 * Add a variable to $this->viewData.
	 * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible,
	 *
	 * @param string $key Key of variable
	 * @param object $value Value of object
	 * @return \F3\FLOW3\MVC\View\ViewInterface an instance of $this, to enable chaining.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function assign($key, $value) {
		$this->viewData[$key] = $value;
		return $this;
	}

	/**
	 * Add multiple variables to $this->viewData.
	 *
	 * @param array $values array in the format array(key1 => value1, key2 => value2).
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function assignMultiple(array $values) {
		foreach($values as $key => $value) {
			$this->assign($key, $value);
		}
	}

	/**
	 * Initializes this view.
	 *
	 * Override this method for initializing your concrete view implementation.
	 *
	 * @return void
	 * @api
	 */
	protected function initializeView() {
	}

}

?>