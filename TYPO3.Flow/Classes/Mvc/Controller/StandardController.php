<?php
namespace TYPO3\FLOW3\Mvc\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A Special Case of a Controller: If no controller has been specified in the
 * request, this controller is chosen.
 *
 * @FLOW3\Scope("singleton")
 */
class StandardController extends \TYPO3\FLOW3\Mvc\Controller\ActionController {

	/**
	 * Overrides the standard resolveView method
	 *
	 * @return \TYPO3\FLOW3\Mvc\View\ViewInterface $view The view
	 */
	protected function resolveView() {
		$view = new \TYPO3\Fluid\View\TemplateView();
		$view->setControllerContext($this->controllerContext);
		$view->setTemplatePathAndFilename(FLOW3_PATH_FLOW3 . 'Resources/Private/Mvc/StandardView_Template.html');
		return $view;
	}

	/**
	 * Displays the default view
	 *
	 * @return string
	 */
	public function indexAction() {

		if (!$this->request instanceof \TYPO3\FLOW3\Mvc\ActionRequest) {
			return
				"\nWelcome to FLOW3!\n\n" .
				"This is the default view of the FLOW3 MVC object. You see this message because no \n" .
				"other view is available. Please refer to the Developer's Guide for more information \n" .
				"how to create and configure one.\n\n" .
				"Have fun! The FLOW3 Development Team\n";
		}
	}
}

?>