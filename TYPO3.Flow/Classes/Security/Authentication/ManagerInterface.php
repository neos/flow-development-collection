<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication;

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
 * Contract for an authentication manager.
 * Has to add a \F3\FLOW3\Security\Authentication\TokenInterface to the securit context
 * Might set a UserDetailsService, RequestPattern and AuthenticationEntryPoint (from configuration).
 *
 * @version $Id$
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface ManagerInterface {

	/**
	 * Returns the tokens this manager is responsible for.
	 * Note: The order of the tokens in the array is important, as the tokens will be authenticated in the given order.
	 *
	 * @return array Array of \F3\FLOW3\Security\Authentication\TokenInterface An array of tokens this manager is responsible for
	 */
	public function getTokens();

	/**
	 * Sets the security context
	 *
	 * @param \F3\FLOW3\Security\Context $securityContext The security context of the current request
	 * @return void
	 */
	public function setSecurityContext(\F3\FLOW3\Security\Context $securityContext);

	/**
	 * Returns the security context
	 *
	 * @return \F3\FLOW3\Security\Context $securityContext The security context of the current request
	 */
	public function getSecurityContext();

	/**
	 * Tries to authenticate the tokens in the security context, if needed.
	 * (Have a look at the \F3\FLOW3\Security\Authentication\TokenManager for an implementation example)
	 *
	 * @return void
	 */
	public function authenticate();

	/**
	 * Logs all acitve authentication tokens out
	 *
	 * @return void
	 */
	public function logout();
}

?>