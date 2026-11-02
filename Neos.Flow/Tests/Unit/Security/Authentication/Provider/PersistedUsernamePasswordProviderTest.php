<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security\Authentication\Provider;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider;
use Neos\Flow\Security\Authentication\Token\UsernamePassword;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Security\Cryptography\PrecomposedHashProvider;
use Neos\Flow\Security\Exception\UnsupportedAuthenticationTokenException;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for username/password authentication provider. The account are stored in the CR.
 */
final class PersistedUsernamePasswordProviderTest extends UnitTestCase
{
    /**
     * @var Security\Cryptography\HashService
     */
    protected $mockHashService;

    /**
     * @var Security\Account
     */
    protected $mockAccount;

    /**
     * @var Security\AccountRepository
     */
    protected $mockAccountRepository;

    /**
     * @var Security\Authentication\Token\UsernamePassword
     */
    protected $mockToken;

    /**
     * @var Security\Context
     */
    protected $mockSecurityContext;

    /**
     * @var Security\Authentication\Provider\PersistedUsernamePasswordProvider
     */
    protected $persistedUsernamePasswordProvider;

    /**
     * @var Security\Cryptography\PrecomposedHashProvider
     */
    protected $mockPrecomposedHashProvider;

    protected function setUp(): void
    {
        $this->mockHashService = $this->createMock(HashService::class);
        $this->mockAccount = $this->createMock(Account::class);
        $this->mockAccountRepository = $this->createMock(AccountRepository::class);
        $this->mockToken = $this->createMock(UsernamePassword::class);
        $this->mockPrecomposedHashProvider = $this->createMock(PrecomposedHashProvider::class);
        $this->mockPrecomposedHashProvider->method('getPrecomposedHash')->willReturn('bcrypt=>$2a$14$mYqRRlg5V2yUDy1bd9vt3Oq8Fa9d508WWazFWE5tcpTGn3G145RAm');

        $this->mockSecurityContext = $this->createMock(Context::class);
        $this->mockSecurityContext->method('withoutAuthorizationChecks')->willReturnCallback(function ($callback) {
            return $callback->__invoke();
        });

        $this->persistedUsernamePasswordProvider = $this->getAccessibleMock(PersistedUsernamePasswordProvider::class, [], [], '', false);
        $this->persistedUsernamePasswordProvider->_set('name', 'myProvider');
        $this->persistedUsernamePasswordProvider->_set('options', []);
        $this->persistedUsernamePasswordProvider->_set('hashService', $this->mockHashService);
        $this->persistedUsernamePasswordProvider->_set('accountRepository', $this->mockAccountRepository);
        $this->persistedUsernamePasswordProvider->_set('persistenceManager', $this->createStub(PersistenceManagerInterface::class));
        $this->persistedUsernamePasswordProvider->_set('securityContext', $this->mockSecurityContext);
        $this->persistedUsernamePasswordProvider->_set('precomposedHashProvider', $this->mockPrecomposedHashProvider);
    }

