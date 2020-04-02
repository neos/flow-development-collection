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

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security;
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
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

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


    protected function setUp(): void
    {
        $this->mockHashService = $this->createMock(Security\Cryptography\HashService::class);
        $this->mockAccount = $this->getMockBuilder(Security\Account::class)->disableOriginalConstructor()->getMock();
        $this->mockAccountRepository = $this->getMockBuilder(Security\AccountRepository::class)->disableOriginalConstructor()->getMock();
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->expects(self::any())->method('get')->with(Security\AccountRepository::class)->willReturn($this->mockAccountRepository);
        $this->mockToken = $this->getMockBuilder(Security\Authentication\Token\UsernamePassword::class)->disableOriginalConstructor()->getMock();

        $this->mockSecurityContext = $this->createMock(Security\Context::class);
        $this->mockSecurityContext->expects(self::any())->method('withoutAuthorizationChecks')->will(self::returnCallBack(function ($callback) {
            return $callback->__invoke();
        }));

        $this->persistedUsernamePasswordProvider = $this->getAccessibleMock(Security\Authentication\Provider\PersistedUsernamePasswordProvider::class, ['dummy'], [], '', false);
        $this->persistedUsernamePasswordProvider->_set('name', 'myProvider');
        $this->persistedUsernamePasswordProvider->_set('options', ['accountRepositoryClassName' => Security\AccountRepository::class]);
        $this->persistedUsernamePasswordProvider->_set('hashService', $this->mockHashService);
        $this->persistedUsernamePasswordProvider->_set('objectManager', $this->mockObjectManager);
        $this->persistedUsernamePasswordProvider->_set('accountRepository', $this->mockAccountRepository);
        $this->persistedUsernamePasswordProvider->_set('securityContext', $this->mockSecurityContext);
    }

    /**
     * @test
     */
    public function authenticatingAnUsernamePasswordTokenChecksIfTheGivenClearTextPasswordMatchesThePersistedHashedPassword()
    {
        $this->mockHashService->expects(self::once())->method('validatePassword')->with('password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->will(self::returnValue(true));

        $this->mockAccount->expects(self::once())->method('getCredentialsSource')->will(self::returnValue(Security\Authentication\CredentialsSource::fromString('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')));

        $this->mockAccountRepository->expects(self::once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'myProvider')->will(self::returnValue($this->mockAccount));

        $this->mockToken->expects(self::once())->method('getCredentials')->will(self::returnValue(['username' => 'admin', 'password' => 'password']));
        $this->mockToken->expects(self::at(2))->method('setAuthenticationStatus')->with(\Neos\Flow\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);
        $this->mockToken->expects(self::at(3))->method('setAuthenticationStatus')->with(\Neos\Flow\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);
        $this->mockToken->expects(self::at(4))->method('setAuthenticationStatus')->with(\Neos\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
        $this->mockToken->expects(self::once())->method('setAccount')->with($this->mockAccount);

        $this->persistedUsernamePasswordProvider->authenticate($this->mockToken);
    }

    /**
     * @test
     */
    public function authenticatingAnUsernamePasswordTokenFetchesAccountWithDisabledAuthorization()
    {
        $this->mockToken->expects(self::once())->method('getCredentials')->will(self::returnValue(['username' => 'admin', 'password' => 'password']));
        $this->mockSecurityContext->expects(self::once())->method('withoutAuthorizationChecks');
        $this->persistedUsernamePasswordProvider->authenticate($this->mockToken);
    }

    /**
     * @test
     */
    public function authenticationFailsWithWrongCredentialsInAnUsernamePasswordToken()
    {
        $this->mockHashService->expects(self::once())->method('validatePassword')->with('wrong password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->will(self::returnValue(false));

        $this->mockAccount->expects(self::once())->method('getCredentialsSource')->will(self::returnValue(Security\Authentication\CredentialsSource::fromString('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')));

        $this->mockAccountRepository->expects(self::once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'myProvider')->will(self::returnValue($this->mockAccount));

        $this->mockToken->expects(self::once())->method('getCredentials')->will(self::returnValue(['username' => 'admin', 'password' => 'wrong password']));
        $this->mockToken->expects(self::at(2))->method('setAuthenticationStatus')->with(\Neos\Flow\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);
        $this->mockToken->expects(self::at(3))->method('setAuthenticationStatus')->with(\Neos\Flow\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);

        $this->persistedUsernamePasswordProvider->authenticate($this->mockToken);
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
