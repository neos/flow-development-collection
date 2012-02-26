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
 * An abstract authentication token.
 */
abstract class AbstractToken implements \TYPO3\FLOW3\Security\Authentication\TokenInterface {

	/**
	 * @var string
	 */
	protected $authenticationProviderName;

	/**
	 * Current authentication status of this token
	 * @var integer
	 */
	protected $authenticationStatus = self::NO_CREDENTIALS_GIVEN;

	/**
	 * The credentials submitted by the client
	 * @var array
	 * @FLOW3\Transient
	 */
	protected $credentials = array();

	/**
	 * @var \TYPO3\FLOW3\Security\Account
	 */
	protected $account;

	/**
	 * @var array
	 */
	protected $requestPatterns = NULL;

	/**
	 * The authentication entry point
	 * @var \TYPO3\FLOW3\Security\Authentication\EntryPointInterface
	 */
	protected $entryPoint = NULL;

	/**
	 * Returns the name of the authentication provider responsible for this token
	 *
	 * @return string The authentication provider name
	 */
	public function getAuthenticationProviderName() {
		return $this->authenticationProviderName;
	}

	/**
	 * Sets the name of the authentication provider responsible for this token
	 *
	 * @param string $authenticationProviderName The authentication provider name
	 * @return void
	 */
	public function setAuthenticationProviderName($authenticationProviderName) {
		$this->authenticationProviderName = $authenticationProviderName;
	}

	/**
	 * Returns TRUE if this token is currently authenticated
	 *
	 * @return boolean TRUE if this this token is currently authenticated
	 */
	public function isAuthenticated() {
		return ($this->authenticationStatus === self::AUTHENTICATION_SUCCESSFUL);
	}

	/**
	 * Sets the authentication entry point
	 *
	 * @param \TYPO3\FLOW3\Security\Authentication\EntryPointInterface $entryPoint The authentication entry point
	 * @return void
	 */
	public function setAuthenticationEntryPoint(\TYPO3\FLOW3\Security\Authentication\EntryPointInterface $entryPoint) {
		$this->entryPoint = $entryPoint;
	}

	/**
	 * Returns the configured authentication entry point, NULL if none is available
	 *
	 * @return \TYPO3\FLOW3\Security\Authentication\EntryPointInterface The configured authentication entry point, NULL if none is available
	 */
	public function getAuthenticationEntryPoint() {
		return $this->entryPoint;
	}

	/**
	 * Returns TRUE if \TYPO3\FLOW3\Security\RequestPattern were set
	 *
	 * @return boolean True if a \TYPO3\FLOW3\Security\RequestPatternInterface was set
	 */
	public function hasRequestPatterns() {
		if ($this->requestPatterns != NULL) return TRUE;
		return FALSE;
	}

	/**
	 * Sets request patterns
	 *
	 * @param array $requestPatterns Array of \TYPO3\FLOW3\Security\RequestPattern to be set
	 * @return void
	 * @see hasRequestPattern()
	 */
	public function setRequestPatterns(array $requestPatterns) {
		$this->requestPatterns = $requestPatterns;
	}

	/**
	 * Returns an array of set \TYPO3\FLOW3\Security\RequestPatternInterface, NULL if none was set
	 *
	 * @return array Array of set request patterns
	 * @see hasRequestPattern()
	 */
	public function getRequestPatterns() {
		return $this->requestPatterns;
	}

	/**
	 * Returns the credentials (username and password) of this token.
	 *
	 * @return object $credentials The needed credentials to authenticate this token
	 */
	public function getCredentials() {
		return $this->credentials;
	}

	/**
	 * Returns the account if one is authenticated, NULL otherwise.
	 *
	 * @return \TYPO3\FLOW3\Security\Account An account object
	 */
	public function getAccount() {
		return $this->account;
	}

	/**
	 * Set the (authenticated) account
	 *
	 * @param \TYPO3\FLOW3\Security\Account $account An account object
	 * @return void
	 */
	public function setAccount(\TYPO3\FLOW3\Security\Account $account = NULL) {
		$this->account = $account;
	}

	/**
	 * Returns the currently valid roles.
	 *
	 * @return array Array of TYPO3\FLOW3\Security\Authentication\Role objects
	 */
	public function getRoles() {
		$account = $this->getAccount();
		return ($account !== NULL && $this->isAuthenticated()) ? $account->getRoles() : array();
	}

	/**
	 * Sets the authentication status. Usually called by the responsible \TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface
	 *
	 * @param integer $authenticationStatus One of NO_CREDENTIALS_GIVEN, WRONG_CREDENTIALS, AUTHENTICATION_SUCCESSFUL, AUTHENTICATION_NEEDED
	 * @return void
	 * @throws \TYPO3\FLOW3\Security\Exception\InvalidAuthenticationStatusException
	 */
	public function setAuthenticationStatus($authenticationStatus) {
		if (!in_array($authenticationStatus, array(self::NO_CREDENTIALS_GIVEN, self::WRONG_CREDENTIALS, self::AUTHENTICATION_SUCCESSFUL, self::AUTHENTICATION_NEEDED))) {
			throw new \TYPO3\FLOW3\Security\Exception\InvalidAuthenticationStatusException('Invalid authentication status.', 1237224453);
		}
		$this->authenticationStatus = $authenticationStatus;
	}

	/**
	 * Returns the current authentication status
	 *
	 * @return integer One of NO_CREDENTIALS_GIVEN, WRONG_CREDENTIALS, AUTHENTICATION_SUCCESSFUL, AUTHENTICATION_NEEDED
	 */
	public function getAuthenticationStatus() {
		return $this->authenticationStatus;
	}

	/**
	 * Returns a string representation of the token for logging purposes.
	 *
	 * @return string The class name
	 */
	public function  __toString() {
		return get_class($this);
	}

}
?>