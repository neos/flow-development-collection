<?php
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
use Neos\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for username/password authentication provider. The account are stored in the CR.
 */
class PersistedUsernamePasswordProviderTest extends UnitTestCase
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
     * @var PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

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


    protected function setUp(): void
    {
        $this->mockHashService = $this->createMock(Security\Cryptography\HashService::class);
        $this->mockAccount = $this->getMockBuilder(Security\Account::class)->disableOriginalConstructor()->getMock();
        $this->mockAccountRepository = $this->getMockBuilder(Security\AccountRepository::class)->disableOriginalConstructor()->getMock();
        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->mockToken = $this->getMockBuilder(Security\Authentication\Token\UsernamePassword::class)->disableOriginalConstructor()->getMock();

        $this->mockSecurityContext = $this->createMock(Security\Context::class);
        $this->mockSecurityContext->expects(self::any())->method('withoutAuthorizationChecks')->will(self::returnCallBack(function ($callback) {
            return $callback->__invoke();
        }));

        $this->persistedUsernamePasswordProvider = $this->getAccessibleMock(Security\Authentication\Provider\PersistedUsernamePasswordProvider::class, ['dummy'], [], '', false);
        $this->persistedUsernamePasswordProvider->_set('name', 'myProvider');
        $this->persistedUsernamePasswordProvider->_set('options', []);
        $this->persistedUsernamePasswordProvider->_set('hashService', $this->mockHashService);
        $this->persistedUsernamePasswordProvider->_set('accountRepository', $this->mockAccountRepository);
        $this->persistedUsernamePasswordProvider->_set('persistenceManager', $this->mockPersistenceManager);
        $this->persistedUsernamePasswordProvider->_set('securityContext', $this->mockSecurityContext);
    }

    /**
     * @test
     */
    public function authenticatingAnUsernamePasswordTokenChecksIfTheGivenClearTextPasswordMatchesThePersistedHashedPassword()
    {
        $this->mockHashService->expects(self::once())->method('validatePassword')->with('password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->will(self::returnValue(true));

        $this->mockAccount->expects(self::once())->method('getCredentialsSource')->will(self::returnValue('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086'));

        $this->mockAccountRepository->expects(self::once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'myProvider')->will(self::returnValue($this->mockAccount));

        $this->mockToken->expects(self::atLeastOnce())->method('getUsername')->will(self::returnValue('admin'));
        $this->mockToken->expects(self::atLeastOnce())->method('getPassword')->will(self::returnValue('password'));

        $lastAuthenticationStatus = null;
        $this->mockToken->method('setAuthenticationStatus')->willReturnCallback(static function ($status) use (&$lastAuthenticationStatus) {
            $lastAuthenticationStatus = $status;
        });

        $this->mockToken->expects(self::once())->method('setAccount')->with($this->mockAccount);

        $this->persistedUsernamePasswordProvider->authenticate($this->mockToken);
        self::assertSame(\Neos\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL, $lastAuthenticationStatus);
    }

    /**
     * @test
     */
    public function authenticatingAndUsernamePasswordTokenRespectsTheConfiguredLookupProviderName()
    {
        $this->mockHashService->expects(self::once())->method('validatePassword')->with('password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->will(self::returnValue(true));

        $this->mockAccount->expects(self::once())->method('getCredentialsSource')->will(self::returnValue('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086'));

        $this->mockAccountRepository->expects(self::once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'customLookupName')->will(self::returnValue($this->mockAccount));

        $this->mockToken->expects(self::atLeastOnce())->method('getUsername')->will(self::returnValue('admin'));
        $this->mockToken->expects(self::atLeastOnce())->method('getPassword')->will(self::returnValue('password'));

        $this->mockToken->expects(self::once())->method('setAccount')->with($this->mockAccount);

        $persistedUsernamePasswordProvider = PersistedUsernamePasswordProvider::create('providerName', ['lookupProviderName' => 'customLookupName']);
        $this->inject($persistedUsernamePasswordProvider, 'hashService', $this->mockHashService);
        $this->inject($persistedUsernamePasswordProvider, 'accountRepository', $this->mockAccountRepository);
        $this->inject($persistedUsernamePasswordProvider, 'persistenceManager', $this->mockPersistenceManager);
        $this->inject($persistedUsernamePasswordProvider, 'securityContext', $this->mockSecurityContext);

        $persistedUsernamePasswordProvider->authenticate($this->mockToken);
    }

    /**
     * @test
     */
    public function authenticatingAnUsernamePasswordTokenFetchesAccountWithDisabledAuthorization()
    {
        $this->mockToken->expects(self::atLeastOnce())->method('getUsername')->will(self::returnValue('admin'));
        $this->mockToken->expects(self::atLeastOnce())->method('getPassword')->will(self::returnValue('password'));
        $this->mockSecurityContext->expects(self::once())->method('withoutAuthorizationChecks');
        $this->persistedUsernamePasswordProvider->authenticate($this->mockToken);
    }

    /**
     * @test
     */
    public function authenticationFailsWithWrongCredentialsInAnUsernamePasswordToken()
    {
        $this->mockHashService->expects(self::once())->method('validatePassword')->with('wrong password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->will(self::returnValue(false));

        $this->mockAccount->expects(self::once())->method('getCredentialsSource')->will(self::returnValue('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086'));

        $this->mockAccountRepository->expects(self::once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'myProvider')->will(self::returnValue($this->mockAccount));

        $this->mockToken->expects(self::atLeastOnce())->method('getUsername')->will(self::returnValue('admin'));
        $this->mockToken->expects(self::atLeastOnce())->method('getPassword')->will(self::returnValue('wrong password'));

        $lastAuthenticationStatus = null;
        $this->mockToken->method('setAuthenticationStatus')->willReturnCallback(static function ($status) use (&$lastAuthenticationStatus) {
            $lastAuthenticationStatus = $status;
        });

        $this->persistedUsernamePasswordProvider->authenticate($this->mockToken);
        self::assertSame(\Neos\Flow\Security\Authentication\TokenInterface::WRONG_CREDENTIALS, $lastAuthenticationStatus);
    }

    /**
     * @test
     */
    public function authenticatingAnUnsupportedTokenThrowsAnException()
    {
        $this->expectException(Security\Exception\UnsupportedAuthenticationTokenException::class);
        $someNiceToken = $this->createMock(Security\Authentication\TokenInterface::class);

        $usernamePasswordProvider = Security\Authentication\Provider\PersistedUsernamePasswordProvider::create('myProvider', []);

        $usernamePasswordProvider->authenticate($someNiceToken);
    }

    /**
     * @test
     */
    public function canAuthenticateReturnsTrueOnlyForAnTokenThatHasTheCorrectProviderNameSet()
    {
        $mockToken1 = $this->createMock(Security\Authentication\TokenInterface::class);
        $mockToken1->expects(self::once())->method('getAuthenticationProviderName')->will(self::returnValue('myProvider'));
        $mockToken2 = $this->createMock(Security\Authentication\TokenInterface::class);
        $mockToken2->expects(self::once())->method('getAuthenticationProviderName')->will(self::returnValue('someOtherProvider'));

        $usernamePasswordProvider = Security\Authentication\Provider\PersistedUsernamePasswordProvider::create('myProvider', []);

        self::assertTrue($usernamePasswordProvider->canAuthenticate($mockToken1));
        self::assertFalse($usernamePasswordProvider->canAuthenticate($mockToken2));
    }
}
