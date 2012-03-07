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

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An authentication token used for simple password authentication.
 */
class PasswordToken extends \TYPO3\FLOW3\Security\Authentication\Token\AbstractToken {

	/**
	 * The password credentials
	 * @var array
	 * @FLOW3\Transient
	 */
	protected $credentials = array('password' => '');

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 * @FLOW3\Inject
	 */
	protected $environment;

	/**
	 * Updates the password credential from the POST vars, if the POST parameters
	 * are available. Sets the authentication status to AUTHENTICATION_NEEDED, if credentials have been sent.
	 *
	 * Note: You need to send the password in this POST parameter:
	 *       __authentication[TYPO3][FLOW3][Security][Authentication][Token][PasswordToken][password]
	 *
	 * @param \TYPO3\FLOW3\Http\Request $request The original HTTP request
	 * @return void
	 */
	public function updateCredentials(\TYPO3\FLOW3\Http\Request $request) {
		if ($request->getMethod() !== 'POST') {
			return;
		}

		$postArguments = $request->getArguments();
		$password = \TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($postArguments, '__authentication.TYPO3.FLOW3.Security.Authentication.Token.PasswordToken.password');

		if (!empty($password)) {
			$this->credentials['password'] = $password;
			$this->setAuthenticationStatus(self::AUTHENTICATION_NEEDED);
		}
	}

	/**
	 * Returns a string representation of the token for logging purposes.
	 *
	 * @return string
	 */
	public function  __toString() {
		return 'Password token';
	}

}
?>