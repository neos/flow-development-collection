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
 * An authentication token used for simple password authentication.
 */
class PasswordToken extends \TYPO3\Flow\Security\Authentication\Token\AbstractToken
{
    /**
     * The password credentials
     * @var array
     * @Flow\Transient
     */
    protected $credentials = array('password' => '');

    /**
     * @var \TYPO3\Flow\Utility\Environment
     * @Flow\Inject
     */
    protected $environment;

    /**
     * Updates the password credential from the POST vars, if the POST parameters
     * are available. Sets the authentication status to AUTHENTICATION_NEEDED, if credentials have been sent.
     *
     * Note: You need to send the password in this POST parameter:
     *       __authentication[TYPO3][Flow][Security][Authentication][Token][PasswordToken][password]
     *
     * @param \TYPO3\Flow\Mvc\ActionRequest $actionRequest The current action request
     * @return void
     */
    public function updateCredentials(\TYPO3\Flow\Mvc\ActionRequest $actionRequest)
    {
        if ($actionRequest->getHttpRequest()->getMethod() !== 'POST') {
            return;
        }

        $postArguments = $actionRequest->getInternalArguments();
        $password = \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($postArguments, '__authentication.TYPO3.Flow.Security.Authentication.Token.PasswordToken.password');

        if (!empty($password)) {
            $this->credentials['password'] = $password;
            $this->setAuthenticationStatus(self::AUTHENTICATION_NEEDED);
        }
    }

    /**
     * Returns a string representation of the token for logging purposes.
     *
     * @return string
     */
    public function __toString()
    {
        return 'Password token';
    }
}