    #[Test]
    public function authenticatingAnUsernamePasswordTokenChecksIfTheGivenClearTextPasswordMatchesThePersistedHashedPassword()
    {
        $this->mockHashService->expects($this->once())->method('validatePassword')->with('password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->willReturn((true));

        $this->mockAccount->expects($this->once())->method('getCredentialsSource')->willReturn(('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086'));

        $this->mockAccountRepository->expects($this->once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'myProvider')->willReturn(($this->mockAccount));

        $this->mockToken->expects($this->atLeastOnce())->method('getUsername')->willReturn(('admin'));
        $this->mockToken->expects($this->atLeastOnce())->method('getPassword')->willReturn(('password'));

        $lastAuthenticationStatus = null;
        $this->mockToken->method('setAuthenticationStatus')->willReturnCallback(static function ($status) use (&$lastAuthenticationStatus) {
            $lastAuthenticationStatus = $status;
        });

        $this->mockToken->expects($this->once())->method('setAccount')->with($this->mockAccount);

        $this->persistedUsernamePasswordProvider->authenticate($this->mockToken);
        self::assertSame(TokenInterface::AUTHENTICATION_SUCCESSFUL, $lastAuthenticationStatus);
    }

    #[Test]
    public function authenticatingAndUsernamePasswordTokenRespectsTheConfiguredLookupProviderName()
    {
        $this->mockHashService->expects($this->once())->method('validatePassword')->with('password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->willReturn((true));

        $this->mockAccount->expects($this->once())->method('getCredentialsSource')->willReturn(('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086'));

        $this->mockAccountRepository->expects($this->once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'customLookupName')->willReturn(($this->mockAccount));

        $this->mockToken->expects($this->atLeastOnce())->method('getUsername')->willReturn(('admin'));
        $this->mockToken->expects($this->atLeastOnce())->method('getPassword')->willReturn(('password'));

        $this->mockToken->expects($this->once())->method('setAccount')->with($this->mockAccount);

        $persistedUsernamePasswordProvider = PersistedUsernamePasswordProvider::create('providerName', ['lookupProviderName' => 'customLookupName']);
        $this->inject($persistedUsernamePasswordProvider, 'hashService', $this->mockHashService);
        $this->inject($persistedUsernamePasswordProvider, 'accountRepository', $this->mockAccountRepository);
        $this->inject($persistedUsernamePasswordProvider, 'persistenceManager', $this->createStub(PersistenceManagerInterface::class));
        $this->inject($persistedUsernamePasswordProvider, 'securityContext', $this->mockSecurityContext);
        $this->inject($persistedUsernamePasswordProvider, 'precomposedHashProvider', $this->mockPrecomposedHashProvider);

        $persistedUsernamePasswordProvider->authenticate($this->mockToken);
    }

    #[Test]
    public function authenticatingAnUsernamePasswordTokenFetchesAccountWithDisabledAuthorization()
    {
        $this->mockToken->expects($this->atLeastOnce())->method('getUsername')->willReturn(('admin'));
        $this->mockToken->expects($this->atLeastOnce())->method('getPassword')->willReturn(('password'));
        $this->mockSecurityContext->expects($this->once())->method('withoutAuthorizationChecks');
        $this->persistedUsernamePasswordProvider->authenticate($this->mockToken);
    }

    #[Test]
    public function authenticationFailsWithWrongCredentialsInAnUsernamePasswordToken()
    {
        $this->mockHashService->expects($this->once())->method('validatePassword')->with('wrong password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->willReturn((false));

        $this->mockAccount->expects($this->once())->method('getCredentialsSource')->willReturn(('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086'));

        $this->mockAccountRepository->expects($this->once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'myProvider')->willReturn(($this->mockAccount));

        $this->mockToken->expects($this->atLeastOnce())->method('getUsername')->willReturn(('admin'));
        $this->mockToken->expects($this->atLeastOnce())->method('getPassword')->willReturn(('wrong password'));

        $lastAuthenticationStatus = null;
        $this->mockToken->method('setAuthenticationStatus')->willReturnCallback(static function ($status) use (&$lastAuthenticationStatus) {
            $lastAuthenticationStatus = $status;
        });

        $this->persistedUsernamePasswordProvider->authenticate($this->mockToken);
        self::assertSame(TokenInterface::WRONG_CREDENTIALS, $lastAuthenticationStatus);
    }

    #[Test]
    public function authenticatingAnUnsupportedTokenThrowsAnException()
    {
        $this->expectException(UnsupportedAuthenticationTokenException::class);
        $someNiceToken = $this->createStub(TokenInterface::class);

        $usernamePasswordProvider = PersistedUsernamePasswordProvider::create('myProvider', []);

        $usernamePasswordProvider->authenticate($someNiceToken);
    }

    #[Test]
    public function canAuthenticateReturnsTrueOnlyForAnTokenThatHasTheCorrectProviderNameSet()
    {
        $mockToken1 = $this->createMock(TokenInterface::class);
        $mockToken1->expects($this->once())->method('getAuthenticationProviderName')->willReturn(('myProvider'));
        $mockToken2 = $this->createMock(TokenInterface::class);
        $mockToken2->expects($this->once())->method('getAuthenticationProviderName')->willReturn(('someOtherProvider'));

        $usernamePasswordProvider = PersistedUsernamePasswordProvider::create('myProvider', []);

        self::assertTrue($usernamePasswordProvider->canAuthenticate($mockToken1));
        self::assertFalse($usernamePasswordProvider->canAuthenticate($mockToken2));
    }
}
