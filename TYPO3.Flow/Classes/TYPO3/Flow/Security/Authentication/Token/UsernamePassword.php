<?php
namespace TYPO3\Flow\Security\Authentication\Token;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An authentication token used for simple username and password authentication.
 */
class UsernamePassword extends \TYPO3\Flow\Security\Authentication\Token\AbstractToken
{
    /**
     * The username/password credentials
     * @var array
     * @Flow\Transient
     */
    protected $credentials = array('username' => '', 'password' => '');

    /**
     * Updates the username and password credentials from the POST vars, if the POST parameters
     * are available. Sets the authentication status to REAUTHENTICATION_NEEDED, if credentials have been sent.
     *
     * Note: You need to send the username and password in these two POST parameters:
     *       __authentication[TYPO3][Flow][Security][Authentication][Token][UsernamePassword][username]
     *   and __authentication[TYPO3][Flow][Security][Authentication][Token][UsernamePassword][password]
     *
     * @param \TYPO3\Flow\Mvc\ActionRequest $actionRequest The current action request
     * @return void
     */
    public function updateCredentials(\TYPO3\Flow\Mvc\ActionRequest $actionRequest)
    {
        $httpRequest = $actionRequest->getHttpRequest();
        if ($httpRequest->getMethod() !== 'POST') {
            return;
        }

        $arguments = $actionRequest->getInternalArguments();
        $username = \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($arguments, '__authentication.TYPO3.Flow.Security.Authentication.Token.UsernamePassword.username');
        $password = \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($arguments, '__authentication.TYPO3.Flow.Security.Authentication.Token.UsernamePassword.password');

        if (!empty($username) && !empty($password)) {
            $this->credentials['username'] = $username;
            $this->credentials['password'] = $password;
            $this->setAuthenticationStatus(self::AUTHENTICATION_NEEDED);
        }
    }

    /**
     * Returns a string representation of the token for logging purposes.
     *
     * @return string The username credential
     */
    public function __toString()
    {
        return 'Username: "' . $this->credentials['username'] . '"';
    }
}
