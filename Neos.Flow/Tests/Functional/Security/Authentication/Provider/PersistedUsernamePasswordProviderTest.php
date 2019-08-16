<?php
namespace Neos\Flow\Tests\Functional\Security\Authentication\Provider;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Security;

/**
 * Testcase for the persisted username and password provider
 */
class PersistedUsernamePasswordProviderTest extends FunctionalTestCase
{
    protected $testableSecurityEnabled = true;

    /**
     * @var PersistedUsernamePasswordProvider
     */
    protected $persistedUsernamePasswordProvider;

    /**
     * @var Security\AccountFactory
     */
    protected $accountFactory;

    /**
     * @var Security\AccountRepository
     */
    protected $accountRepository;

    /**
     * @var Security\Authentication\Token\UsernamePassword
     */
    protected $authenticationToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->persistedUsernamePasswordProvider = PersistedUsernamePasswordProvider::create('myTestProvider', []);
        $this->accountFactory = new Security\AccountFactory();
        $this->accountRepository = new Security\AccountRepository();

        $this->authenticationToken = $this->getAccessibleMock(Security\Authentication\Token\UsernamePassword::class, ['dummy']);

        $account = $this->accountFactory->createAccountWithPassword('username', 'password', [], 'myTestProvider');
        $this->accountRepository->add($account);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     */
    public function successfulAuthentication(): void
    {
        $this->authenticationToken->_set('credentials', ['username' => 'username', 'password' => 'password']);

        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        self::assertTrue($this->authenticationToken->isAuthenticated());

        $account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName('username', 'myTestProvider');
        self::assertEquals((new \DateTime())->format(\DateTime::W3C), $account->getLastSuccessfulAuthenticationDate()->format(\DateTime::W3C));
        self::assertEquals(0, $account->getFailedAuthenticationCount());
    }

    /**
     * @test
     */
    public function authenticationWithWrongPassword(): void
    {
        $this->authenticationToken->_set('credentials', ['username' => 'username', 'password' => 'wrongPW']);

        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        self::assertFalse($this->authenticationToken->isAuthenticated());

        $account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName('username', 'myTestProvider');
        self::assertEquals(1, $account->getFailedAuthenticationCount());
    }


    /**
     * @test
     */
    public function authenticationWithWrongUserName(): void
    {
        $this->authenticationToken->_set('credentials', ['username' => 'wrongUsername', 'password' => 'password']);

        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        self::assertFalse($this->authenticationToken->isAuthenticated());
    }


    /**
     * @test
     */
    public function authenticationWithCorrectCredentialsResetsFailedAuthenticationCount(): void
    {
        $this->authenticationToken->_set('credentials', ['username' => 'username', 'password' => 'wrongPW']);
        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        $account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName('username', 'myTestProvider');
        self::assertEquals(1, $account->getFailedAuthenticationCount());

        $expectedResetDateTime = new \DateTimeImmutable();

        $this->authenticationToken->_set('credentials', ['username' => 'username', 'password' => 'password']);
        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        $account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName('username', 'myTestProvider');
        self::assertGreaterThanOrEqual($expectedResetDateTime->format('U'), $account->getLastSuccessfulAuthenticationDate()->format('U'));
        self::assertEquals(0, $account->getFailedAuthenticationCount());
    }
}
