<?php

declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 */

/**
 * Contract for an authentication token.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_Security_Authentication_TokenInterface {

	/**
	 * Returns TRUE if this token is currently authenticated
	 *
	 * @return boolean TRUE if this this token is currently authenticated
	 */
	public function isAuthenticated();

	/**
	 * Returns TRUE if a F3_FLOW3_Security_RequestPattern was set
	 *
	 * @return boolean True if a F3_FLOW3_Security_RequestPattern was set
	 */
	public function hasRequestPattern();

	/**
	 * Sets a F3_FLOW3_Security_RequestPattern
	 *
	 * @param F3_FLOW3_Security_RequestPattern $requestPattern The set request pattern
	 * @return void
	 * @see hasRequestPattern()
	 */
	public function setRequestPattern(F3_FLOW3_Security_RequestPatternInterface $requestPattern);

	/**
	 * Returns the set F3_FLOW3_Security_RequestPatternInterface, NULL if none was set
	 *
	 * @return F3_FLOW3_Security_RequestPatternInterface The set request pattern
	 * @see hasRequestPattern()
	 */
	public function getRequestPattern();

	/**
	 * Updates the authentication credentials, the authentication manager needs to authenticate this token.
	 * This could be a username/password from a login controller.
	 * This method is called while initializing the security context.
	 *
	 * @return void
	 */
	public function updateCredentials();

	/**
	 * Returns the credentials of this token.
	 *
	 * @return object $credentials The needed credentials to authenticate this token
	 */
	public function getCredentials();

	/**
	 * Might ask a F3_FLOW3_Security_Authentication_UserDetailsServiceInterface.
	 *
	 * @return F3_FLOW3_Security_Authentication_UserDetailsInterface A user details object
	 */
	public function getUserDetails();

	/**
	 * Returns the currently valid granted authorities. It might ask a F3_FLOW3_Security_Authentication_UserDetailsServiceInterface.
	 * Note: You have to check isAuthenticated() before you call this method
	 *
	 * @return array Array of F3_FLOW3_Security_Authentication_GrantedAuthority objects
	 */
	public function getGrantedAuthorities();

	/**
	 * Sets the authentication status. Usually called by the responsible F3_FLOW3_Security_Authentication_ManagerInterface
	 *
	 * @param boolean $authenticationStatus TRUE if the token ist authenticated, FALSE otherwise
	 * @return void
	 */
	public function setAuthenticationStatus($authenticationStatus);
}

?>