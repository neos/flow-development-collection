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
 * An authentication token used for simple username and password authentication.
 */
class UsernamePassword extends \TYPO3\FLOW3\Security\Authentication\Token\AbstractToken {

	/**
	 * The username/password credentials
	 * @var array
	 * @FLOW3\Transient
	 */
	protected $credentials = array('username' => '', 'password' => '');

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 * @FLOW3\Inject
	 */
	protected $environment;

	/**
	 * @var \TYPO3\FLOW3\Security\AccountRepository
	 * @FLOW3\Inject
	 */
	protected $accountRepository;

	/**
	 * Updates the username and password credentials from the POST vars, if the POST parameters
	 * are available. Sets the authentication status to REAUTHENTICATION_NEEDED, if credentials have been sent.
	 *
	 * Note: You need to send the username and password in these two POST parameters:
	 *       __authentication[TYPO3][FLOW3][Security][Authentication][Token][UsernamePassword][username]
	 *   and __authentication[TYPO3][FLOW3][Security][Authentication][Token][UsernamePassword][password]
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The current request instance
	 * @return void
	 */
	public function updateCredentials(\TYPO3\FLOW3\MVC\RequestInterface $request) {
		$postArguments = $this->environment->getRawPostArguments();
		$username = \TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($postArguments, '__authentication.TYPO3.FLOW3.Security.Authentication.Token.UsernamePassword.username');
		$password = \TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($postArguments, '__authentication.TYPO3.FLOW3.Security.Authentication.Token.UsernamePassword.password');

		if (!empty($username) && !empty($password)) {
			$this->credentials['username'] = $username;
			$this->credentials['password'] = $password;

			$this->setAuthenticationStatus(self::AUTHENTICATION_NEEDED);
		}
	}

	/**
	 * Returns a string representation of the token for logging purposes.
	 *
	 * @return string The username credential
	 */
	public function  __toString() {
		return 'Username: "' . $this->credentials['username'] . '"';
	}

}
?>