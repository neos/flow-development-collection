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
 * A role (granted authority) for the ACLService. These roles can be structured in a tree.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_ACL_Role implements F3_FLOW3_Security_Authentication_GrantedAuthorityInterface {

	/**
	 * @var array Array of child roles
	 */
	protected $children = array();

	/**
	 * @var F3_FLOW3_Security_ACL_Role A reference to the parent role
	 */
	protected $parent = NULL;

	/**
	 * Constructor.
	 *
	 * @param F3_FLOW3_Security_ACL_Role $parent The parent role
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3_FLOW3_Security_ACL_Role $parent = NULL) {

	}

	/**
	 * Returns the role (granted authority) in a string representation.
	 *
	 * @return string The string representation of the GrantedAuthority
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAuthority() {

	}

	/**
	 * Adds a new child role to this role.
	 *
	 * @param F3_FLOW3_Security_ACL_Role $role A new child role for this role
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addChild(F3_FLOW3_Security_ACL_Role $role) {

	}

	/**
	 * Returns an array of all child roles of this role.
	 *
	 * @return array Array of F3_FLOW3_Security_ACL_Role objects, beeing the children of this role
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getChildren() {

	}

	/**
	 * Returns the parent role of this role, NULL if there is none.
	 *
	 * @return F3_FLOW3_Security_ACL_Role The parent role of this one, NULL if there is none
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getParent() {

	}

	/**
	 * Returns an array of the string representation of all roles in the tree starting from this one to the tree root.
	 *
	 * @return array Array of the string representation of all roles in the tree starting from this one to the tree root
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getFlattenedAuthorityTree() {

	}
}

?>