<?php

declare(strict_types=1);

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
use Neos\Flow\Security;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\AccountFactory;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider;
use Neos\Flow\Security\Authentication\Token\UsernamePassword;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the persisted username and password provider
 */
final class PersistedUsernamePasswordProviderTest extends FunctionalTestCase
{
    protected $testableSecurityEnabled = true;

    /**
     * @var PersistedUsernamePasswordProvider
     */
    protected $persistedUsernamePasswordProvider;

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
        $accountFactory = new AccountFactory();
        $this->accountRepository = new AccountRepository();

        $this->authenticationToken = new class () extends UsernamePassword {
            public function _setCredentials(array $credentials): void
            {
                $this->credentials = $credentials;
            }
        };

        $account = $accountFactory->createAccountWithPassword('username', 'password', [], 'myTestProvider');
        $this->accountRepository->add($account);
        $this->persistenceManager->persistAll();
    }

    #[Test]
    public function successfulAuthentication(): void
    {
        self::markTestIncomplete('needs to be updated, dies silently…');
        $this->authenticationToken->_setCredentials(['username' => 'username', 'password' => 'password']);

        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        self::assertTrue($this->authenticationToken->isAuthenticated());

        $account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName('username', 'myTestProvider');
        $this->assertInstanceOf(Account::class, $account);
        self::assertNotNull($account->getLastSuccessfulAuthenticationDate());
        self::assertSame(0, $account->getFailedAuthenticationCount());
    }

    #[Test]
    public function authenticationWithWrongPassword(): void
    {
        self::markTestIncomplete('needs to be updated, dies silently…');
        $this->authenticationToken->_setCredentials(['username' => 'username', 'password' => 'wrongPW']);

        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        self::assertFalse($this->authenticationToken->isAuthenticated());

        $account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName('username', 'myTestProvider');
        $this->assertInstanceOf(Account::class, $account);
        self::assertSame(1, $account->getFailedAuthenticationCount());
    }


    #[Test]
    public function authenticationWithWrongUserName(): void
    {
        self::markTestIncomplete('needs to be updated, dies silently…');
        $this->authenticationToken->_setCredentials(['username' => 'wrongUsername', 'password' => 'password']);

        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        self::assertFalse($this->authenticationToken->isAuthenticated());
    }


    #[Test]
    public function authenticationWithCorrectCredentialsResetsFailedAuthenticationCount(): void
    {
        self::markTestIncomplete('needs to be updated, dies silently…');
        $this->authenticationToken->_setCredentials(['username' => 'username', 'password' => 'wrongPW']);
        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        $account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName('username', 'myTestProvider');
        $this->assertInstanceOf(Account::class, $account);
        self::assertSame(1, $account->getFailedAuthenticationCount());

        $this->authenticationToken->_setCredentials(['username' => 'username', 'password' => 'password']);
        $this->persistedUsernamePasswordProvider->authenticate($this->authenticationToken);

        $account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName('username', 'myTestProvider');
        $this->assertInstanceOf(Account::class, $account);
        self::assertNotNull($account->getLastSuccessfulAuthenticationDate());
        self::assertSame(0, $account->getFailedAuthenticationCount());
    }
}
