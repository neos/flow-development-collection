<?php
namespace TYPO3\Flow\Security\Authentication;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Contract for an authentication manager.
 *
 * Has to add a \TYPO3\Flow\Security\Authentication\TokenInterface to the security context
 * Might set a UserDetailsService, RequestPattern and AuthenticationEntryPoint (from configuration).
 */
interface AuthenticationManagerInterface
{
    /**
     * Returns the tokens this manager is responsible for.
     * Note: The order of the tokens in the array is important, as the tokens will be authenticated in the given order.
     *
     * @return array Array of \TYPO3\Flow\Security\Authentication\TokenInterface An array of tokens this manager is responsible for
     */
    public function getTokens();

    /**
     * Sets the security context
     *
     * @param \TYPO3\Flow\Security\Context $securityContext The security context of the current request
     * @return void
     */
    public function setSecurityContext(\TYPO3\Flow\Security\Context $securityContext);

    /**
     * Returns the security context
     *
     * @return \TYPO3\Flow\Security\Context $securityContext The security context of the current request
     */
    public function getSecurityContext();

    /**
     * Tries to authenticate the tokens in the security context, if needed.
     * (Have a look at the \TYPO3\Flow\Security\Authentication\TokenManager for an implementation example)
     *
     * @return void
     */
    public function authenticate();

    /**
     * Checks if at least one token is authenticated
     *
     * @return boolean
     */
    public function isAuthenticated();

    /**
     * Logs all active authentication tokens out
     *
     * @return void
     */
    public function logout();
}
