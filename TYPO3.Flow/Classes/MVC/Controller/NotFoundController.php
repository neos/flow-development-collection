<?php
namespace TYPO3\FLOW3\MVC\Controller;

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
 * A Special Case of a Controller: If no controller could be resolved this
 * controller is chosen.
 *
 * @scope singleton
 * @api
 */
class NotFoundController extends \TYPO3\FLOW3\MVC\Controller\AbstractController implements \TYPO3\FLOW3\MVC\Controller\NotFoundControllerInterface {

	/**
	 * @var array
	 */
	protected $supportedRequestTypes = array('TYPO3\FLOW3\MVC\Web\Request', 'TYPO3\FLOW3\MVC\Cli\Request');

	/**
	 * @var \TYPO3\FLOW3\MVC\View\NotFoundView
	 */
	protected $notFoundView;

	/**
	 * @var \TYPO3\FLOW3\MVC\Controller\Exception
	 */
	protected $exception;

	/**
	 * Injects the NotFoundView.
	 *
	 * @param \TYPO3\FLOW3\MVC\View\NotFoundView $notFoundView
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function injectNotFoundView(\TYPO3\FLOW3\MVC\View\NotFoundView $notFoundView) {
		$this->notFoundView = $notFoundView;
	}

	/**
	 * Sets the controller exception
	 *
	 * @param \TYPO3\FLOW3\MVC\Controller\Exception $exception
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setException(\TYPO3\FLOW3\MVC\Controller\Exception $exception) {
		$this->exception = $exception;
	}

	/**
	 * Processes a generic request and fills the response with the default view
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The request
	 * @param \TYPO3\FLOW3\MVC\ResponseInterface $response The response
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function processRequest(\TYPO3\FLOW3\MVC\RequestInterface $request, \TYPO3\FLOW3\MVC\ResponseInterface $response) {
		parent::processRequest($request, $response);
		$this->notFoundView->setControllerContext($this->controllerContext);
		if ($this->exception !== NULL) {
			$this->notFoundView->assign('errorMessage', $this->exception->getMessage());
		}
		switch (get_class($request)) {
			case 'TYPO3\FLOW3\MVC\Web\Request' :
				$response->setStatus(404);
				$response->setContent($this->notFoundView->render());
				break;
			default :
				$response->setContent("\nUnknown command\n\n");
		}
	}
}

?>