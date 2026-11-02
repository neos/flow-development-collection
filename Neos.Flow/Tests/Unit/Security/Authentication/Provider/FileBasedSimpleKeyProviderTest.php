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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for file based simple key authentication provider.
 *
 */
final class FileBasedSimpleKeyProviderTest extends UnitTestCase
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
     * @var PolicyService|MockObject
     */
    protected $mockPolicyService;

    /**
     * @var FileBasedSimpleKeyService|MockObject
     */
    protected $mockFileBasedSimpleKeyService;

    /**
     * @var HashService|MockObject
     */
    protected $mockHashService;

    /**
     * @var PasswordToken|MockObject
     */
    protected $mockToken;

    protected function setUp(): void
    {
        $mockRole = $this->createMock(Role::class);
        $mockRole->method('getIdentifier')->willReturn(('Neos.Flow:TestRoleIdentifier'));

        $this->mockPolicyService = $this->createMock(PolicyService::class);
        $this->mockPolicyService->method('getRole')->with('Neos.Flow:TestRoleIdentifier')->willReturn(($mockRole));

        $this->mockHashService = $this->createMock(HashService::class);

        $expectedPassword = $this->testKeyClearText;
        $expectedHashedPasswordAndSalt = $this->testKeyHashed;
        $this->mockHashService->method('validatePassword')->willReturnCallback(function ($password, $hashedPasswordAndSalt) use ($expectedPassword, $expectedHashedPasswordAndSalt) {
            return $hashedPasswordAndSalt === $expectedHashedPasswordAndSalt && $password === $expectedPassword;
        });

        $this->mockFileBasedSimpleKeyService = $this->createMock(FileBasedSimpleKeyService::class);
        $this->mockFileBasedSimpleKeyService->method('getKey')->with('testKey')->willReturn(($this->testKeyHashed));

        $this->mockToken = $this->createMock(PasswordToken::class);
    }

    #[Test]
    public function authenticatingAPasswordTokenChecksIfTheGivenClearTextPasswordMatchesThePersistedHashedPassword()
    {
        $this->mockToken->expects($this->atLeastOnce())->method('getPassword')->willReturn(($this->testKeyClearText));
        $this->mockToken->expects($this->once())->method('setAuthenticationStatus')->with(TokenInterface::AUTHENTICATION_SUCCESSFUL);

        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', ['keyName' => 'testKey', 'authenticateRoles' => ['Neos.Flow:TestRoleIdentifier']]);
        $this->inject($authenticationProvider, 'policyService', $this->mockPolicyService);
        $this->inject($authenticationProvider, 'hashService', $this->mockHashService);
        $this->inject($authenticationProvider, 'fileBasedSimpleKeyService', $this->mockFileBasedSimpleKeyService);

        $authenticationProvider->authenticate($this->mockToken);
    }

    #[Test]
    public function authenticationAddsAnAccountHoldingTheConfiguredRoles()
    {
        $this->mockToken = $this->getMockBuilder(PasswordToken::class)->disableOriginalConstructor()->onlyMethods(['getPassword'])->getMock();
        $this->mockToken->expects($this->atLeastOnce())->method('getPassword')->willReturn(($this->testKeyClearText));

        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', ['keyName' => 'testKey', 'authenticateRoles' => ['Neos.Flow:TestRoleIdentifier']]);
        $this->inject($authenticationProvider, 'policyService', $this->mockPolicyService);
        $this->inject($authenticationProvider, 'hashService', $this->mockHashService);
        $this->inject($authenticationProvider, 'fileBasedSimpleKeyService', $this->mockFileBasedSimpleKeyService);

        $authenticationProvider->authenticate($this->mockToken);

        $authenticatedRoles = $this->mockToken->getAccount()->getRoles();
        self::assertContains('Neos.Flow:TestRoleIdentifier', array_keys($authenticatedRoles));
    }

    #[Test]
    public function authenticationFailsWithWrongCredentialsInAPasswordToken()
    {
        $this->mockToken->expects($this->atLeastOnce())->method('getPassword')->willReturn(('wrong password'));
        $this->mockToken->expects($this->once())->method('setAuthenticationStatus')->with(TokenInterface::WRONG_CREDENTIALS);

        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', ['keyName' => 'testKey', 'authenticateRoles' => ['Neos.Flow:TestRoleIdentifier']]);
        $this->inject($authenticationProvider, 'policyService', $this->mockPolicyService);
        $this->inject($authenticationProvider, 'hashService', $this->mockHashService);
        $this->inject($authenticationProvider, 'fileBasedSimpleKeyService', $this->mockFileBasedSimpleKeyService);

        $authenticationProvider->authenticate($this->mockToken);
    }

    #[Test]
    public function authenticationIsSkippedIfNoCredentialsInAPasswordToken()
    {
        $this->mockToken->expects($this->atLeastOnce())->method('getPassword')->willReturn((''));
        $this->mockToken->expects($this->once())->method('setAuthenticationStatus')->with(TokenInterface::NO_CREDENTIALS_GIVEN);

        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', ['keyName' => 'testKey', 'authenticateRoles' => ['Neos.Flow:TestRoleIdentifier']]);
        $this->inject($authenticationProvider, 'policyService', $this->mockPolicyService);
        $this->inject($authenticationProvider, 'hashService', $this->mockHashService);
        $this->inject($authenticationProvider, 'fileBasedSimpleKeyService', $this->mockFileBasedSimpleKeyService);

        $authenticationProvider->authenticate($this->mockToken);
    }

    #[Test]
    public function getTokenClassNameReturnsCorrectClassNames()
    {
        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', []);
        self::assertSame($authenticationProvider->getTokenClassNames(), [PasswordTokenInterface::class]);
    }

    #[Test]
    public function authenticatingAnUnsupportedTokenThrowsAnException()
    {
        $this->expectException(UnsupportedAuthenticationTokenException::class);
        $someInvalidToken = $this->createStub(TokenInterface::class);

        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', []);

        $authenticationProvider->authenticate($someInvalidToken);
    }

    #[Test]
    public function canAuthenticateReturnsTrueOnlyForAnTokenThatHasTheCorrectProviderNameSet()
    {
        $mockToken1 = $this->createMock(TokenInterface::class);
        $mockToken1->expects($this->once())->method('getAuthenticationProviderName')->willReturn(('myProvider'));
        $mockToken2 = $this->createMock(TokenInterface::class);
        $mockToken2->expects($this->once())->method('getAuthenticationProviderName')->willReturn(('someOtherProvider'));

        $authenticationProvider = FileBasedSimpleKeyProvider::create('myProvider', []);

        self::assertTrue($authenticationProvider->canAuthenticate($mockToken1));
        self::assertFalse($authenticationProvider->canAuthenticate($mockToken2));
    }
}
