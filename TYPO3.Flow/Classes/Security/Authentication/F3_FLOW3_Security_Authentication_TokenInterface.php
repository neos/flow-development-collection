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
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_Security_Authentication_TokenInterface {

	/**
	 * Constructor.
	 *
	 * @param F3_FLOW3_Security_Authentication_UserDetailsServiceInterface A user details service
	 * @param F3_FLOW3_Security_RequestPattern $requestPattern This patterns defines the specific requests, this token is only valid for
	 * @return
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(
			F3_FLOW3_Security_Authentication_UserDetailsServiceInterface $userDetailsService,
			F3_FLOW3_Security_RequestPattern $requestPattern = NULL
			);

	/**
	 *
	 *
	 * @return boolean
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isAuthenticated();

	/**
	 * Returns TRUE if a F3_FLOW3_Security_RequestPattern was set
	 *
	 * @return boolean True if a F3_FLOW3_Security_RequestPattern was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRequestPattern() {

	}

	/**
	 * Returns the set F3_FLOW3_Security_RequestPattern, NULL if none was set
	 *
	 * @return F3_FLOW3_Security_RequestPattern The set request pattern
	 * @see hasRequestPattern()
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRequestPattern() {

	}

	/**
	 *
	 *
	 * @return F3_FLOW3_Security_Authentication_UserDetailsInterface A user details object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getUserDetails();

	/**
	 * Returns the currently valid granted authorities.
	 * Note: You have to check isAuthenticated() before you call this method
	 *
	 * @return array Array of F3_FLOW3_Security_Authentication_GrantedAuthority objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getGrantedAuthorities();

	/**
	 * Sets the authentication status. Usually called by the responsible F3_FLOW3_Security_Authentication_ManagerInterface
	 *
	 * @param boolean $authenticationStatus TRUE if the token ist authenticated, FALSE otherwise
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAuthenticationStatus($authenticationStatus);
}

?>