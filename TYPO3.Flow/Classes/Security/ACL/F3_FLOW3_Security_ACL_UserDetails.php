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
 * The representation of an authenticated user that has specific roles depending on request patterns.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_ACL_UserDetails implements F3_FLOW3_Security_Authentication_UserDetailsInterface {

	/**
	 * Compares this user to another.
	 *
	 * @param F3_FLOW3_Security_Authentication_UserDetailsInterface $userDetails The UserDetails object that should be compared with $this.
	 * @return boolean TRUE if the two UserDetails are equal.
	 */
	public function compare(F3_FLOW3_Security_Authentication_UserDetailsInterface $userDetails) {

	}

	/**
	 * Returns the string representation of this user
	 *
	 * @return string The string representation of this user.
	 */
	public function getName() {

	}

	/**
	 * Adds a new role to this user, they role is only active if the given request pattern matches. If no pattern is given, the role will always be active.
	 *
	 * @param F3_FLOW3_Security_ACL_Role $role The role the user should have
	 * @param F3_FLOW3_Security_RequestPattern $requestPattern A request pattern for which the role should be active
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo: This should be filled by configuration
	 */
	public function addRole(F3_FLOW3_Security_ACL_Role $role, F3_FLOW3_Security_RequestPattern $requestPattern = NULL) {

	}

	/**
	 * Returns an array of F3_FLOW3_Security_Authentication_GrantedAuthorityInterfaces (roles), the user currently has.
	 *
	 * @return array Array of F3_FLOW3_Security_Authentication_GrantedAuthorityInterfaces (e.g. Roles), the user currently has.
	 */
	public function getAuthorities() {

	}
}

?>