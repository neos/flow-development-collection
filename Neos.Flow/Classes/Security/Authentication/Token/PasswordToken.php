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
use Neos\Flow\Security\Exception\InvalidAuthenticationStatusException;
use Neos\Utility\ObjectAccess;

/**
 * An authentication token used for simple password authentication.
 */
class PasswordToken extends AbstractToken implements PasswordTokenInterface
{
    private const DEFAULT_PASSWORD_POST_FIELD = '__authentication.Neos.Flow.Security.Authentication.Token.PasswordToken.password';

    /**
     * The password credentials
     * @var array
     * @Flow\Transient
     */
    protected $credentials = ['password' => ''];

    /**
     * Updates the password credential from the POST vars, if the POST parameters
     * are available. Sets the authentication status to AUTHENTICATION_NEEDED, if credentials have been sent.
     *
     * Note: You need to send the password in this POST parameter:
     *       __authentication[Neos][Flow][Security][Authentication][Token][PasswordToken][password]
     *
     * @param ActionRequest $actionRequest The current action request
     * @return void
     * @throws InvalidAuthenticationStatusException
     */
    public function updateCredentials(ActionRequest $actionRequest)
    {
        if ($actionRequest->getHttpRequest()->getMethod() !== 'POST') {
            return;
        }
        $allArguments = array_merge($actionRequest->getArguments(), $actionRequest->getInternalArguments());
        $passwordFieldName = $this->options['passwordPostField'] ?? self::DEFAULT_PASSWORD_POST_FIELD;
        $password = ObjectAccess::getPropertyPath($allArguments, $passwordFieldName);
        if (!empty($password)) {
            $this->credentials['password'] = $password;
            $this->setAuthenticationStatus(self::AUTHENTICATION_NEEDED);
        }
    }

    /**
     * @return string the password this token represents, or an empty string
     */
    public function getPassword(): string
    {
        return $this->credentials['password'] ?? '';
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
