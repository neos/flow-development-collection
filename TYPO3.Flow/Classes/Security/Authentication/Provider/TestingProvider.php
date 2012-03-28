<?php
namespace TYPO3\FLOW3\Security\Authentication\Provider;

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
 * A singleton authentication provider for functional tests with
 * mockable authentication.
 *
 * @FLOW3\Scope("singleton")
 */
class TestingProvider extends \TYPO3\FLOW3\Security\Authentication\Provider\AbstractProvider {

	/**
	 * @var \TYPO3\FLOW3\Security\Account
	 */
	protected $account;

	/**
	 * @var integer
	 */
	protected $authenticationStatus = \TYPO3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN;

	/**
	 * Returns the class names of the tokens this provider can authenticate.
	 *
	 * @return array
	 */
	public function getTokenClassNames() {
		return array('TYPO3\FLOW3\Security\Authentication\Token\TestingToken');
	}

	/**
	 * Sets isAuthenticated to TRUE for all tokens.
	 *
	 * @param \TYPO3\FLOW3\Security\Authentication\TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 */
	public function authenticate(\TYPO3\FLOW3\Security\Authentication\TokenInterface $authenticationToken) {
		$authenticationToken->setAuthenticationStatus($this->authenticationStatus);
		if ($this->authenticationStatus === \TYPO3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL) {
			$authenticationToken->setAccount($this->account);
		} else {
			$authenticationToken->setAccount(NULL);
		}
	}

	/**
	 * Set the account that will be authenticated
	 *
	 * @param \TYPO3\FLOW3\Security\Account $account
	 * @return void
	 */
	public function setAccount($account) {
		$this->account = $account;
	}

	/**
	 * Set the authentication status for authentication
	 *
	 * @param int $authenticationStatus
	 * @return void
	 */
	public function setAuthenticationStatus($authenticationStatus) {
		$this->authenticationStatus = $authenticationStatus;
	}

	/**
	 * Set the provider name
	 *
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Reset the authentication status and account
	 *
	 * @return void
	 */
	public function reset() {
		$this->account = NULL;
		$this->authenticationStatus = \TYPO3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN;
	}

}
?>