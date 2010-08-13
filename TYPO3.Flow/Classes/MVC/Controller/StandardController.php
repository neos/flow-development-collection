<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

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
 * A Special Case of a Controller: If no controller has been specified in the
 * request, this controller is chosen.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class StandardController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * Overrides the standard resolveView method
	 *
	 * @return \F3\FLOW3\MVC\View\ViewInterface $view The view
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveView() {
		$view = $this->objectManager->create('F3\Fluid\View\TemplateView');
		$view->setControllerContext($this->controllerContext);
		$view->setTemplatePathAndFilename(FLOW3_PATH_FLOW3 . 'Resources/Private/MVC/StandardView_Template.html');
		return $view;
	}

	/**
	 * Displays the default view
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function indexAction() {

		if (!$this->request instanceof \F3\FLOW3\MVC\Web\Request) {
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