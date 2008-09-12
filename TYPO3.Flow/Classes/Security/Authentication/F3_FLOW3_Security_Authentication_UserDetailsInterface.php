<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authentication;

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
 * Contract for a UserDetails object. A UserDetails object e.g. identifies a person that is authenticated.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface UserDetailsInterface {

	/**
	 * Compares this user to another.
	 *
	 * @param F3::FLOW3::Security::Authentication::UserDetailsInterface $userDetails The UserDetails object that should be compared with $this.
	 * @return boolean TRUE if the two UserDetails are equal.
	 */
	public function compare(F3::FLOW3::Security::Authentication::UserDetailsInterface $userDetails);

	/**
	 * Returns the string representation of this user
	 *
	 * @return string The string representation of this user.
	 */
	public function getName();

	/**
	 * Returns an array of F3::FLOW3::Security::Authentication::GrantedAuthorityInterfaces (e.g. Roles), the user currently has.
	 *
	 * @return array Array of F3::FLOW3::Security::Authentication::GrantedAuthorityInterfaces (e.g. Roles), the user currently has.
	 */
	public function getAuthorities();
}

?>