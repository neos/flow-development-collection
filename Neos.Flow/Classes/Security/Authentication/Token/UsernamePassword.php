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
 * An authentication token used for simple username and password authentication.
 */
class UsernamePassword extends AbstractToken implements UsernamePasswordTokenInterface
{
    private const DEFAULT_USERNAME_POST_FIELD = '__authentication.Neos.Flow.Security.Authentication.Token.UsernamePassword.username';
    private const DEFAULT_PASSWORD_POST_FIELD = '__authentication.Neos.Flow.Security.Authentication.Token.UsernamePassword.password';

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
     *   or specify the "usernamePostField" and "passwordPostField" options in the provider configuration:
     *
     * Neos:
     *   Flow:
     *     security:
     *       authentication:
     *         providers:
     *           DefaultProvider:
     *             provider: PersistedUsernamePasswordProvider
     *             tokenOptions:
     *               usernamePostField: 'some.argument'
     *               passwordPostField: 'some.other.argument'
     *
     * @param ActionRequest $actionRequest The current action request
     * @return void
     * @throws InvalidAuthenticationStatusException
     */
    public function updateCredentials(ActionRequest $actionRequest)
    {
        $httpRequest = $actionRequest->getHttpRequest();
        if ($httpRequest->getMethod() !== 'POST') {
            return;
        }
        $allArguments = array_merge($actionRequest->getArguments(), $actionRequest->getInternalArguments());
        $usernameFieldName = $this->options['usernamePostField'] ?? self::DEFAULT_USERNAME_POST_FIELD;
        $passwordFieldName = $this->options['passwordPostField'] ?? self::DEFAULT_PASSWORD_POST_FIELD;
        $username = ObjectAccess::getPropertyPath($allArguments, $usernameFieldName);
        $password = ObjectAccess::getPropertyPath($allArguments, $passwordFieldName);
        if (!empty($username) && !empty($password)) {
            $this->credentials['username'] = $username;
            $this->credentials['password'] = $password;
            $this->setAuthenticationStatus(self::AUTHENTICATION_NEEDED);
        }
    }

    /**
     * @return string The username this token extracted from the request, or an empty string
     */
    public function getUsername(): string
    {
        return $this->credentials['username'] ?? '';
    }

    /**
     * @return string The password this token extracted from the request, or an empty string
     */
    public function getPassword(): string
    {
        return $this->credentials['password'] ?? '';
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
