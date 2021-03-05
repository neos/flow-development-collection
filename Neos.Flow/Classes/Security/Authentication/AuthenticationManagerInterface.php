<?php
namespace Neos\Flow\Security\Authentication;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Security\Exception\NoTokensAuthenticatedException;

/**
 * Contract for an authentication manager.
 *
 * Has to add a TokenInterface to the security context
 * Might set a UserDetailsService, RequestPattern and AuthenticationEntryPoint (from configuration).
 */
interface AuthenticationManagerInterface
{
    /**
     * Returns the security context
     *
     * @return SecurityContext $securityContext The security context of the current request
     */
    public function getSecurityContext(): SecurityContext;

    /**
     * Tries to authenticate the tokens in the security context, if needed.
     * (Have a look at the Authentication\TokenManager for an implementation example)
     *
     * @return void
     * @throws AuthenticationRequiredException
     * @throws NoTokensAuthenticatedException
     */
    public function authenticate(): void;

    /**
     * Checks if at least one token is authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool;

    /**
     * Logs all active authentication tokens out
     *
     * @return void
     */
    public function logout(): void;
}
