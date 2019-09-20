<?php
namespace Neos\Flow\Security\Authorization;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Security\Exception\NoTokensAuthenticatedException;

/**
 * Contract for a security interceptor.
 */
interface InterceptorInterface
{
    /**
     * Invokes the security interception (e.g. calls a PrivilegeManagerInterface)
     *
     * @return boolean true if the security checks was passed
     * @throws AccessDeniedException
     * @throws AuthenticationRequiredException if an entity could not be found (assuming it is bound to the current session), causing a redirect to the authentication entrypoint
     * @throws NoTokensAuthenticatedException if no tokens could be found and the accessDecisionManager denied access to the privilege target, causing a redirect to the authentication entrypoint
     */
    public function invoke();
}
