<?php
namespace TYPO3\Flow\Security\Authentication\Provider;

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

/**
 * A singleton authentication provider for functional tests with
 * mockable authentication.
 *
 * @Flow\Scope("singleton")
 */
class TestingProvider extends \TYPO3\Flow\Security\Authentication\Provider\AbstractProvider
{
    /**
     * @var \TYPO3\Flow\Security\Account
     */
    protected $account;

    /**
     * @var integer
     */
    protected $authenticationStatus = \TYPO3\Flow\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN;

    /**
     * Returns the class names of the tokens this provider can authenticate.
     *
     * @return array
     */
    public function getTokenClassNames()
    {
        return array(\TYPO3\Flow\Security\Authentication\Token\TestingToken::class);
    }

    /**
     * Sets isAuthenticated to TRUE for all tokens.
     *
     * @param \TYPO3\Flow\Security\Authentication\TokenInterface $authenticationToken The token to be authenticated
     * @return void
     */
    public function authenticate(\TYPO3\Flow\Security\Authentication\TokenInterface $authenticationToken)
    {
        $authenticationToken->setAuthenticationStatus($this->authenticationStatus);
        if ($this->authenticationStatus === \TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL) {
            $authenticationToken->setAccount($this->account);
        } else {
            $authenticationToken->setAccount(null);
        }
    }

    /**
     * Set the account that will be authenticated
     *
     * @param \TYPO3\Flow\Security\Account $account
     * @return void
     */
    public function setAccount($account)
    {
        $this->account = $account;
    }

    /**
     * Set the authentication status for authentication
     *
     * @param integer $authenticationStatus
     * @return void
     */
    public function setAuthenticationStatus($authenticationStatus)
    {
        $this->authenticationStatus = $authenticationStatus;
    }

    /**
     * Set the provider name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Reset the authentication status and account
     *
     * @return void
     */
    public function reset()
    {
        $this->account = null;
        $this->authenticationStatus = \TYPO3\Flow\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN;
    }
}
