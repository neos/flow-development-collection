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
