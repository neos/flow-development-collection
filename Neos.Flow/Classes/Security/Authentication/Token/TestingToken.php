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

/**
 * An authentication token used for functional tests
 */
class TestingToken extends AbstractToken implements SessionlessTokenInterface
{
    /**
     * Simply sets the authentication status to AUTHENTICATION_NEEDED
     *
     * @param ActionRequest $actionRequest The current action request instance
     * @return void
     */
    public function updateCredentials(ActionRequest $actionRequest)
    {
        $this->authenticationStatus = self::AUTHENTICATION_NEEDED;
    }

    /**
     * Returns a string representation of the token for logging purposes.
     *
     * @return string The username credential
     */
    public function __toString()
    {
        return 'Testing token';
    }
}
