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
 * An authentication token used for functional tests
 */
class TestingToken extends AbstractToken implements SessionlessTokenInterface
{
    /**
     * Simply sets the authentication status to AUTHENTICATION_NEEDED
     *
     * @param \TYPO3\Flow\Mvc\ActionRequest $actionRequest The current action request instance
     * @return void
     */
    public function updateCredentials(\TYPO3\Flow\Mvc\ActionRequest $actionRequest)
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
