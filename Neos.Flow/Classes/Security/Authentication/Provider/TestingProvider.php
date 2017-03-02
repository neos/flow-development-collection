<?php
namespace Neos\Flow\Security\Authentication\Provider;

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
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authentication\Token\TestingToken;
use Neos\Flow\Security\Authentication\TokenInterface;

/**
 * A singleton authentication provider for functional tests with
 * mockable authentication.
 *
 * @Flow\Scope("singleton")
 */
class TestingProvider extends AbstractProvider
{
    /**
     * @var Account
     */
    protected $account;

    /**
     * @var integer
     */
    protected $authenticationStatus = TokenInterface::NO_CREDENTIALS_GIVEN;

    /**
     * Returns the class names of the tokens this provider can authenticate.
     *
     * @return array
     */
    public function getTokenClassNames()
    {
        return [TestingToken::class];
    }

    /**
     * Sets isAuthenticated to TRUE for all tokens.
     *
     * @param TokenInterface $authenticationToken The token to be authenticated
     * @return void
     */
    public function authenticate(TokenInterface $authenticationToken)
    {
        $authenticationToken->setAuthenticationStatus($this->authenticationStatus);
        if ($this->authenticationStatus === TokenInterface::AUTHENTICATION_SUCCESSFUL) {
            $authenticationToken->setAccount($this->account);
        } else {
            $authenticationToken->setAccount(null);
        }
    }

    /**
     * Set the account that will be authenticated
     *
     * @param Account $account
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
        $this->authenticationStatus = TokenInterface::NO_CREDENTIALS_GIVEN;
    }
}
