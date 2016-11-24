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

use Neos\Flow\Mvc\ActionRequest;

/**
 * An authentication token used for simple username and password authentication via HTTP Basic Auth.
 */
class UsernamePasswordHttpBasic extends UsernamePassword implements SessionlessTokenInterface
{
    /**
     * Updates the username and password credentials from the HTTP authorization header.
     * Sets the authentication status to AUTHENTICATION_NEEDED, if the header has been
     * sent, to NO_CREDENTIALS_GIVEN if no authorization header was there.
     *
     * @param ActionRequest $actionRequest The current action request instance
     * @return void
     */
    public function updateCredentials(ActionRequest $actionRequest)
    {
        $authorizationHeader = $actionRequest->getHttpRequest()->getHeaders()->get('Authorization');
        if (substr($authorizationHeader, 0, 5) === 'Basic') {
            $credentials = base64_decode(substr($authorizationHeader, 6));
            $this->credentials['username'] = substr($credentials, 0, strpos($credentials, ':'));
            $this->credentials['password'] = substr($credentials, strpos($credentials, ':') + 1);
            $this->setAuthenticationStatus(self::AUTHENTICATION_NEEDED);
        } else {
            $this->credentials = ['username' => null, 'password' => null];
            $this->authenticationStatus = self::NO_CREDENTIALS_GIVEN;
        }
    }
}
