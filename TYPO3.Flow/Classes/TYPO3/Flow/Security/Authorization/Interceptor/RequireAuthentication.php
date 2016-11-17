<?php
namespace TYPO3\Flow\Security\Authorization\Interceptor;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface;
use TYPO3\Flow\Security\Authorization\InterceptorInterface;

/**
 * This security interceptor invokes the authentication of the authentication tokens in the security context.
 * It is usally used by the firewall to define secured request that need proper authentication.
 *
 * @Flow\Scope("singleton")
 */
class RequireAuthentication implements InterceptorInterface
{
    /**
     * @var AuthenticationManagerInterface
     */
    protected $authenticationManager = null;

    /**
     * Constructor.
     *
     * @param AuthenticationManagerInterface $authenticationManager The authentication Manager
     */
    public function __construct(AuthenticationManagerInterface $authenticationManager)
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
