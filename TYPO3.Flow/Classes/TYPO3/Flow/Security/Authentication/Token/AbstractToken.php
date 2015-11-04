<?php
namespace TYPO3\Flow\Security\Authentication\Token;

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
use TYPO3\Flow\Security\RequestPatternInterface;

/**
 * An abstract authentication token.
 */
abstract class AbstractToken implements \TYPO3\Flow\Security\Authentication\TokenInterface
{
    /**
     * @var string
     */
    protected $authenticationProviderName;

    /**
     * Current authentication status of this token
     * @var integer
     */
    protected $authenticationStatus = self::NO_CREDENTIALS_GIVEN;

    /**
     * The credentials submitted by the client
     * @var array
     * @Flow\Transient
     */
    protected $credentials = array();

    /**
     * @var \TYPO3\Flow\Security\Account
     */
    protected $account;

    /**
     * @var array
     */
    protected $requestPatterns = array();

    /**
     * The authentication entry point
     * @var \TYPO3\Flow\Security\Authentication\EntryPointInterface
     */
    protected $entryPoint = null;

    /**
     * Returns the name of the authentication provider responsible for this token
     *
     * @return string The authentication provider name
     */
    public function getAuthenticationProviderName()
    {
        return $this->authenticationProviderName;
    }

    /**
     * Sets the name of the authentication provider responsible for this token
     *
     * @param string $authenticationProviderName The authentication provider name
     * @return void
     */
    public function setAuthenticationProviderName($authenticationProviderName)
    {
        $this->authenticationProviderName = $authenticationProviderName;
    }

    /**
     * Returns TRUE if this token is currently authenticated
     *
     * @return boolean TRUE if this this token is currently authenticated
     */
    public function isAuthenticated()
    {
        return ($this->authenticationStatus === self::AUTHENTICATION_SUCCESSFUL);
    }

    /**
     * Sets the authentication entry point
     *
     * @param \TYPO3\Flow\Security\Authentication\EntryPointInterface $entryPoint The authentication entry point
     * @return void
     */
    public function setAuthenticationEntryPoint(\TYPO3\Flow\Security\Authentication\EntryPointInterface $entryPoint)
    {
        $this->entryPoint = $entryPoint;
    }

    /**
     * Returns the configured authentication entry point, NULL if none is available
     *
     * @return \TYPO3\Flow\Security\Authentication\EntryPointInterface The configured authentication entry point, NULL if none is available
     */
    public function getAuthenticationEntryPoint()
    {
        return $this->entryPoint;
    }

    /**
     * Returns TRUE if any request pattern has been defined
     *
     * @return boolean
     */
    public function hasRequestPatterns()
    {
        return ($this->requestPatterns !== array());
    }

    /**
     * Sets request patterns
     *
     * @param array $requestPatterns Array of RequestPatternInterface to be set
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setRequestPatterns(array $requestPatterns)
    {
        foreach ($requestPatterns as $requestPattern) {
            if (!$requestPattern instanceof RequestPatternInterface) {
                throw new \InvalidArgumentException(sprintf('Invalid request pattern passed to token of type "%s"', get_class($this)), 1327398366);
            }
        }
        $this->requestPatterns = $requestPatterns;
    }

    /**
     * Returns an array of set \TYPO3\Flow\Security\RequestPatternInterface, NULL if none was set
     *
     * @return array Array of set request patterns
     * @see hasRequestPattern()
     */
    public function getRequestPatterns()
    {
        return $this->requestPatterns;
    }

    /**
     * Returns the credentials (username and password) of this token.
     *
     * @return array $credentials The needed credentials to authenticate this token
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * Returns the account if one is authenticated, NULL otherwise.
     *
     * @return \TYPO3\Flow\Security\Account An account object
     */
    public function getAccount()
    {
        return $this->isAuthenticated() ? $this->account: null;
    }

    /**
     * Set the (authenticated) account
     *
     * @param \TYPO3\Flow\Security\Account $account An account object
     * @return void
     */
    public function setAccount(\TYPO3\Flow\Security\Account $account = null)
    {
        $this->account = $account;
    }

    /**
     * Sets the authentication status. Usually called by the responsible \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
     *
     * @param integer $authenticationStatus One of NO_CREDENTIALS_GIVEN, WRONG_CREDENTIALS, AUTHENTICATION_SUCCESSFUL, AUTHENTICATION_NEEDED
     * @return void
     * @throws \TYPO3\Flow\Security\Exception\InvalidAuthenticationStatusException
     */
    public function setAuthenticationStatus($authenticationStatus)
    {
        if (!in_array($authenticationStatus, array(self::NO_CREDENTIALS_GIVEN, self::WRONG_CREDENTIALS, self::AUTHENTICATION_SUCCESSFUL, self::AUTHENTICATION_NEEDED))) {
            throw new \TYPO3\Flow\Security\Exception\InvalidAuthenticationStatusException('Invalid authentication status.', 1237224453);
        }
        $this->authenticationStatus = $authenticationStatus;
    }

    /**
     * Returns the current authentication status
     *
     * @return integer One of NO_CREDENTIALS_GIVEN, WRONG_CREDENTIALS, AUTHENTICATION_SUCCESSFUL, AUTHENTICATION_NEEDED
     */
    public function getAuthenticationStatus()
    {
        return $this->authenticationStatus;
    }

    /**
     * Returns a string representation of the token for logging purposes.
     *
     * @return string The class name
     */
    public function __toString()
    {
        return get_class($this);
    }
}
