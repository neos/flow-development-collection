<?php
namespace TYPO3\FLOW3\Mvc\View;

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
 * An empty view - a special case.
 *
 */
final class EmptyView implements \TYPO3\FLOW3\Mvc\View\ViewInterface {

	/**
	 * Dummy method to satisfy the ViewInterface
	 *
	 * @param \TYPO3\FLOW3\Mvc\Controller\ControllerContext $controllerContext
	 * @return void
	 * @api
	 */
	public function setControllerContext(\TYPO3\FLOW3\Mvc\Controller\ControllerContext $controllerContext) {
	}

	/**
	 * Dummy method to satisfy the ViewInterface
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return \TYPO3\FLOW3\Mvc\View\EmptyView instance of $this to allow chaining
	 * @api
	 */
	public function assign($key, $value) {
		return $this;
	}

	/**
	 * Dummy method to satisfy the ViewInterface
	 *
	 * @param array $values
	 * @return \TYPO3\FLOW3\Mvc\View\EmptyView instance of $this to allow chaining
	 * @api
	 */
	public function assignMultiple(array $values) {
		return $this;
	}

	/**
	 * This view can be used in any case.
	 *
	 * @param \TYPO3\FLOW3\Mvc\Controller\ControllerContext $controllerContext
	 * @return boolean TRUE
	 * @api
	 */
	public function canRender(\TYPO3\FLOW3\Mvc\Controller\ControllerContext $controllerContext) {
		return TRUE;
	}

	/**
	 * Renders the empty view
	 *
	 * @return string An empty string
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
	 * @param string $methodName Name of the method
	 * @param array $arguments Arguments passed to the method
	 * @return void
	 */
	public function __call($methodName, array $arguments) {
	}
}
?>