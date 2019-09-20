<?php
namespace Neos\Eel\Tests\Unit;

/*                                                                        *
 * This script belongs to the Flow package "Neos.Eel".                   *
 *                                                                        */

use Neos\Eel\Helper\SecurityHelper;

/**
 * Eel SecurityHelper test
 */
class SecurityHelperTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function csrfTokenIsReturnedFromTheSecurityContext()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockSecurityContext->expects(self::any())->method('getCsrfProtectionToken')->willReturn('TheCsrfToken');

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertEquals('TheCsrfToken', $helper->csrfToken());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsTrueIfAnAuthenticatedTokenIsPresent()
    {
        $mockUnautenticatedAuthenticationToken = $this->createMock(\Neos\Flow\Security\Authentication\TokenInterface::class);
        $mockUnautenticatedAuthenticationToken->expects(self::once())->method('isAuthenticated')->will(self::returnValue(false));

        $mockAutenticatedAuthenticationToken = $this->createMock(\Neos\Flow\Security\Authentication\TokenInterface::class);
        $mockAutenticatedAuthenticationToken->expects(self::once())->method('isAuthenticated')->will(self::returnValue(true));

        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);

        $mockSecurityContext->expects(self::once())->method('canBeInitialized')->will(self::returnValue(true));
        $mockSecurityContext->expects(self::once())->method('getAuthenticationTokens')->will(self::returnValue([
            $mockUnautenticatedAuthenticationToken,
            $mockAutenticatedAuthenticationToken
        ]));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertTrue($helper->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsFalseIfNoAuthenticatedTokenIsPresent()
    {
        $mockUnautenticatedAuthenticationToken = $this->createMock(\Neos\Flow\Security\Authentication\TokenInterface::class);
        $mockUnautenticatedAuthenticationToken->expects(self::once())->method('isAuthenticated')->will(self::returnValue(false));

        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);

        $mockSecurityContext->expects(self::once())->method('canBeInitialized')->will(self::returnValue(true));
        $mockSecurityContext->expects(self::once())->method('getAuthenticationTokens')->will(self::returnValue([
            $mockUnautenticatedAuthenticationToken
        ]));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertFalse($helper->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsFalseIfNoAuthenticatedTokensAre()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);

        $mockSecurityContext->expects(self::once())->method('canBeInitialized')->will(self::returnValue(true));
        $mockSecurityContext->expects(self::once())->method('getAuthenticationTokens')->will(self::returnValue([]));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertFalse($helper->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsFalseIfSecurityContextCannotBeInitialized()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);

        $mockSecurityContext->expects(self::once())->method('canBeInitialized')->will(self::returnValue(false));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertFalse($helper->isAuthenticated());
    }

    /**
     * @test
     */
    public function hasAccessToPrivilegeTargetReturnsTrueIfAccessIsAllowed()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockPrivilegeManager = $this->createMock(\Neos\Flow\Security\Authorization\PrivilegeManagerInterface::class);

        $mockSecurityContext->expects(self::once())->method('canBeInitialized')->will(self::returnValue(true));
        $mockPrivilegeManager->expects(self::once())->method('isPrivilegeTargetGranted')->with('somePrivilegeTarget')->will(self::returnValue(true));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);
        $this->inject($helper, 'privilegeManager', $mockPrivilegeManager);

        self::assertTrue($helper->hasAccess('somePrivilegeTarget', []));
    }

    /**
     * @test
     */
    public function hasAccessToPrivilegeTargetReturnsFalseIfAccessIsForbidden()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockPrivilegeManager = $this->createMock(\Neos\Flow\Security\Authorization\PrivilegeManagerInterface::class);

        $mockSecurityContext->expects(self::once())->method('canBeInitialized')->will(self::returnValue(true));
        $mockPrivilegeManager->expects(self::once())->method('isPrivilegeTargetGranted')->with('somePrivilegeTarget')->will(self::returnValue(false));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);
        $this->inject($helper, 'privilegeManager', $mockPrivilegeManager);

        self::assertFalse($helper->hasAccess('somePrivilegeTarget', []));
    }

    /**
     * @test
     */
    public function hasAccessToPrivilegeTargetReturnsFalseIfSecurityContextCannotBeInitialized()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockPrivilegeManager = $this->createMock(\Neos\Flow\Security\Authorization\PrivilegeManagerInterface::class);

        $mockSecurityContext->expects(self::once())->method('canBeInitialized')->will(self::returnValue(false));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);
        $this->inject($helper, 'privilegeManager', $mockPrivilegeManager);

        self::assertFalse($helper->hasAccess('somePrivilegeTarget', []));
    }

    /**
     * @test
     */
    public function getAccountReturnsNullIfSecurityContextCannotBeInitialized()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockSecurityContext->expects(self::any())->method('canBeInitialized')->willReturn(false);

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertNull($helper->getAccount());
    }

    /**
     * @test
     */
    public function getAccountDelegatesToSecurityContextIfSecurityContextCanBeInitialized()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockSecurityContext->expects(self::any())->method('canBeInitialized')->willReturn(true);
        $mockSecurityContext->expects(self::atLeastOnce())->method('getAccount')->willReturn('this would be an account instance');

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertSame('this would be an account instance', $helper->getAccount());
    }

    /**
     * @test
     */
    public function hasRoleReturnsTrueForEverybodyRole()
    {
        $helper = new SecurityHelper();
        self::assertTrue($helper->hasRole('Neos.Flow:Everybody'));
    }

    /**
     * @test
     */
    public function hasRoleReturnsFalseIfSecurityContextCannotBeInitialized()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockSecurityContext->expects(self::any())->method('canBeInitialized')->willReturn(false);

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertFalse($helper->hasRole('Acme.Com:DummyRole'));
    }

    /**
     * @test
     */
    public function hasRoleDelegatesToSecurityContextIfSecurityContextCanBeInitialized()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockSecurityContext->expects(self::any())->method('canBeInitialized')->willReturn(true);
        $mockSecurityContext->expects(self::atLeastOnce())->method('hasRole')->with('Acme.Com:GrantsAccess')->willReturn(true);

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        self::assertTrue($helper->hasRole('Acme.Com:GrantsAccess'));
    }
}
