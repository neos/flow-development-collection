<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 */

/**
 * Contract for an authentication token.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
interface TokenInterface {

	/**
	 * Returns TRUE if this token is currently authenticated
	 *
	 * @return boolean TRUE if this this token is currently authenticated
	 */
	public function isAuthenticated();

	/**
	 * Returns the configured authentication entry point, NULL if none is available
	 *
	 * @return \F3\FLOW3\Security\Authentication\EntryPoint The configured authentication entry point, NULL if none is available
	 */
	public function getAuthenticationEntryPoint();

	/**
	 * Returns TRUE if a \F3\FLOW3\Security\RequestPattern was set
	 *
	 * @return boolean True if a \F3\FLOW3\Security\RequestPattern was set
	 */
	public function hasRequestPattern();

	/**
	 * Sets a \F3\FLOW3\Security\RequestPattern
	 *
	 * @param \F3\FLOW3\Security\RequestPattern $requestPattern The set request pattern
	 * @return void
	 * @see hasRequestPattern()
	 */
	public function setRequestPattern(\F3\FLOW3\Security\RequestPatternInterface $requestPattern);

	/**
	 * Returns the set \F3\FLOW3\Security\RequestPatternInterface, NULL if none was set
	 *
	 * @return \F3\FLOW3\Security\RequestPatternInterface The set request pattern
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
	 * Might ask a \F3\FLOW3\Security\Authentication\UserDetailsServiceInterface.
	 *
	 * @return \F3\FLOW3\Security\Authentication\UserDetailsInterface A user details object
	 */
	public function getUserDetails();

	/**
	 * Returns the currently valid granted authorities. It might ask a \F3\FLOW3\Security\Authentication\UserDetailsServiceInterface.
	 * Note: You have to check isAuthenticated() before you call this method
	 *
	 * @return array Array of \F3\FLOW3\Security\Authentication\GrantedAuthority objects
	 */
	public function getGrantedAuthorities();

	/**
	 * Sets the authentication status. Usually called by the responsible \F3\FLOW3\Security\Authentication\ManagerInterface
	 *
	 * @param boolean $authenticationStatus TRUE if the token ist authenticated, FALSE otherwise
	 * @return void
	 */
	public function setAuthenticationStatus($authenticationStatus);
}

?>