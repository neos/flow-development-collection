<?php
namespace TYPO3\Flow\Mvc\View;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * An abstract View
 *
 * @api
 */
abstract class AbstractView implements \TYPO3\Flow\Mvc\View\ViewInterface {

	/**
	 * View variables and their values
	 * @var array
	 * @see assign()
	 */
	protected $variables = array();

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * Add a variable to $this->variables.
	 * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
	 *
	 * @param string $key Key of variable
	 * @param mixed $value Value of object
	 * @return \TYPO3\Flow\Mvc\View\AbstractView an instance of $this, to enable chaining
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
	 * @return \TYPO3\Flow\Mvc\View\AbstractView an instance of $this, to enable chaining
	 * @api
	 */
	public function assignMultiple(array $values) {
		foreach ($values as $key => $value) {
			$this->assign($key, $value);
		}
		return $this;
	}

	/**
	 * Sets the current controller context
	 *
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
	 * @return void
	 * @api
	 */
	public function setControllerContext(\TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	/**
	 * Tells if the view implementation can render the view for the given context.
	 *
	 * By default we assume that the view implementation can handle all kinds of
	 * contexts. Override this method if that is not the case.
	 *
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
	 * @return boolean TRUE if the view has something useful to display, otherwise FALSE
	 * @api
	 */
	public function canRender(\TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
		return TRUE;
	}

}

?>
