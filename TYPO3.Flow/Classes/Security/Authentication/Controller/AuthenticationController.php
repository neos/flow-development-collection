<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication\Controller;

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
 * An action controller for generic authentication in FLOW3
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class AuthenticationController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * The authentication manager
	 * @var \F3\FLOW3\Security\Authentication\ManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * The current security context
	 * @var \F3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * Inject the authentication manager
	 *
	 * @param \F3\FLOW3\Security\Authentication\ManagerInterface $authenticationManager The authentication manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectAuthenticationManager(\F3\FLOW3\Security\Authentication\ManagerInterface $authenticationManager) {
		$this->authenticationManager = $authenticationManager;
	}

	/**
	 * Inject the security context holder and fetch the current security context from it
	 *
	 * @param \F3\FLOW3\Security\ContextHolderInterface $securityContextHolder The security context holder
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSecurityContextHolder(\F3\FLOW3\Security\ContextHolderInterface $securityContextHolder) {
		$this->securityContext = $securityContextHolder->getContext();
	}

	/**
	 * Calls the authentication manager to authenticate all active tokens
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateAction() {
		$this->authenticationManager->authenticate();
	}

	/**
	 * Sets the authentication status of all active tokens back to NO_CREDENTIALS_GIVEN
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function logoutAction() {
		$this->authenticationManager->logout();
	}
}
?>