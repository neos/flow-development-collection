<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\ACL;

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
 * The representation of an authenticated user that has specific roles depending on request patterns.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class UserDetails implements \F3\FLOW3\Security\Authentication\UserDetailsInterface {

	/**
	 * Compares this user to another.
	 *
	 * @param \F3\FLOW3\Security\Authentication\UserDetailsInterface $userDetails The UserDetails object that should be compared with $this.
	 * @return boolean TRUE if the two UserDetails are equal.
	 */
	public function compare(\F3\FLOW3\Security\Authentication\UserDetailsInterface $userDetails) {

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
	 * @param \F3\FLOW3\Security\ACL\Role $role The role the user should have
	 * @param \F3\FLOW3\Security\RequestPattern $requestPattern A request pattern for which the role should be active
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo: This should be filled by configuration
	 */
	public function addRole(\F3\FLOW3\Security\ACL\Role $role, \F3\FLOW3\Security\RequestPatternInterface $requestPattern = NULL) {

	}

	/**
	 * Returns an array of \F3\FLOW3\Security\Authentication\GrantedAuthorityInterfaces (roles), the user currently has.
	 *
	 * @return array Array of \F3\FLOW3\Security\Authentication\GrantedAuthorityInterfaces (e.g. Roles), the user currently has.
	 */
	public function getAuthorities() {

	}
}

?>