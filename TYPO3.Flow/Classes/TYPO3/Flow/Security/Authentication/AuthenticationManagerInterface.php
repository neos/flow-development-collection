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
 * Contract for an authentication manager.
 *
 * Has to add a \TYPO3\Flow\Security\Authentication\TokenInterface to the security context
 * Might set a UserDetailsService, RequestPattern and AuthenticationEntryPoint (from configuration).
 */
interface AuthenticationManagerInterface {

	/**
	 * Returns the tokens this manager is responsible for.
	 * Note: The order of the tokens in the array is important, as the tokens will be authenticated in the given order.
	 *
	 * @return array Array of \TYPO3\Flow\Security\Authentication\TokenInterface An array of tokens this manager is responsible for
	 */
	public function getTokens();

	/**
	 * Sets the security context
	 *
	 * @param \TYPO3\Flow\Security\Context $securityContext The security context of the current request
	 * @return void
	 */
	public function setSecurityContext(\TYPO3\Flow\Security\Context $securityContext);

	/**
	 * Returns the security context
	 *
	 * @return \TYPO3\Flow\Security\Context $securityContext The security context of the current request
	 */
	public function getSecurityContext();

	/**
	 * Tries to authenticate the tokens in the security context, if needed.
	 * (Have a look at the \TYPO3\Flow\Security\Authentication\TokenManager for an implementation example)
	 *
	 * @return void
	 */
	public function authenticate();

	/**
	 * Checks if at least one token is authenticated
	 *
	 * @return boolean
	 */
	public function isAuthenticated();

	/**
	 * Logs all active authentication tokens out
	 *
	 * @return void
	 */
	public function logout();

}
?>