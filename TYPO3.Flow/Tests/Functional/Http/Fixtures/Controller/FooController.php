<?php
namespace TYPO3\FLOW3\Tests\Functional\Http\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

class FooController extends \TYPO3\FLOW3\Mvc\Controller\AbstractController {

	/**
	 * Process Request
	 *
	 * @param \TYPO3\FLOW3\Mvc\RequestInterface $request
	 * @param \TYPO3\FLOW3\Mvc\ResponseInterface $response
	 * @return void
	 */
	public function processRequest(\TYPO3\FLOW3\Mvc\RequestInterface $request, \TYPO3\FLOW3\Mvc\ResponseInterface $response) {
		$this->initializeController($request, $response);
		$response->appendContent('FooController responded');
	}
}
?>
