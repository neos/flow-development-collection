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
 * @deprecated since 1.2 Instead you should inherit from the AbstractAuthenticationController from within your package
 */
class AuthenticationController extends AbstractAuthenticationController {

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
	 * Redirects to a potentially intercepted request. Returns an error message if there has been none.
	 *
	 * @param \TYPO3\FLOW3\Mvc\ActionRequest $originalRequest The request that was intercepted by the security framework, NULL if there was none
	 * @return string
	 */
	protected function onAuthenticationSuccess(\TYPO3\FLOW3\Mvc\ActionRequest $originalRequest = NULL) {
		if ($originalRequest !== NULL) {
			$this->redirectToRequest($originalRequest);
		}
		return 'There was no redirect implemented and no intercepted request could be found after authentication.
				Please implement onAuthenticationSuccess() in your login controller to handle this case correctly.
				If you have a template for the authenticate action, simply make sure that onAuthenticationSuccess()
				returns NULL in your login controller.';
	}
}
?>