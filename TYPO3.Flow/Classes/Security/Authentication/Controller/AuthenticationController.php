<?php
namespace TYPO3\FLOW3\Security\Authentication\Controller;

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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @scope singleton
 */
class AuthenticationController extends \TYPO3\FLOW3\MVC\Controller\ActionController {

	/**
	 * The authentication manager
	 * @var \TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface
	 * @inject
	 */
	protected $authenticationManager;

	/**
	 * @var \TYPO3\FLOW3\Security\Context
	 * @inject
	 */
	protected $securityContext;

	/**
	 * Calls the authentication manager to authenticate all active tokens
	 * and redirects to the original intercepted request on success (if there
	 * is one stored in the security context)
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateAction() {
		$authenticated = FALSE;
		try {
			$this->authenticationManager->authenticate();
			$authenticated = TRUE;
		} catch (\TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException $exception) {
		}

		if ($authenticated) {
			$storedRequest = $this->securityContext->getInterceptedRequest();
			if ($storedRequest !== NULL) {
				$packageKey = $storedRequest->getControllerPackageKey();
				$subpackageKey = $storedRequest->getControllerSubpackageKey();
				if ($subpackageKey !== NULL) $packageKey .= '\\' . $subpackageKey;
				$this->redirect($storedRequest->getControllerActionName(), $storedRequest->getControllerName(), $packageKey, $storedRequest->getArguments());
			}
		} else {
			return $this->errorAction();
		}
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

	/**
	 * A template method for displaying custom error flash messages, or to
	 * display no flash message at all on errors. Override this to customize
	 * the flash message in your action controller.
	 *
	 * @return string|boolean The flash message or FALSE if no flash message should be set
	 * @api
	 */
	protected function getErrorFlashMessage() {
		return 'Wrong credentials.';
	}
}
?>