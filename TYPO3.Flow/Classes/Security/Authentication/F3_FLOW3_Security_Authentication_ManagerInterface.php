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
 * Contract for an authentication manager.
 * Has to add a F3::FLOW3::Security::Authentication::TokenInterface to the securit context
 * Might set a UserDetailsService, RequestPattern and AuthenticationEntryPoint (from configuration).
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface ManagerInterface {

	/**
	 * Returns the tokens this manager is responsible for.
	 * Note: The order of the tokens in the array is important, as the tokens will be authenticated in the given order.
	 *
	 * @return array Array of F3::FLOW3::Security::Authentication::TokenInterface An array of tokens this manager is responsible for
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getTokens();

	/**
	 * Authenticates the given token. (Have a look at the F3::FLOW3::Security::Authentication::TokenManager for an implementation example)
	 *
	 * @param F3::FLOW3::Security::Authentication::TokenInterface $authenticationToken The token to be authenticated
	 * @return F3::FLOW3::Security::Authentication::TokenInterface The authenticated token, NULL if authentication failed
	 */
	public function authenticate(F3::FLOW3::Security::Authentication::TokenInterface $authenticationToken);
}

?>