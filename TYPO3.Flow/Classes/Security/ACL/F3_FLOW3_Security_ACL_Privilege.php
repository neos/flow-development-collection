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
 * The representation of a privilege, that a role has for a given resource. E.g. READ or WRITE.
 * A privilege can be explicitly granted or denied. In the policy file this is expressed by appending
 * _DENY or _GRANT to the privilege's identifier.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_ACL_Privilege {

	/**
	 * Constructor.
	 *
	 * @param string $identifier An identifier for this privilege. Note: Always prefix your package key for custom privileges!
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct($identifier) {

	}

	/**
	 * Sets this privilege to a granting privilege
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setGrant() {

	}

	/**
	 * Sets this privilege to a denying privilege
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setDeny() {

	}

	/**
	 * Returns TRUE if this privilege object grants the privilege it represents
	 *
	 * @return boolean TRUE if this privilege object grants the privilege it represents
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isGrant() {

	}

	/**
	 * Returns TRUE if this privilege object denies the privilege it represents
	 *
	 * @return boolean TRUE if this privilege object denies the privilege it represents
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isDeny() {

	}
}

?>