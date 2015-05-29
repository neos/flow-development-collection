<?php
namespace TYPO3\Flow\Security\Authentication\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An action controller for generic authentication in Flow
 *
 * @Flow\Scope("singleton")
 */
abstract class AbstractAuthenticationController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 * @Flow\Inject
	 */
	protected $authenticationManager;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * This action is used to show the login form. To make this
	 * work in your package simply create a template for this
	 * action, which could look like this in the simplest case:
	 *
	 * <f:flashMessages />
	 * <f:form action="authenticate">
	 *   <f:form.textfield name="__authentication[TYPO3][Flow][Security][Authentication][Token][UsernamePassword][username]" />
	 *   <f:form.password name="__authentication[TYPO3][Flow][Security][Authentication][Token][UsernamePassword][password]" />
	 *   <f:form.submit value="login" />
	 * </f:form>
	 *
	 * Note: This example is designed to serve the "UsernamePassword" token.
	 *
	 * @return void
	 */
	public function loginAction() {
	}

	/**
	 * Calls the authentication manager to authenticate all active tokens
	 * and redirects to the original intercepted request on success if there
	 * is one stored in the security context. If no intercepted request is
	 * found, the function simply returns.
	 *
	 * If authentication fails, the result of calling the defined
	 * $errorMethodName is returned.
	 *
	 * Note: Usually there is no need to override this action. You should use
	 * the according callback methods instead (onAuthenticationSuccess() and
	 * onAuthenticationFailure()).
	 *
	 * @return string
	 * @Flow\SkipCsrfProtection
	 */
	public function authenticateAction() {
		$authenticationException = NULL;
		try {
			$this->authenticationManager->authenticate();
		} catch (\TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception) {
			$authenticationException = $exception;
		}

		if ($this->authenticationManager->isAuthenticated()) {
			$storedRequest = $this->securityContext->getInterceptedRequest();
			if ($storedRequest !== NULL) {
				$this->securityContext->setInterceptedRequest(NULL);
			}
			return $this->onAuthenticationSuccess($storedRequest);
		} else {
			$this->onAuthenticationFailure($authenticationException);
			return call_user_func(array($this, $this->errorMethodName));
		}

	}

	/**
	 * Logs all active tokens out. Override this, if you want to
	 * have some custom action here. You can always call the parent
	 * method to do the actual logout.
	 *
	 * @return void
	 */
	public function logoutAction() {
		$this->authenticationManager->logout();
	}

	/**
	 * Is called if authentication failed.
	 *
	 * Override this method in your login controller to take any
	 * custom action for this event. Most likely you would want
	 * to redirect to some action showing the login form again.
	 *
	 * @param \TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception The exception thrown while the authentication process
	 * @return void
	 */
	protected function onAuthenticationFailure(\TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception = NULL) {
		$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Error('Authentication failed!', ($exception === NULL ? 1347016771 : $exception->getCode())));
	}

	/**
	 * Is called if authentication was successful. If there has been an
	 * intercepted request due to security restrictions, you might want to use
	 * something like the following code to restart the originally intercepted
	 * request:
	 *
	 * if ($originalRequest !== NULL) {
	 *     $this->redirectToRequest($originalRequest);
	 * }
	 * $this->redirect('someDefaultActionAfterLogin');
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $originalRequest The request that was intercepted by the security framework, NULL if there was none
	 * @return string
	 */
	abstract protected function onAuthenticationSuccess(\TYPO3\Flow\Mvc\ActionRequest $originalRequest = NULL);


	/**
	 * A template method for displaying custom error flash messages, or to
	 * display no flash message at all on errors. Override this to customize
	 * the flash message in your action controller.
	 *
	 * Note: If you implement a nice redirect in the onAuthenticationFailure()
	 * method of you login controller, this message should never be displayed.
	 *
	 * @return \TYPO3\Flow\Error\Error The flash message
	 * @api
	 */
	protected function getErrorFlashMessage() {
		return new \TYPO3\Flow\Error\Error('Wrong credentials.', NULL, array(), $this->actionMethodName);
	}
}
