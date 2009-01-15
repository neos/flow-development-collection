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
 * A role (granted authority) for the ACLService. These roles can be structured in a tree.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Role implements \F3\FLOW3\Security\Authentication\GrantedAuthorityInterface {

	/**
	 * The string identifier of this role
	 * @var string
	 */
	protected $identifier;

	/**
	 * Constructor.
	 *
	 * @param string $identifier The string identifier of this role
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * Returns the role (granted authority) in a string representation.
	 *
	 * @return string The string representation of the GrantedAuthority
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAuthority() {
		return $this->identifier;
	}

	/**
	 * Returns the string representation of this role (the identifier)
	 *
	 * @return string the string representation of this role
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __toString() {
		return $this->identifier;
	}
}

?>