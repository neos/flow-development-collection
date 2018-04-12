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
 * An authentication token used for simple password authentication.
 */
class PasswordToken extends AbstractToken
{
    /**
     * The password credentials
     * @var array
     * @Flow\Transient
     */
    protected $credentials = ['password' => ''];

    /**
     * @var \Neos\Flow\Utility\Environment
     * @Flow\Inject
     */
    protected $environment;

    /**
     * Updates the password credential from the POST vars, if the POST parameters
     * are available. Sets the authentication status to AUTHENTICATION_NEEDED, if credentials have been sent.
     *
     * Note: You need to send the password in this POST parameter:
     *       __authentication[Neos][Flow][Security][Authentication][Token][PasswordToken][password]
     *
     * @param ActionRequest $actionRequest The current action request
     * @return void
     */
    public function updateCredentials(ActionRequest $actionRequest)
    {
        if ($actionRequest->getHttpRequest()->getMethod() !== 'POST') {
            return;
        }

        $postArguments = $actionRequest->getInternalArguments();
        $password = ObjectAccess::getPropertyPath($postArguments, '__authentication.Neos.Flow.Security.Authentication.Token.PasswordToken.password');

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
