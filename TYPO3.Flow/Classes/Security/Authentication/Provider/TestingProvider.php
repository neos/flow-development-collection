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
class TestingProvider implements \TYPO3\FLOW3\Security\Authentication\AuthenticationProviderInterface {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var \TYPO3\FLOW3\Security\Account
	 */
	protected $account;

	/**
	 * @var int
	 */
	protected $authenticationStatus = \TYPO3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN;

	/**
	 * Constructor
	 *
	 * @param string $name The name of this authentication provider
	 * @param array $options Additional configuration options
	 * @return void
	 */
	public function __construct($name, array $options = array()) {
		$this->name = $name;
	}

	/**
	 * Returns TRUE if the given token can be authenticated by this provider
	 *
	 * @param \TYPO3\FLOW3\Security\Authentication\TokenInterface $authenticationToken The token that should be authenticated
	 * @return boolean TRUE if the given token class can be authenticated by this provider
	 */
	public function canAuthenticate(\TYPO3\FLOW3\Security\Authentication\TokenInterface $authenticationToken) {
		if ($authenticationToken->getAuthenticationProviderName() === $this->name) return TRUE;
		return FALSE;
	}

	/**
	 * Returns the classnames of the tokens this provider is responsible for.
	 *
	 * @return string The classname of the token this provider is responsible for
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