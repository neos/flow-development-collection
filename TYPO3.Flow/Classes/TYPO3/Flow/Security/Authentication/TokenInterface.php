<?php
namespace TYPO3\Flow\Security\Authentication;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract for an authentication token.
 *
 */
interface TokenInterface {

	const
		NO_CREDENTIALS_GIVEN = 1,
		WRONG_CREDENTIALS = 2,
		AUTHENTICATION_SUCCESSFUL = 3,
		AUTHENTICATION_NEEDED = 4;

	/**
	 * Returns the name of the authentication provider responsible for this token
	 *
	 * @return string The authentication provider name
	 */
	public function getAuthenticationProviderName();

	/**
	 * Sets the name of the authentication provider responsible for this token
	 *
	 * @param string $authenticationProviderName The authentication provider name
	 * @return void
	 */
	public function setAuthenticationProviderName($authenticationProviderName);

	/**
	 * Returns TRUE if this token is currently authenticated
	 *
	 * @return boolean TRUE if this this token is currently authenticated
	 */
	public function isAuthenticated();

	/**
	 * Sets the authentication status. Usually called by the responsible \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 *
	 * @param integer $authenticationStatus One of NO_CREDENTIALS_GIVEN, WRONG_CREDENTIALS, AUTHENTICATION_SUCCESSFUL
	 * @return void
	 */
	public function setAuthenticationStatus($authenticationStatus);

	/**
	 * Returns the current authentication status
	 *
	 * @return integer One of NO_CREDENTIALS_GIVEN, WRONG_CREDENTIALS, AUTHENTICATION_SUCCESSFUL, REAUTHENTICATION_NEEDED
	 */
	public function getAuthenticationStatus();

	/**
	 * Sets the authentication entry point
	 *
	 * @param \TYPO3\Flow\Security\Authentication\EntryPointInterface $entryPoint The authentication entry point
	 * @return void
	 */
	public function setAuthenticationEntryPoint(\TYPO3\Flow\Security\Authentication\EntryPointInterface $entryPoint);

	/**
	 * Returns the configured authentication entry point, NULL if none is available
	 *
	 * @return \TYPO3\Flow\Security\Authentication\EntryPointInterface The configured authentication entry point, NULL if none is available
	 */
	public function getAuthenticationEntryPoint();

	/**
	 * Returns TRUE if \TYPO3\Flow\Security\RequestPattern were set
	 *
	 * @return boolean True if a \TYPO3\Flow\Security\RequestPattern was set
	 */
	public function hasRequestPatterns();

	/**
	 * Sets request patterns
	 *
	 * @param array $requestPatterns Array of \TYPO3\Flow\Security\RequestPattern to be set
	 * @return void
	 * @see hasRequestPattern()
	 */
	public function setRequestPatterns(array $requestPatterns);

	/**
	 * Returns an array of set \TYPO3\Flow\Security\RequestPatternInterface, NULL if none was set
	 *
	 * @return array Array of set request patterns
	 * @see hasRequestPattern()
	 */
	public function getRequestPatterns();

	/**
	 * Updates the authentication credentials, the authentication manager needs to authenticate this token.
	 * This could be a username/password from a login controller.
	 * This method is called while initializing the security context. By returning TRUE you
	 * make sure that the authentication manager will (re-)authenticate the tokens with the current credentials.
	 * Note: You should not persist the credentials!
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $actionRequest The current request instance
	 * @return boolean TRUE if this token needs to be (re-)authenticated
	 */
	public function updateCredentials(\TYPO3\Flow\Mvc\ActionRequest $actionRequest);

	/**
	 * Returns the credentials of this token. The type depends on the provider
	 * of the token.
	 *
	 * @return mixed $credentials The needed credentials to authenticate this token
	 */
	public function getCredentials();

	/**
	 * Returns the account if one is authenticated, NULL otherwise.
	 *
	 * @return \TYPO3\Flow\Security\Account An account object
	 */
	public function getAccount();

	/**
	 * Set the (authenticated) account
	 *
	 * @param \TYPO3\Flow\Security\Account $account An account object
	 * @return void
	 */
	public function setAccount(\TYPO3\Flow\Security\Account $account = NULL);

	/**
	 * Returns a string representation of the token for logging purposes.
	 *
	 * @return string A string representation of the token
	 */
	public function __toString();

}
?>