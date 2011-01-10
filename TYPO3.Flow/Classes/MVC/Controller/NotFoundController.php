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
 * A Special Case of a Controller: If no controller could be resolved this
 * controller is chosen.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class NotFoundController extends \F3\FLOW3\MVC\Controller\AbstractController implements \F3\FLOW3\MVC\Controller\NotFoundControllerInterface {

	/**
	 * @var array
	 */
	protected $supportedRequestTypes = array('F3\FLOW3\MVC\Web\Request', 'F3\FLOW3\MVC\Cli\Request');

	/**
	 * @var \F3\FLOW3\MVC\View\NotFoundView
	 */
	protected $notFoundView;

	/**
	 * @var \F3\FLOW3\MVC\Controller\Exception
	 */
	protected $exception;

	/**
	 * Injects the NotFoundView.
	 *
	 * @param \F3\FLOW3\MVC\View\NotFoundView $notFoundView
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function injectNotFoundView(\F3\FLOW3\MVC\View\NotFoundView $notFoundView) {
		$this->notFoundView = $notFoundView;
	}

	/**
	 * Sets the controller exception
	 *
	 * @param \F3\FLOW3\MVC\Controller\Exception $exception
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setException(\F3\FLOW3\MVC\Controller\Exception $exception) {
		$this->exception = $exception;
	}

	/**
	 * Processes a generic request and fills the response with the default view
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request
	 * @param \F3\FLOW3\MVC\ResponseInterface $response The response
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function processRequest(\F3\FLOW3\MVC\RequestInterface $request, \F3\FLOW3\MVC\ResponseInterface $response) {
		parent::processRequest($request, $response);
		$this->notFoundView->setControllerContext($this->controllerContext);
		if ($this->exception !== NULL) {
			$this->notFoundView->assign('errorMessage', $this->exception->getMessage());
		}
		switch (get_class($request)) {
			case 'F3\FLOW3\MVC\Web\Request' :
				$response->setStatus(404);
				$response->setContent($this->notFoundView->render());
				break;
			default :
				$response->setContent(
					"\n404 Not Found\n\n" .
					$this->exception->getMessage() . "\n"
				);
		}
	}
}

?>