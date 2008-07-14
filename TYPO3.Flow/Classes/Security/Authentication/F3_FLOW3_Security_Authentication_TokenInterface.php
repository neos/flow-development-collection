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

//TODO: all parameters should be configured through configuration
	/**
	 * Constructor.
	 *
	 * @param F3_FLOW3_Security_Authentication_UserDetailsServiceInterface A user details service
	 * @param F3_FLOW3_Security_RequestPattern $requestPattern This patterns defines the specific requests, this token is only valid for
	 * @param F3_FLOW3_Security_Authentication_EntryPointInterface $authenticationEntryPoint If set the authentication manager automatically calls the entry point when authenticating this token
	 * @return void
	 */
	public function __construct(
			F3_FLOW3_Security_Authentication_UserDetailsServiceInterface $userDetailsService = NULL,
			F3_FLOW3_Security_RequestPattern $requestPattern = NULL,
			F3_FLOW3_Security_Authentication_EntryPointInterface $authenticationEntryPoint = NULL
			);

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
	 * Returns the set F3_FLOW3_Security_RequestPattern, NULL if none was set
	 *
	 * @return F3_FLOW3_Security_RequestPattern The set request pattern
	 * @see hasRequestPattern()
	 */
	public function getRequestPattern();

//TODO: this method should be called while initialzing the security context
	/**
	 * Sets the authentication credentials, the authentication manager needs to authenticate this token.
	 * This could be a username/password from a login controller. It also could be empty if no special
	 * credentials are needed for authentication (e.g. HTTP Basic authentication).
	 *
	 * @param object $credentials The needed credentials to authenticate this token
	 * @return void
	 */
	public function setCredentials($credentials);

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