<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Authentication\Token;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Security\Authentication\TokenInterface;
use TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword;
use TYPO3\FLOW3\Security\RequestPattern\Uri as UriRequestPattern;

/**
 * Testcase for abstract authentication token
 *
 */
class AbstractTokenTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Security\Authentication\Token\AbstractToken
	 */
	protected $token;

	public function setup() {
		$this->token = $this->getMockForAbstractClass('TYPO3\FLOW3\Security\Authentication\Token\AbstractToken');
	}

	/**
	 * @test
	 */
	public function authenticationProviderNameCanBeSetAndRetrieved() {
		$this->token->setAuthenticationProviderName('My Cool Provider');
		$this->assertEquals('My Cool Provider', $this->token->getAuthenticationProviderName());
	}

	/**
	 * @test
	 */
	public function authenticationEntryPointCanBeSetAndRetrieved() {
		$entryPoint = new \TYPO3\FLOW3\Security\Authentication\EntryPoint\WebRedirect();
		$this->token->setAuthenticationEntryPoint($entryPoint);
		$this->assertSame($entryPoint, $this->token->getAuthenticationEntryPoint());
	}

	/**
	 * @test
	 */
	public function theAuthenticationStatusIsCorrectlyInitialized() {
		$this->assertSame(TokenInterface::NO_CREDENTIALS_GIVEN, $this->token->getAuthenticationStatus());
	}

	/**
	 * @return array
	 */
	public function authenticationStatusAndIsAuthenticated() {
		return array(
			array(TokenInterface::NO_CREDENTIALS_GIVEN, FALSE),
			array(TokenInterface::AUTHENTICATION_NEEDED, FALSE),
			array(TokenInterface::WRONG_CREDENTIALS, FALSE),
			array(TokenInterface::AUTHENTICATION_SUCCESSFUL, TRUE),
		);
	}

	/**
	 * @test
	 * @dataProvider authenticationStatusAndIsAuthenticated
	 */
	public function isAuthenticatedReturnsTheCorrectValueForAGivenStatus($status, $isAuthenticated) {
		$this->token->setAuthenticationStatus($status);
		$this->assertEquals($isAuthenticated, $this->token->isAuthenticated());
		$this->token->setAuthenticationStatus($status);
		$this->assertEquals($isAuthenticated, $this->token->isAuthenticated());
		$this->token->setAuthenticationStatus($status);
		$this->assertEquals($isAuthenticated, $this->token->isAuthenticated());
		$this->token->setAuthenticationStatus($status);
		$this->assertEquals($isAuthenticated, $this->token->isAuthenticated());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Security\Exception\InvalidAuthenticationStatusException
	 */
	public function setAuthenticationStatusThrowsAnExceptionForAnInvalidStatus() {
		$this->token->setAuthenticationStatus(-1);
	}

	/**
	 * @test
	 */
	public function getRolesReturnsTheRolesOfTheAuthenticatedAccount() {
		$this->token->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);

		$roles = array('role1', 'role2');

		$mockAccount = $this->getMock('TYPO3\FLOW3\Security\Account', array(), array(), '', FALSE);
		$mockAccount->expects($this->once())->method('getRoles')->will($this->returnValue($roles));

		$this->token->setAccount($mockAccount);

		$this->assertEquals($roles, $this->token->getRoles(), 'The wrong roles were returned');
	}

	/**
	 * @test
	 */
	public function getRolesReturnsAnEmptyArrayIfTheTokenIsNotAuthenticated() {
		$mockAccount = $this->getMock('TYPO3\FLOW3\Security\Account', array(), array(), '', FALSE);
		$mockAccount->expects($this->never())->method('getRoles');

		$this->token->setAccount($mockAccount);

		$this->assertEquals(array(), $this->token->getRoles(), 'Roles have been returned, although the token was not authenticated.');
	}

	/**
	 * @test
	 */
	public function getRolesReturnsAnEmptyArrayIfNoAccountHasBeenSet() {
		$this->token->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);

		$this->assertEquals(array(), $this->token->getRoles(), 'Roles have been returned, although no account has been set.');
	}

	/**
	 * @test
	 */
	public function requestPatternsCanBeSetRetrievedAndChecked() {
		$this->assertFalse($this->token->hasRequestPatterns());

		$uriRequestPattern = new UriRequestPattern('http://mydomain.com/some/path/pattern');
		$this->token->setRequestPatterns(array($uriRequestPattern));

		$this->assertTrue($this->token->hasRequestPatterns());
		$this->assertEquals(array($uriRequestPattern), $this->token->getRequestPatterns());
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setRequestPatternsOnlyAcceptsRequestPatterns() {
		$uriRequestPattern = new UriRequestPattern('http://mydomain.com/some/path/pattern');
		$this->token->setRequestPatterns(array($uriRequestPattern, 'no valid pattern'));
	}

}
?>