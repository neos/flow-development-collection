<?php

declare(strict_types=1);

namespace Neos\Eel\Tests\Unit;

/*                                                                        *
 * This script belongs to the Flow package "Neos.Eel".                   *
 *                                                                        */
use Neos\Eel\Helper\SecurityHelper;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Eel SecurityHelper test
 */
final class SecurityHelperTest extends UnitTestCase
{
    #[Test]
    public function csrfTokenIsReturnedFromTheSecurityContext()
    {
        $mockSecurityContext = $this->createMock(Context::class);
        $mockSecurityContext->method('getCsrfProtectionToken')->willReturn('TheCsrfToken');

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertSame('TheCsrfToken', $helper->csrfToken());
    }

    #[Test]
    public function isAuthenticatedReturnsTrueIfAnAuthenticatedTokenIsPresent()
    {
        $mockUnautenticatedAuthenticationToken = $this->createMock(TokenInterface::class);
        $mockUnautenticatedAuthenticationToken->expects($this->once())->method('isAuthenticated')->willReturn((false));

        $mockAutenticatedAuthenticationToken = $this->createMock(TokenInterface::class);
        $mockAutenticatedAuthenticationToken->expects($this->once())->method('isAuthenticated')->willReturn((true));

        $mockSecurityContext = $this->createMock(Context::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->willReturn((true));
        $mockSecurityContext->expects($this->once())->method('getAuthenticationTokens')->willReturn(([
            $mockUnautenticatedAuthenticationToken,
            $mockAutenticatedAuthenticationToken
        ]));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertTrue($helper->isAuthenticated());
    }

    #[Test]
    public function isAuthenticatedReturnsFalseIfNoAuthenticatedTokenIsPresent()
    {
        $mockUnautenticatedAuthenticationToken = $this->createMock(TokenInterface::class);
        $mockUnautenticatedAuthenticationToken->expects($this->once())->method('isAuthenticated')->willReturn((false));

        $mockSecurityContext = $this->createMock(Context::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->willReturn((true));
        $mockSecurityContext->expects($this->once())->method('getAuthenticationTokens')->willReturn(([
            $mockUnautenticatedAuthenticationToken
        ]));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertFalse($helper->isAuthenticated());
    }

    #[Test]
    public function isAuthenticatedReturnsFalseIfNoAuthenticatedTokensAre()
    {
        $mockSecurityContext = $this->createMock(Context::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->willReturn((true));
        $mockSecurityContext->expects($this->once())->method('getAuthenticationTokens')->willReturn(([]));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertFalse($helper->isAuthenticated());
    }

    #[Test]
    public function isAuthenticatedReturnsFalseIfSecurityContextCannotBeInitialized()
    {
        $mockSecurityContext = $this->createMock(Context::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->willReturn((false));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertFalse($helper->isAuthenticated());
    }

    #[Test]
    public function hasAccessToPrivilegeTargetReturnsTrueIfAccessIsAllowed()
    {
        $mockSecurityContext = $this->createMock(Context::class);
        $mockPrivilegeManager = $this->createMock(PrivilegeManagerInterface::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->willReturn((true));
        $mockPrivilegeManager->expects($this->once())->method('isPrivilegeTargetGranted')->with('somePrivilegeTarget')->willReturn((true));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);
        $this->inject($helper, 'privilegeManager', $mockPrivilegeManager);

        self::assertTrue($helper->hasAccess('somePrivilegeTarget', []));
    }

    #[Test]
    public function hasAccessToPrivilegeTargetReturnsFalseIfAccessIsForbidden()
    {
        $mockSecurityContext = $this->createMock(Context::class);
        $mockPrivilegeManager = $this->createMock(PrivilegeManagerInterface::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->willReturn((true));
        $mockPrivilegeManager->expects($this->once())->method('isPrivilegeTargetGranted')->with('somePrivilegeTarget')->willReturn((false));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);
        $this->inject($helper, 'privilegeManager', $mockPrivilegeManager);

        self::assertFalse($helper->hasAccess('somePrivilegeTarget', []));
    }

    #[Test]
    public function hasAccessToPrivilegeTargetReturnsFalseIfSecurityContextCannotBeInitialized()
    {
        $mockSecurityContext = $this->createMock(Context::class);
        $mockPrivilegeManager = $this->createStub(PrivilegeManagerInterface::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->willReturn((false));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);
        $this->inject($helper, 'privilegeManager', $mockPrivilegeManager);

        self::assertFalse($helper->hasAccess('somePrivilegeTarget', []));
    }

    #[Test]
    public function getAccountReturnsNullIfSecurityContextCannotBeInitialized()
    {
        $mockSecurityContext = $this->createMock(Context::class);
        $mockSecurityContext->method('canBeInitialized')->willReturn(false);

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertNull($helper->getAccount());
    }

    #[Test]
    public function getAccountDelegatesToSecurityContextIfSecurityContextCanBeInitialized()
    {
        $mockSecurityContext = $this->createMock(Context::class);
        $mockSecurityContext->method('canBeInitialized')->willReturn(true);
        $mockSecurityContext->expects($this->atLeastOnce())->method('getAccount')->willReturn('this would be an account instance');

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertSame('this would be an account instance', $helper->getAccount());
    }

    #[Test]
    public function hasRoleReturnsTrueForEverybodyRole()
    {
        $helper = new SecurityHelper();
        self::assertTrue($helper->hasRole('Neos.Flow:Everybody'));
    }

    #[Test]
    public function hasRoleReturnsFalseIfSecurityContextCannotBeInitialized()
    {
        $mockSecurityContext = $this->createMock(Context::class);
        $mockSecurityContext->method('canBeInitialized')->willReturn(false);

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertFalse($helper->hasRole('Acme.Com:DummyRole'));
    }

    #[Test]
    public function hasRoleDelegatesToSecurityContextIfSecurityContextCanBeInitialized()
    {
        $mockSecurityContext = $this->createMock(Context::class);
        $mockSecurityContext->method('canBeInitialized')->willReturn(true);
        $mockSecurityContext->expects($this->atLeastOnce())->method('hasRole')->with('Acme.Com:GrantsAccess')->willReturn(true);

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertTrue($helper->hasRole('Acme.Com:GrantsAccess'));
    }
}
