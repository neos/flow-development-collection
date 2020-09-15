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
        $mockSecurityContext->expects($this->any())->method('getCsrfProtectionToken')->willReturn('TheCsrfToken');

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        $this->assertEquals('TheCsrfToken', $helper->csrfToken());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsTrueIfAnAuthenticatedTokenIsPresent()
    {
        $mockUnautenticatedAuthenticationToken = $this->createMock(\Neos\Flow\Security\Authentication\TokenInterface::class);
        $mockUnautenticatedAuthenticationToken->expects($this->once())->method('isAuthenticated')->will($this->returnValue(false));

        $mockAutenticatedAuthenticationToken = $this->createMock(\Neos\Flow\Security\Authentication\TokenInterface::class);
        $mockAutenticatedAuthenticationToken->expects($this->once())->method('isAuthenticated')->will($this->returnValue(true));

        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue([
            $mockUnautenticatedAuthenticationToken,
            $mockAutenticatedAuthenticationToken
        ]));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        $this->assertTrue($helper->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsFalseIfNoAuthenticatedTokenIsPresent()
    {
        $mockUnautenticatedAuthenticationToken = $this->createMock(\Neos\Flow\Security\Authentication\TokenInterface::class);
        $mockUnautenticatedAuthenticationToken->expects($this->once())->method('isAuthenticated')->will($this->returnValue(false));

        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue([
            $mockUnautenticatedAuthenticationToken
        ]));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        $this->assertFalse($helper->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsFalseIfNoAuthenticatedTokensAre()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue([]));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        $this->assertFalse($helper->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsFalseIfSecurityContextCannotBeInitialized()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->will($this->returnValue(false));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        $this->assertFalse($helper->isAuthenticated());
    }

    /**
     * @test
     */
    public function hasAccessToPrivilegeTargetReturnsTrueIfAccessIsAllowed()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockPrivilegeManager = $this->createMock(\Neos\Flow\Security\Authorization\PrivilegeManagerInterface::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->will($this->returnValue(true));
        $mockPrivilegeManager->expects($this->once())->method('isPrivilegeTargetGranted')->with('somePrivilegeTarget')->will($this->returnValue(true));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);
        $this->inject($helper, 'privilegeManager', $mockPrivilegeManager);

        $this->assertTrue($helper->hasAccess('somePrivilegeTarget', []));
    }

    /**
     * @test
     */
    public function hasAccessToPrivilegeTargetReturnsFalseIfAccessIsForbidden()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockPrivilegeManager = $this->createMock(\Neos\Flow\Security\Authorization\PrivilegeManagerInterface::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->will($this->returnValue(true));
        $mockPrivilegeManager->expects($this->once())->method('isPrivilegeTargetGranted')->with('somePrivilegeTarget')->will($this->returnValue(false));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);
        $this->inject($helper, 'privilegeManager', $mockPrivilegeManager);

        $this->assertFalse($helper->hasAccess('somePrivilegeTarget', []));
    }

    /**
     * @test
     */
    public function hasAccessToPrivilegeTargetReturnsFalseIfSecurityContextCannotBeInitialized()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockPrivilegeManager = $this->createMock(\Neos\Flow\Security\Authorization\PrivilegeManagerInterface::class);

        $mockSecurityContext->expects($this->once())->method('canBeInitialized')->will($this->returnValue(false));

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);
        $this->inject($helper, 'privilegeManager', $mockPrivilegeManager);

        $this->assertFalse($helper->hasAccess('somePrivilegeTarget', []));
    }

    /**
     * @test
     */
    public function getAccountReturnsNullIfSecurityContextCannotBeInitialized()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockSecurityContext->expects($this->any())->method('canBeInitialized')->willReturn(false);

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        $this->assertNull($helper->getAccount());
    }

    /**
     * @test
     */
    public function getAccountDelegatesToSecurityContextIfSecurityContextCanBeInitialized()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockSecurityContext->expects($this->any())->method('canBeInitialized')->willReturn(true);
        $mockSecurityContext->expects($this->atLeastOnce())->method('getAccount')->willReturn('this would be an account instance');

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        $this->assertSame('this would be an account instance', $helper->getAccount());
    }

    /**
     * @test
     */
    public function hasRoleReturnsTrueForEverybodyRole()
    {
        $helper = new SecurityHelper();
        $this->assertTrue($helper->hasRole('Neos.Flow:Everybody'));
    }

    /**
     * @test
     */
    public function hasRoleReturnsFalseIfSecurityContextCannotBeInitialized()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockSecurityContext->expects($this->any())->method('canBeInitialized')->willReturn(false);

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        $this->assertFalse($helper->hasRole('Acme.Com:DummyRole'));
    }

    /**
     * @test
     */
    public function hasRoleDelegatesToSecurityContextIfSecurityContextCanBeInitialized()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockSecurityContext->expects($this->any())->method('canBeInitialized')->willReturn(true);
        $mockSecurityContext->expects($this->atLeastOnce())->method('hasRole')->with('Acme.Com:GrantsAccess')->willReturn(true);

        $helper = new SecurityHelper();
        $this->inject($helper, 'securityContext', $mockSecurityContext);

        $this->assertTrue($helper->hasRole('Acme.Com:GrantsAccess'));
    }
}
