<?php
namespace TYPO3\FLOW3\Security\Authentication\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An action controller for generic authentication in FLOW3
 *
 * @FLOW3\Scope("singleton")
 */
class AuthenticationController extends \TYPO3\FLOW3\MVC\Controller\ActionController {

	/**
	 * The authentication manager
	 * @var \TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface
	 * @FLOW3\Inject
	 */
	protected $authenticationManager;

	/**
	 * @var \TYPO3\FLOW3\Security\Context
	 * @FLOW3\Inject
	 */
	protected $securityContext;

	/**
	 * Calls the authentication manager to authenticate all active tokens
	 * and redirects to the original intercepted request on success (if there
	 * is one stored in the security context)
	 *
	 * @return void
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
	 */
	public function logoutAction() {
		$this->authenticationManager->logout();
	}

	/**
	 * A template method for displaying custom error flash messages, or to
	 * display no flash message at all on errors. Override this to customize
	 * the flash message in your action controller.
	 *
	 * @return \TYPO3\FLOW3\Error\Error The flash message
	 * @api
	 */
	protected function getErrorFlashMessage() {
		return new \TYPO3\FLOW3\Error\Error('Wrong credentials.', NULL, NULL, $this->actionMethodName);
	}
}
?>