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
 * The ACL UserDetailsService. It mainly calculates the current roles for the set request patterns from the given authentication token.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_ACL_UserDetailsService implements F3_FLOW3_Security_Authentication_UserDetailsServiceInterface {

	/**
	 * Returns the F3_FLOW3_Security_Authentication_UserDetailsInterface object for the given authentication token.
	 *
	 * @param F3_FLOW3_Security_Authentication_TokenInterface $authenticationToken The authentication token to get the user details for
	 * @return F3_FLOW3_Security_Authentication_UserDetailsInterface The user details for the given token
	 */
	public function loadUserDetials(F3_FLOW3_Security_Authentication_TokenInterface $authenticationToken) {
		//Uses the credentials in the token to figure out which user should be loaded
	}
}

?>