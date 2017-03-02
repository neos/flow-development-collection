<?php
namespace Neos\Flow\Security\Authentication\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Error;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;

/**
 * An action controller for generic authentication in Flow
 *
 * @Flow\Scope("singleton")
 */
abstract class AbstractAuthenticationController extends ActionController
{
    /**
     * @var AuthenticationManagerInterface
     * @Flow\Inject
     */
    protected $authenticationManager;

    /**
     * @var Context
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
     *   <f:form.textfield name="__authentication[Neos][Flow][Security][Authentication][Token][UsernamePassword][username]" />
     *   <f:form.password name="__authentication[Neos][Flow][Security][Authentication][Token][UsernamePassword][password]" />
     *   <f:form.submit value="login" />
     * </f:form>
     *
     * Note: This example is designed to serve the "UsernamePassword" token.
     *
     * @return void
     */
    public function loginAction()
    {
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
    public function authenticateAction()
    {
        $authenticationException = null;
        try {
            $this->authenticationManager->authenticate();
        } catch (AuthenticationRequiredException $exception) {
            $authenticationException = $exception;
        }

        if ($this->authenticationManager->isAuthenticated()) {
            $storedRequest = $this->securityContext->getInterceptedRequest();
            if ($storedRequest !== null) {
                $this->securityContext->setInterceptedRequest(null);
            }
            return $this->onAuthenticationSuccess($storedRequest);
        } else {
            $this->onAuthenticationFailure($authenticationException);
            return call_user_func([$this, $this->errorMethodName]);
        }
    }

    /**
     * Logs all active tokens out. Override this, if you want to
     * have some custom action here. You can always call the parent
     * method to do the actual logout.
     *
     * @return void
     */
    public function logoutAction()
    {
        $this->authenticationManager->logout();
    }

    /**
     * Is called if authentication failed.
     *
     * Override this method in your login controller to take any
     * custom action for this event. Most likely you would want
     * to redirect to some action showing the login form again.
     *
     * @param AuthenticationRequiredException $exception The exception thrown while the authentication process
     * @return void
     */
    protected function onAuthenticationFailure(AuthenticationRequiredException $exception = null)
    {
        $this->flashMessageContainer->addMessage(new Error('Authentication failed!', ($exception === null ? 1347016771 : $exception->getCode())));
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
     * @param ActionRequest $originalRequest The request that was intercepted by the security framework, NULL if there was none
     * @return string
     */
    abstract protected function onAuthenticationSuccess(ActionRequest $originalRequest = null);


    /**
     * A template method for displaying custom error flash messages, or to
     * display no flash message at all on errors. Override this to customize
     * the flash message in your action controller.
     *
     * Note: If you implement a nice redirect in the onAuthenticationFailure()
     * method of you login controller, this message should never be displayed.
     *
     * @return Error The flash message
     * @api
     */
    protected function getErrorFlashMessage()
    {
        return new Error('Wrong credentials.', null, [], $this->actionMethodName);
    }
}
