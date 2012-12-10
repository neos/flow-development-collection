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
 * A Special Case of a Controller: If no controller could be resolved this
 * controller is chosen.
 *
 * @Flow\Scope("singleton")
 * @deprecated since Flow 2.0. Use the "renderingGroups" options of the exception handler configuration instead
 */
class NotFoundController extends \TYPO3\Flow\Mvc\Controller\AbstractController implements \TYPO3\Flow\Mvc\Controller\NotFoundControllerInterface {

	/**
	 * @var \TYPO3\Flow\Mvc\View\NotFoundView
	 */
	protected $notFoundView;

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\Exception
	 */
	protected $exception;

	/**
	 * Injects the NotFoundView.
	 *
	 * @param \TYPO3\Flow\Mvc\View\NotFoundView $notFoundView
	 * @return void
	 * @api
	 */
	public function injectNotFoundView(\TYPO3\Flow\Mvc\View\NotFoundView $notFoundView) {
		$this->notFoundView = $notFoundView;
	}

	/**
	 * Sets the controller exception
	 *
	 * @param \TYPO3\Flow\Mvc\Controller\Exception $exception
	 * @return void
	 */
	public function setException(\TYPO3\Flow\Mvc\Controller\Exception $exception) {
		$this->exception = $exception;
	}

	/**
	 * Processes a generic request and fills the response with the default view
	 *
	 * @param \TYPO3\Flow\Mvc\RequestInterface $request The request object
	 * @param \TYPO3\Flow\Mvc\ResponseInterface $response The response, modified by this handler
	 * @return void
	 * @api
	 */
	public function processRequest(\TYPO3\Flow\Mvc\RequestInterface $request, \TYPO3\Flow\Mvc\ResponseInterface $response) {
		$this->initializeController($request, $response);
		$this->notFoundView->setControllerContext($this->controllerContext);
		if ($this->exception !== NULL) {
			$this->notFoundView->assign('errorMessage', $this->exception->getMessage());
		}
		switch (get_class($request)) {
			case 'TYPO3\Flow\Mvc\ActionRequest' :
				$response->setStatus(404);
				$response->setContent($this->notFoundView->render());
				break;
			default :
				$response->setContent("\nUnknown command\n\n");
		}
	}
}

?>