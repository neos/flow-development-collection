<?php
namespace Neos\Flow\Security;

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
use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Policy\Role;

/**
 * This is dummy implementation of a security context, which holds
 * security information like roles oder details of authenticated users.
 * These information can be set manually on the context as needed.
 *
 * @Flow\Scope("prototype")
 */
class DummyContext extends Context
{
    /**
     * TRUE if the context is initialized in the current request, FALSE or NULL otherwise.
     *
     * @var boolean
     * @Flow\Transient
     */
    protected $initialized = false;

    /**
     * Array of configured tokens (might have request patterns)
     * @var array
     */
    protected $tokens = [];

    /**
     * @var string
     */
    protected $csrfProtectionToken;

    /**
     * @var RequestInterface
     */
    protected $interceptedRequest;

    /**
     * @Flow\Transient
     * @var Role[]
     */
    protected $roles = null;

    /**
     * @param boolean $initialized
     * @return void
     */
    public function setInitialized($initialized)
    {
        $this->initialized = $initialized;
    }

    /**
     * @return boolean TRUE if the Context is initialized, FALSE otherwise.
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * Get the token authentication strategy
     *
     * @return int One of the AUTHENTICATE_* constants
     */
    public function getAuthenticationStrategy()
    {
        return $this->authenticationStrategy;
    }

    /**
     * Sets the Authentication\Tokens of the security context which should be active.
     *
     * @param TokenInterface[] $tokens Array of set tokens
     * @return array
     */
    public function setAuthenticationTokens(array $tokens)
    {
        return $this->tokens = $tokens;
    }

    /**
     * Returns all Authentication\Tokens of the security context which are
     * active for the current request. If a token has a request pattern that cannot match
     * against the current request it is determined as not active.
     *
     * @return TokenInterface[] Array of set tokens
     */
    public function getAuthenticationTokens()
    {
        return $this->tokens;
    }

    /**
     * Returns all Authentication\Tokens of the security context which are
     * active for the current request and of the given type. If a token has a request pattern that cannot match
     * against the current request it is determined as not active.
     *
     * @param string $className The class name
     * @return TokenInterface[] Array of set tokens of the specified type
     */
    public function getAuthenticationTokensOfType($className)
    {
        $tokens = [];
        foreach ($this->tokens as $token) {
            if ($token instanceof $className) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    /**
     * Returns the roles of all authenticated accounts, including inherited roles.
     *
     * If no authenticated roles could be found the "Anonymous" role is returned.
     *
     * The "Neos.Flow:Everybody" roles is always returned.
     *
     * @return Role[]
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Set an array of role objects.
     *
     * @param Role[] $roles
     * @return void
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * Returns TRUE, if at least one of the currently authenticated accounts holds
     * a role with the given identifier, also recursively.
     *
     * @param string $roleIdentifier The string representation of the role to search for
     * @return boolean TRUE, if a role with the given string representation was found
     */
    public function hasRole($roleIdentifier)
    {
        if ($roleIdentifier === 'Neos.Flow:Everybody') {
            return true;
        }
        if ($roleIdentifier === 'Neos.Flow:Anonymous') {
            return (!empty($this->roles));
        }
        if ($roleIdentifier === 'Neos.Flow:AuthenticatedUser') {
            return (empty($this->roles));
        }

        return isset($this->roles[$roleIdentifier]);
    }

    /**
     * @param string $csrfProtectionToken
     * @return void
     */
    public function setCsrfProtectionToken($csrfProtectionToken)
    {
        $this->csrfProtectionToken = $csrfProtectionToken;
    }

    /**
     * Returns the current CSRF protection token. A new one is created when needed, depending on the  configured CSRF
     * protection strategy.
     *
     * @return string
     */
    public function getCsrfProtectionToken()
    {
        return $this->csrfProtectionToken;
    }

    /**
     * Returns TRUE if the context has CSRF protection tokens.
     *
     * @return boolean TRUE, if the token is valid. FALSE otherwise.
     */
    public function hasCsrfProtectionTokens()
    {
        return isset($this->csrfProtectionToken);
    }

    /**
     * Returns TRUE if the given string is a valid CSRF protection token. The token will be removed if the configured
     * csrf strategy is 'onePerUri'.
     *
     * @param string $csrfToken The token string to be validated
     * @return boolean TRUE, if the token is valid. FALSE otherwise.
     */
    public function isCsrfProtectionTokenValid($csrfToken)
    {
        return ($csrfToken === $this->csrfProtectionToken);
    }

    /**
     * Sets an action request, to be stored for later resuming after it
     * has been intercepted by a security exception.
     *
     * @param ActionRequest $interceptedRequest
     * @return void
     * @Flow\Session(autoStart=true)
     */
    public function setInterceptedRequest(ActionRequest $interceptedRequest = null)
    {
        $this->interceptedRequest = $interceptedRequest;
    }

    /**
     * Returns the request, that has been stored for later resuming after it
     * has been intercepted by a security exception, NULL if there is none.
     *
     * @return ActionRequest
     */
    public function getInterceptedRequest()
    {
        return $this->interceptedRequest;
    }

    /**
     * Clears the security context.
     *
     * @return void
     */
    public function clearContext()
    {
        $this->roles = null;
        $this->tokens = [];
        $this->csrfProtectionToken = null;
        $this->interceptedRequest = null;
        $this->initialized = false;
    }
}
