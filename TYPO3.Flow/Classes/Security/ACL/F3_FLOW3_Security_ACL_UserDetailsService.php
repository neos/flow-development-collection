<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\ACL;

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
 * @version $Id$
 */

/**
 * The ACL UserDetailsService. It mainly calculates the current roles for the set request patterns from the given authentication token.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class UserDetailsService implements \F3\FLOW3\Security\Authentication\UserDetailsServiceInterface {

	/**
	 * Returns the \F3\FLOW3\Security\Authentication\UserDetailsInterface object for the given authentication token.
	 *
	 * @param \F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken The authentication token to get the user details for
	 * @return \F3\FLOW3\Security\Authentication\UserDetailsInterface The user details for the given token
	 */
	public function loadUserDetails(\F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken) {
		//Uses the credentials in the token to figure out which user should be loaded
	}
}

?>