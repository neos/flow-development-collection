<?php
namespace Neos\Flow\Security\Authentication\Token;

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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Utility\ObjectAccess;

/**
 * An authentication token used for simple username and password authentication.
 */
class UsernamePassword extends AbstractToken
{
    /**
     * The username/password credentials
     * @var array
     * @Flow\Transient
     */
    protected $credentials = ['username' => '', 'password' => ''];

    /**
     * Updates the username and password credentials from the POST vars, if the POST parameters
     * are available. Sets the authentication status to REAUTHENTICATION_NEEDED, if credentials have been sent.
     *
     * Note: You need to send the username and password in these two POST parameters:
     *       __authentication[Neos][Flow][Security][Authentication][Token][UsernamePassword][username]
     *   and __authentication[Neos][Flow][Security][Authentication][Token][UsernamePassword][password]
     *
     * @param ActionRequest $actionRequest The current action request
     * @return void
     */
    public function updateCredentials(ActionRequest $actionRequest)
    {
        $httpRequest = $actionRequest->getHttpRequest();
        if ($httpRequest->getMethod() !== 'POST') {
            return;
        }

        $arguments = $actionRequest->getInternalArguments();
        $username = ObjectAccess::getPropertyPath($arguments, '__authentication.Neos.Flow.Security.Authentication.Token.UsernamePassword.username');
        $password = ObjectAccess::getPropertyPath($arguments, '__authentication.Neos.Flow.Security.Authentication.Token.UsernamePassword.password');

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
