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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Account;

/**
 * Contract for an authentication token.
 *
 */
interface TokenInterface
{
    /**
     * This is the default state. The token is not authenticated and holds no credentials, that could be used for authentication.
     */
    const NO_CREDENTIALS_GIVEN = 1;

    /**
     * It was tried to authenticate the token, but the credentials were wrong.
     */
    const WRONG_CREDENTIALS = 2;

    /**
     * The token has been successfully authenticated.
     */
    const AUTHENTICATION_SUCCESSFUL = 3;

    /**
     * This indicates, that the token received credentials, but has not been authenticated yet.
     */
    const AUTHENTICATION_NEEDED = 4;

    /**
     * Returns the name of the authentication provider responsible for this token
     *
     * @return string The authentication provider name
     */
    public function getAuthenticationProviderName();

    /**
     * Sets the name of the authentication provider responsible for this token
     *
     * @param string $authenticationProviderName The authentication provider name
     * @return void
     */
    public function setAuthenticationProviderName($authenticationProviderName);

    /**
     * Returns TRUE if this token is currently authenticated
     *
     * @return boolean TRUE if this this token is currently authenticated
     */
    public function isAuthenticated();

    /**
     * Sets the authentication status. Usually called by the responsible AuthenticationManagerInterface
     *
     * @param integer $authenticationStatus One of NO_CREDENTIALS_GIVEN, WRONG_CREDENTIALS, AUTHENTICATION_SUCCESSFUL
     * @return void
     */
    public function setAuthenticationStatus($authenticationStatus);

    /**
     * Returns the current authentication status
     *
     * @return integer One of NO_CREDENTIALS_GIVEN, WRONG_CREDENTIALS, AUTHENTICATION_SUCCESSFUL, REAUTHENTICATION_NEEDED
     */
    public function getAuthenticationStatus();

    /**
     * Sets the authentication entry point
     *
     * @param EntryPointInterface $entryPoint The authentication entry point
     * @return void
     */
    public function setAuthenticationEntryPoint(EntryPointInterface $entryPoint);

    /**
     * Returns the configured authentication entry point, NULL if none is available
     *
     * @return EntryPointInterface The configured authentication entry point, NULL if none is available
     */
    public function getAuthenticationEntryPoint();

    /**
     * Returns TRUE if \Neos\Flow\Security\RequestPattern were set
     *
     * @return boolean True if a \Neos\Flow\Security\RequestPatternInterface was set
     */
    public function hasRequestPatterns();

    /**
     * Sets request patterns
     *
     * @param array $requestPatterns Array of \Neos\Flow\Security\RequestPatternInterface to be set
     * @return void
     * @see hasRequestPattern()
     */
    public function setRequestPatterns(array $requestPatterns);

    /**
     * Returns an array of set \Neos\Flow\Security\RequestPatternInterface, NULL if none was set
     *
     * @return array Array of set request patterns
     * @see hasRequestPattern()
     */
    public function getRequestPatterns();

    /**
     * Updates the authentication credentials, the authentication manager needs to authenticate this token.
     * This could be a username/password from a login controller.
     * This method is called while initializing the security context. By returning TRUE you
     * make sure that the authentication manager will (re-)authenticate the tokens with the current credentials.
     * Note: You should not persist the credentials!
     *
     * @param ActionRequest $actionRequest The current request instance
     * @return boolean TRUE if this token needs to be (re-)authenticated
     */
    public function updateCredentials(ActionRequest $actionRequest);

    /**
     * Returns the credentials of this token. The type depends on the provider
     * of the token.
     *
     * @return mixed $credentials The needed credentials to authenticate this token
     */
    public function getCredentials();

    /**
     * Returns the account if one is authenticated, NULL otherwise.
     *
     * @return Account An account object
     */
    public function getAccount();

    /**
     * Set the (authenticated) account
     *
     * @param Account $account An account object
     * @return void
     */
    public function setAccount(Account $account = null);

    /**
     * Returns a string representation of the token for logging purposes.
     *
     * @return string A string representation of the token
     */
    public function __toString();
}
