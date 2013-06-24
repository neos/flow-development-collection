<?php
namespace TYPO3\Flow\Mvc\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A Special Case of a Controller: If no controller has been specified in the
 * request, this controller is chosen.
 */
class StandardController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * Overrides the standard resolveView method
	 *
	 * @return \TYPO3\Flow\Mvc\View\ViewInterface $view The view
	 */
	protected function resolveView() {
		$view = new \TYPO3\Fluid\View\TemplateView();
		$view->setControllerContext($this->controllerContext);
		$view->setTemplatePathAndFilename(FLOW_PATH_FLOW . 'Resources/Private/Mvc/StandardView_Template.html');
		return $view;
	}

	/**
	 * Displays the default view
	 *
	 * @return string
	 */
	public function indexAction() {
		if (!$this->request instanceof \TYPO3\Flow\Mvc\ActionRequest) {
			return
				"\nWelcome to Flow!\n\n" .
				"This is the default view of the Flow MVC object. You see this message because no \n" .
				"other view is available. Please refer to the Developer's Guide for more information \n" .
				"how to create and configure one.\n\n" .
				"Have fun! The Flow Development Team\n";
		}
	}
}

?>