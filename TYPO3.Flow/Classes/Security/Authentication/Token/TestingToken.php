<?php
namespace TYPO3\FLOW3\Security\Authentication\Token;

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
 * An authentication token used for functional tests
 */
class TestingToken extends \TYPO3\FLOW3\Security\Authentication\Token\AbstractToken {

	/**
	 * Simply sets the authentication status to AUTHENTICATION_NEEDED
	 *
	 * @param \TYPO3\FLOW3\Mvc\ActionRequest $actionRequest The current action request instance
	 * @return void
	 */
	public function updateCredentials(\TYPO3\FLOW3\Mvc\ActionRequest $actionRequest) {
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
?>