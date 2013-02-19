<?php
namespace TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller;

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
 * A controller for functional testing of the HttpBasic Authentication provider & token
 */
class HttpBasicTestController extends \TYPO3\Flow\Security\Authentication\Controller\AbstractAuthenticationController {

	/**
	 * @param \TYPO3\Flow\Mvc\ActionRequest $originalRequest
	 * @return string
	 */
	public function onAuthenticationSuccess(\TYPO3\Flow\Mvc\ActionRequest $originalRequest = NULL) {
		if ($originalRequest !== NULL) {
			$this->redirectToRequest($originalRequest);
		}
		return 'Authentication Success returned!';
	}

	/**
	 * @param \TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception
	 * @throws \TYPO3\Flow\Exception
	 */
	public function onAuthenticationFailure(\TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception = NULL) {
		throw new \TYPO3\Flow\Exception('Failure Method Exception', 42);
	}
}
?>