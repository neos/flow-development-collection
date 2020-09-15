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

use Neos\Flow\Security\Authentication\Provider\FileBasedSimpleKeyProvider;
use Neos\Flow\Security\Authentication\Token\PasswordToken;
use Neos\Flow\Security\Authentication\Token\PasswordTokenInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Cryptography\FileBasedSimpleKeyService;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Security\Exception\UnsupportedAuthenticationTokenException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for file based simple key authentication provider.
 *
 */
class FileBasedSimpleKeyProviderTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $testKeyClearText = 'password';

    /**
     * @var string
     */
    protected $testKeyHashed = 'pbkdf2=>DPIFYou4eD8=,nMRkJ9708Ryq3zIZcCLQrBiLQ0ktNfG8tVRJoKPTGcG/6N+tyzQHObfH5y5HCra1hAVTBrbgfMjPU6BipIe9xg==%';

    /**
     * @var PolicyService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPolicyService;

    /**
     * @var FileBasedSimpleKeyService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockFileBasedSimpleKeyService;

    /**
     * @var HashService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHashService;

    /**
     * @var Role|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockRole;

    /**
     * @var PasswordToken|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockToken;

    protected function setUp(): void
    {
        $this->mockRole = $this->getMockBuilder(Role::class)->disableOriginalConstructor()->getMock();
        $this->mockRole->expects(self::any())->method('getIdentifier')->will(self::returnValue('Neos.Flow:TestRoleIdentifier'));

        $this->mockPolicyService = $this->getMockBuilder(PolicyService::class)->disableOriginalConstructor()->getMock();
        $this->mockPolicyService->expects(self::any())->method('getRole')->with('Neos.Flow:TestRoleIdentifier')->will(self::returnValue($this->mockRole));

        $this->mockHashService = $this->getMockBuilder(HashService::class)->disableOriginalConstructor()->getMock();

        $expectedPassword = $this->testKeyClearText;
        $expectedHashedPasswordAndSalt = $this->testKeyHashed;
        $this->mockHashService->expects(self::any())->method('validatePassword')->will(self::returnCallBack(function ($password, $hashedPasswordAndSalt) use ($expectedPassword, $expectedHashedPasswordAndSalt) {
            return $hashedPasswordAndSalt === $expectedHashedPasswordAndSalt && $password === $expectedPassword;
        }));

        $this->mockFileBasedSimpleKeyService = $this->getMockBuilder(FileBasedSimpleKeyService::class)->disableOriginalConstructor()->getMock();
        $this->mockFileBasedSimpleKeyService->expects(self::any())->method('getKey')->with('testKey')->will(self::returnValue($this->testKeyHashed));

        $this->mockToken = $this->getMockBuilder(PasswordToken::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function authenticatingAPasswordTokenChecksIfTheGivenClearTextPasswordMatchesThePersistedHashedPassword()
    {
        $this->mockToken->expects(self::atLeastOnce())->method('getPassword')->will(self::returnValue($this->testKeyClearText));
        $this->mockToken->expects(self::once())->method('setAuthenticationStatus')->with(TokenInterface::AUTHENTICATION_SUCCESSFUL);

        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', ['keyName' => 'testKey', 'authenticateRoles' => ['Neos.Flow:TestRoleIdentifier']]);
        $this->inject($authenticationProvider, 'policyService', $this->mockPolicyService);
        $this->inject($authenticationProvider, 'hashService', $this->mockHashService);
        $this->inject($authenticationProvider, 'fileBasedSimpleKeyService', $this->mockFileBasedSimpleKeyService);

        $authenticationProvider->authenticate($this->mockToken);
    }

    /**
     * @test
     */
    public function authenticationAddsAnAccountHoldingTheConfiguredRoles()
    {
        $this->mockToken = $this->getMockBuilder(PasswordToken::class)->disableOriginalConstructor()->setMethods(['getPassword'])->getMock();
        $this->mockToken->expects(self::atLeastOnce())->method('getPassword')->will(self::returnValue($this->testKeyClearText));

        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', ['keyName' => 'testKey', 'authenticateRoles' => ['Neos.Flow:TestRoleIdentifier']]);
        $this->inject($authenticationProvider, 'policyService', $this->mockPolicyService);
        $this->inject($authenticationProvider, 'hashService', $this->mockHashService);
        $this->inject($authenticationProvider, 'fileBasedSimpleKeyService', $this->mockFileBasedSimpleKeyService);

        $authenticationProvider->authenticate($this->mockToken);

        $authenticatedRoles = $this->mockToken->getAccount()->getRoles();
        self::assertTrue(in_array('Neos.Flow:TestRoleIdentifier', array_keys($authenticatedRoles)));
    }

    /**
     * @test
     */
    public function authenticationFailsWithWrongCredentialsInAPasswordToken()
    {
        $this->mockToken->expects(self::atLeastOnce())->method('getPassword')->will(self::returnValue('wrong password'));
        $this->mockToken->expects(self::once())->method('setAuthenticationStatus')->with(TokenInterface::WRONG_CREDENTIALS);

        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', ['keyName' => 'testKey', 'authenticateRoles' => ['Neos.Flow:TestRoleIdentifier']]);
        $this->inject($authenticationProvider, 'policyService', $this->mockPolicyService);
        $this->inject($authenticationProvider, 'hashService', $this->mockHashService);
        $this->inject($authenticationProvider, 'fileBasedSimpleKeyService', $this->mockFileBasedSimpleKeyService);

        $authenticationProvider->authenticate($this->mockToken);
    }

    /**
     * @test
     */
    public function authenticationIsSkippedIfNoCredentialsInAPasswordToken()
    {
        $this->mockToken->expects(self::atLeastOnce())->method('getPassword')->will(self::returnValue(''));
        $this->mockToken->expects(self::once())->method('setAuthenticationStatus')->with(TokenInterface::NO_CREDENTIALS_GIVEN);

        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', ['keyName' => 'testKey', 'authenticateRoles' => ['Neos.Flow:TestRoleIdentifier']]);
        $this->inject($authenticationProvider, 'policyService', $this->mockPolicyService);
        $this->inject($authenticationProvider, 'hashService', $this->mockHashService);
        $this->inject($authenticationProvider, 'fileBasedSimpleKeyService', $this->mockFileBasedSimpleKeyService);

        $authenticationProvider->authenticate($this->mockToken);
    }

    /**
     * @test
     */
    public function getTokenClassNameReturnsCorrectClassNames()
    {
        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', []);
        self::assertSame($authenticationProvider->getTokenClassNames(), [PasswordTokenInterface::class]);
    }

    /**
     * @test
     */
    public function authenticatingAnUnsupportedTokenThrowsAnException()
    {
        $this->expectException(UnsupportedAuthenticationTokenException::class);
        $someInvalidToken = $this->createMock(TokenInterface::class);

        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', []);

        $authenticationProvider->authenticate($someInvalidToken);
    }

    /**
     * @test
     */
    public function canAuthenticateReturnsTrueOnlyForAnTokenThatHasTheCorrectProviderNameSet()
    {
        $mockToken1 = $this->createMock(TokenInterface::class);
        $mockToken1->expects(self::once())->method('getAuthenticationProviderName')->will(self::returnValue('myProvider'));
        $mockToken2 = $this->createMock(TokenInterface::class);
        $mockToken2->expects(self::once())->method('getAuthenticationProviderName')->will(self::returnValue('someOtherProvider'));

        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', []);

        self::assertTrue($authenticationProvider->canAuthenticate($mockToken1));
        self::assertFalse($authenticationProvider->canAuthenticate($mockToken2));
    }
}
