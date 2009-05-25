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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * A Special Case of a Controller: If no controller could be resolved this
 * controller is chosen.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NotFoundController extends \F3\FLOW3\MVC\Controller\AbstractController {

	/**
	 * @var \F3\FLOW3\MVC\View\NotFoundView
	 */
	protected $notFoundView;

	/**
	 * Injects the NotFoundView.
	 *
	 * @param \F3\FLOW3\MVC\View\NotFoundView $notFoundView
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectNotFoundView(\F3\FLOW3\MVC\View\NotFoundView $notFoundView) {
		$this->notFoundView = $notFoundView;
	}

	/**
	 * Processes a generic request and fills the response with the default view
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request
	 * @param \F3\FLOW3\MVC\ResponseInterface $response The response
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function processRequest(\F3\FLOW3\MVC\RequestInterface $request, \F3\FLOW3\MVC\ResponseInterface $response) {
		parent::processRequest($request, $response);
		$this->initializeView();
		switch (get_class($request)) {
			case 'F3\FLOW3\MVC\Web\Request' :
				$response->setStatus(404);
				$response->setContent($this->notFoundView->render());
				break;
			default :
				$response->setContent(
					"\n404 Not Found\n\n" .
					"No controller could be resolved which would match your request.\n"
				);
		}
	}

	/**
	 * Initialize the view
	 */
	protected function initializeView() {
		$this->notFoundView->setControllerContext($this->buildControllerContext());
	}
}

?>