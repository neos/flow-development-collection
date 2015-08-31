<?php
namespace TYPO3\Eel\Tests\Unit;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.Eel".                   *
 *                                                                        */

use TYPO3\Eel\Helper\SecurityHelper;

/**
 * Eel SecurityHelper test
 */
class SecurityHelperTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getAccountReturnsNullIfSecurityContextCannotBeInitialized() {
		$mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
		$mockSecurityContext->expects($this->any())->method('canBeInitialized')->willReturn(FALSE);

		$helper = new SecurityHelper();
		$this->inject($helper, 'securityContext', $mockSecurityContext);

		$this->assertNull($helper->getAccount());
	}

	/**
	 * @test
	 */
	public function getAccountDelegatesToSecurityContextIfSecurityContextCanBeInitialized() {
		$mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
		$mockSecurityContext->expects($this->any())->method('canBeInitialized')->willReturn(TRUE);
		$mockSecurityContext->expects($this->atLeastOnce())->method('getAccount')->willReturn('this would be an account instance');

		$helper = new SecurityHelper();
		$this->inject($helper, 'securityContext', $mockSecurityContext);

		$this->assertSame('this would be an account instance', $helper->getAccount());
	}

	/**
	 * @test
	 */
	public function hasRoleReturnsTrueForEverybodyRole() {
		$helper = new SecurityHelper();
		$this->assertTrue($helper->hasRole('TYPO3.Flow:Everybody'));
	}

	/**
	 * @test
	 */
	public function hasRoleReturnsFalseIfSecurityContextCannotBeInitialized() {
		$mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
		$mockSecurityContext->expects($this->any())->method('canBeInitialized')->willReturn(FALSE);

		$helper = new SecurityHelper();
		$this->inject($helper, 'securityContext', $mockSecurityContext);

		$this->assertFalse($helper->hasRole('Acme.Com:DummyRole'));
	}

	/**
	 * @test
	 */
	public function hasRoleDelegatesToSecurityContextIfSecurityContextCanBeInitialized() {
		$mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
		$mockSecurityContext->expects($this->any())->method('canBeInitialized')->willReturn(TRUE);
		$mockSecurityContext->expects($this->atLeastOnce())->method('hasRole')->with('Acme.Com:GrantsAccess')->willReturn(TRUE);

		$helper = new SecurityHelper();
		$this->inject($helper, 'securityContext', $mockSecurityContext);

		$this->assertTrue($helper->hasRole('Acme.Com:GrantsAccess'));
	}

}
