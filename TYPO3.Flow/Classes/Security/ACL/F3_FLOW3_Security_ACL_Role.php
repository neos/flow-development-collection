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
 * A role (granted authority) for the ACLService. These roles can be structured in a tree.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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