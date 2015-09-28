<?php
namespace TYPO3\Flow\Security\Authorization\Interceptor;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * This security interceptor invokes the authentication of the authentication tokens in the security context.
 * It is usally used by the firewall to define secured request that need proper authentication.
 *
 * @Flow\Scope("singleton")
 */
class RequireAuthentication implements \TYPO3\Flow\Security\Authorization\InterceptorInterface
{
    /**
     * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
     */
    protected $authenticationManager = null;

    /**
     * Constructor.
     *
     * @param \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface $authenticationManager The authentication Manager
     */
    public function __construct(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface $authenticationManager)
    {
        $this->authenticationManager = $authenticationManager;
    }

    /**
     * Invokes the the authentication, if needed.
     *
     * @return boolean TRUE if the security checks was passed
     */
    public function invoke()
    {
        $this->authenticationManager->authenticate();
    }
}
