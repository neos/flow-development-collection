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
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Cryptography\FileBasedSimpleKeyService;
use Neos\Flow\Security\Cryptography\HashService;
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
     * @var PolicyService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPolicyService;

    /**
     * @var FileBasedSimpleKeyService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockFileBasedSimpleKeyService;

    /**
     * @var HashService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHashService;

    /**
     * @var Role|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRole;

    /**
     * @var PasswordToken|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockToken;

    public function setUp()
    {
        $this->mockRole = $this->getMockBuilder(Role::class)->disableOriginalConstructor()->getMock();
        $this->mockRole->expects($this->any())->method('getIdentifier')->will($this->returnValue('Neos.Flow:TestRoleIdentifier'));

        $this->mockPolicyService = $this->getMockBuilder(PolicyService::class)->disableOriginalConstructor()->getMock();
        $this->mockPolicyService->expects($this->any())->method('getRole')->with('Neos.Flow:TestRoleIdentifier')->will($this->returnValue($this->mockRole));

        $this->mockHashService = $this->getMockBuilder(HashService::class)->disableOriginalConstructor()->getMock();

        $expectedPassword = $this->testKeyClearText;
        $expectedHashedPasswordAndSalt = $this->testKeyHashed;
        $this->mockHashService->expects($this->any())->method('validatePassword')->will($this->returnCallback(function ($password, $hashedPasswordAndSalt) use ($expectedPassword, $expectedHashedPasswordAndSalt) {
            return $hashedPasswordAndSalt === $expectedHashedPasswordAndSalt && $password === $expectedPassword;
        }));

        $this->mockFileBasedSimpleKeyService = $this->getMockBuilder(FileBasedSimpleKeyService::class)->disableOriginalConstructor()->getMock();
        $this->mockFileBasedSimpleKeyService->expects($this->any())->method('getKey')->with('testKey')->will($this->returnValue($this->testKeyHashed));

        $this->mockToken = $this->getMockBuilder(PasswordToken::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function authenticatingAPasswordTokenChecksIfTheGivenClearTextPasswordMatchesThePersistedHashedPassword()
    {
        $this->mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(['password' => $this->testKeyClearText]));
        $this->mockToken->expects($this->once())->method('setAuthenticationStatus')->with(TokenInterface::AUTHENTICATION_SUCCESSFUL);

        $authenticationProvider = new FileBasedSimpleKeyProvider('myProvider', ['keyName' => 'testKey', 'authenticateRoles' => ['Neos.Flow:TestRoleIdentifier']]);
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
        $this->mockToken = $this->getMockBuilder(PasswordToken::class)->disableOriginalConstructor()->setMethods(['getCredentials'])->getMock();
        $this->mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(['password' => $this->testKeyClearText]));

        $authenticationProvider = new FileBasedSimpleKeyProvider('myProvider', ['keyName' => 'testKey', 'authenticateRoles' => ['Neos.Flow:TestRoleIdentifier']]);
        $this->inject($authenticationProvider, 'policyService', $this->mockPolicyService);
        $this->inject($authenticationProvider, 'hashService', $this->mockHashService);
        $this->inject($authenticationProvider, 'fileBasedSimpleKeyService', $this->mockFileBasedSimpleKeyService);

        $authenticationProvider->authenticate($this->mockToken);

        $authenticatedRoles = $this->mockToken->getAccount()->getRoles();
        $this->assertTrue(in_array('Neos.Flow:TestRoleIdentifier', array_keys($authenticatedRoles)));
    }

    /**
     * @test
     */
    public function authenticationFailsWithWrongCredentialsInAPasswordToken()
    {
        $this->mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(['password' => 'wrong password']));
        $this->mockToken->expects($this->once())->method('setAuthenticationStatus')->with(TokenInterface::WRONG_CREDENTIALS);

        $authenticationProvider = new FileBasedSimpleKeyProvider('myProvider', ['keyName' => 'testKey', 'authenticateRoles' => ['Neos.Flow:TestRoleIdentifier']]);
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
        $this->mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue([]));
        $this->mockToken->expects($this->once())->method('setAuthenticationStatus')->with(TokenInterface::NO_CREDENTIALS_GIVEN);

        $authenticationProvider = new FileBasedSimpleKeyProvider('myProvider', ['keyName' => 'testKey', 'authenticateRoles' => ['Neos.Flow:TestRoleIdentifier']]);
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
        $authenticationProvider = new FileBasedSimpleKeyProvider('myProvider');
        $this->assertSame($authenticationProvider->getTokenClassNames(), [PasswordToken::class]);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\UnsupportedAuthenticationTokenException
     */
    public function authenticatingAnUnsupportedTokenThrowsAnException()
    {
        $someInvalidToken = $this->createMock(TokenInterface::class);

        $authenticationProvider = new FileBasedSimpleKeyProvider('myProvider');

        $authenticationProvider->authenticate($someInvalidToken);
    }

    /**
     * @test
     */
    public function canAuthenticateReturnsTrueOnlyForAnTokenThatHasTheCorrectProviderNameSet()
    {
        $mockToken1 = $this->createMock(TokenInterface::class);
        $mockToken1->expects($this->once())->method('getAuthenticationProviderName')->will($this->returnValue('myProvider'));
        $mockToken2 = $this->createMock(TokenInterface::class);
        $mockToken2->expects($this->once())->method('getAuthenticationProviderName')->will($this->returnValue('someOtherProvider'));

        $authenticationProvider = new FileBasedSimpleKeyProvider('myProvider');

        $this->assertTrue($authenticationProvider->canAuthenticate($mockToken1));
        $this->assertFalse($authenticationProvider->canAuthenticate($mockToken2));
    }
}
