<?php
namespace TYPO3\Flow\Security\Authentication\Token;

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
 * An authentication token used for functional tests
 */
class TestingToken extends AbstractToken implements SessionlessTokenInterface {

	/**
	 * Simply sets the authentication status to AUTHENTICATION_NEEDED
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $actionRequest The current action request instance
	 * @return void
	 */
	public function updateCredentials(\TYPO3\Flow\Mvc\ActionRequest $actionRequest) {
		$this->authenticationStatus = self::AUTHENTICATION_NEEDED;
	}

	/**
	 * Returns a string representation of the token for logging purposes.
	 *
	 * @return string The username credential
	 */
	public function  __toString() {
		return 'Testing token';
	}

}
