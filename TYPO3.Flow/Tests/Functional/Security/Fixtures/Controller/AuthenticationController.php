<?php
namespace TYPO3\FLOW3\Tests\Functional\Security\Fixtures\Controller;

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
 * A controller for functional testing
 */
class AuthenticationController extends \TYPO3\FLOW3\Security\Authentication\Controller\AbstractAuthenticationController {

	/**
	 * @param \TYPO3\FLOW3\Mvc\ActionRequest $originalRequest
	 * @return string
	 */
	public function onAuthenticationSuccess(\TYPO3\FLOW3\Mvc\ActionRequest $originalRequest = NULL) {
		if ($originalRequest !== NULL) {
			$this->redirectToRequest($originalRequest);
		}
		return 'Authentication Success returned!';
	}

	/**
	 * @param \TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException $exception
	 * @throws \TYPO3\FLOW3\Exception
	 */
	public function onAuthenticationFailure(\TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException $exception = NULL) {
		throw new \TYPO3\FLOW3\Exception('Failure Method Exception', 42);
	}
}
?>