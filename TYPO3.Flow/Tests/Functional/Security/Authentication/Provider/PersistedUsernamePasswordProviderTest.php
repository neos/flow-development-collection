<?php
namespace TYPO3\Flow\Tests\Functional\Security\Authentication\Provider;

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
use TYPO3\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider;

/**
 * Testcase for the persisted username and password provider
 *
 */
class PersistedUsernamePasswordProviderTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    protected $testableSecurityEnabled = true;

    /**
     * @var PersistedUsernamePasswordProvider
     */
    protected $persistedUsernamePasswordProvider;

    /**
     * @var \TYPO3\Flow\Security\AccountFactory
     */
    protected $accountFactory;

    /**
     * @var \TYPO3\Flow\Security\AccountRepository
     */
    protected $accountRepository;

    /**
     * @var \TYPO3\Flow\Security\Authentication\Token\UsernamePassword
     */
    protected $authenticationToken;


    public function setUp()
    {
        parent::setUp();

        $this->persistedUsernamePasswordProvider = new PersistedUsernamePasswordProvider('myTestProvider');
        $this->accountFactory = new \TYPO3\Flow\Security\AccountFactory();
        $this->accountRepository = new \TYPO3\Flow\Security\AccountRepository();

        $this->authenticationToken = $this->getAccessibleMock(\TYPO3\Flow\Security\Authentication\Token\UsernamePassword::class, array('dummy'));

        $account = $this->accountFactory->createAccountWithPassword('username', 'password', array(), 'myTestProvider');
        $this->accountRepository->add($account);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     */
    public function successfulAuthentication()
    {
        $this->authenticationToken->_set('credentials', ['username' => 'username', 'password' => 'password']);

        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        $this->assertTrue($this->authenticationToken->isAuthenticated());

        $account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName('username', 'myTestProvider');
        $this->assertEquals(new \DateTime(), $account->getLastSuccessfulAuthenticationDate());
        $this->assertEquals(0, $account->getFailedAuthenticationCount());
    }

    /**
     * @test
     */
    public function authenticationWithWrongPassword()
    {
        $this->authenticationToken->_set('credentials', ['username' => 'username', 'password' => 'wrongPW']);

        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        $this->assertFalse($this->authenticationToken->isAuthenticated());

        $account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName('username', 'myTestProvider');
        $this->assertEquals(1, $account->getFailedAuthenticationCount());
    }


    /**
     * @test
     */
    public function authenticationWithWrongUserName()
    {
        $this->authenticationToken->_set('credentials', ['username' => 'wrongUsername', 'password' => 'password']);

        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        $this->assertFalse($this->authenticationToken->isAuthenticated());
    }


    /**
     * @test
     */
    public function authenticationWithCorrectCredentialsResetsFailedAuthenticationCount()
    {
        $this->authenticationToken->_set('credentials', ['username' => 'username', 'password' => 'wrongPW']);
        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        $account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName('username', 'myTestProvider');
        $this->assertEquals(1, $account->getFailedAuthenticationCount());

        $this->authenticationToken->_set('credentials', ['username' => 'username', 'password' => 'password']);
        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        $account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName('username', 'myTestProvider');
        $this->assertEquals(new \DateTime(), $account->getLastSuccessfulAuthenticationDate());
        $this->assertEquals(0, $account->getFailedAuthenticationCount());
    }
}
