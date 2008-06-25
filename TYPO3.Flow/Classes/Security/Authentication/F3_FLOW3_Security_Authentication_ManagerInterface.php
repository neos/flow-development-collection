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
 * Contract for an authentication manager.
 * Has to add a F3_FLOW3_Security_Authentication_TokenInterface to the securit context
 * Might set a UserDetailsService, RequestPattern and AuthenticationEntryPoint (from configuration).
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_Security_Authentication_ManagerInterface {

	/**
	 * Authenticates the given token. (Have a look at the F3_FLOW3_Security_Authentication_TokenManager for an implementation example)
	 *
	 * @param F3_FLOW3_Security_Authentication_TokenInterface $authenticationToken The token to be authenticated
	 * @return F3_FLOW3_Security_Authentication_TokenInterface The authenticated token, NULL if authentication failed
	 */
	public function authenticate(F3_FLOW3_Security_Authentication_TokenInterface $authenticationToken);
}

?>