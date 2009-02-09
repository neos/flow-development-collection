<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Controller;

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
 * @subpackage MVC
 * @version $Id: $
 */

/**
 * An action controller for authenticating via an username and password form
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id: $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class LoginController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * The security context holder
	 * @var \F3\FLOW3\Security\ContextHolderInterface
	 */
	protected $securityContextHolder;

	/**
	 * Inject the security context holder
	 *
	 * @param \F3\FLOW3\Security\ContextHolderInterface $securityContextHolder The security context holder
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSecurityContextHolder(\F3\FLOW3\Security\ContextHolderInterface $securityContextHolder) {
		$this->securityContextHolder = $securityContextHolder;
	}

	/**
	 * Renders the login page
	 *
	 * @return string The rendered login page
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function indexAction() {
		$authenticationTokens = $this->securityContextHolder->getContext()->getAuthenticationTokens();
		$userIsAuthenticated = FALSE;
		$publicKeyPassword = NULL;
		$publicKeyUsername = NULL;

		foreach ($authenticationTokens as $authenticationToken) {
			if ($authenticationToken->isAuthenticated()) {
				$userIsAuthenticated = TRUE;
				break;
			}

			if ($authenticationToken instanceof \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword) {
				$publicKeyPassword = $authenticationToken->generatePublicKeyForPassword();
				$publicKeyUsername = $authenticationToken->generatePublicKeyForUsername();
				break;
			}
		}

		if (!$userIsAuthenticated) {
			$loginForm = $this->view->render();
			$loginForm = str_replace('###PUBLIC_KEY_PASSWORD###', $publicKeyPassword->getModulus(), $loginForm);
			$loginForm = str_replace('###PUBLIC_KEY_USERNAME###', $publicKeyUsername->getModulus(), $loginForm);

			return $loginForm;
		} else {
			$authenticatedUserView = $this->objectManager->getObject('F3\FLOW3\Security\View\AuthenticatedUserView');
			$authenticatedUserView->setRequest($this->request);
			return $authenticatedUserView->render();
		}
	}
}
?>