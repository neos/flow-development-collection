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

/**
 * Testcase for username/password authentication token
 *
 */
class UsernamePasswordTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @category unit
	 */
	public function credentialsAreSetCorrectlyFromPostArguments() {
		$postArguments = array();
		$postArguments['__authentication']['TYPO3']['FLOW3']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'johndoe';
		$postArguments['__authentication']['TYPO3']['FLOW3']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getRawPostArguments')->will($this->returnValue($postArguments));
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\RequestInterface');

		$token = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword', array('dummy'));
		$token->_set('environment', $mockEnvironment);

		$token->updateCredentials($mockRequest);

		$expectedCredentials = array ('username' => 'johndoe', 'password' => 'verysecurepassword');
		$this->assertEquals($expectedCredentials, $token->getCredentials(), 'The credentials have not been extracted correctly from the POST arguments');
	}

	/**
	 * @test
	 * @category unit
	 */
	public function theAuthenticationStatusIsCorrectlyInitialized() {
		$token = new \TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword();
		$this->assertSame(\TYPO3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN, $token->getAuthenticationStatus());
	}

	/**
	 * @test
	 * @category unit
	 */
	public function isAuthenticatedReturnsTheCorrectValueForAGivenStatus() {
		$token1 = new \TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword();
		$token1->setAuthenticationStatus(\TYPO3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);
		$token2 = new \TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword();
		$token2->setAuthenticationStatus(\TYPO3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED);
		$token3 = new \TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword();
		$token3->setAuthenticationStatus(\TYPO3\FLOW3\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);
		$token4 = new \TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword();
		$token4->setAuthenticationStatus(\TYPO3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);

		$this->assertFalse($token1->isAuthenticated());
		$this->assertFalse($token2->isAuthenticated());
		$this->assertFalse($token3->isAuthenticated());
		$this->assertTrue($token4->isAuthenticated());
	}

	/**
	 * @test
	 * @category unit
	 */
	public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNewCredentialsArrived() {
		$postArguments = array();
		$postArguments['__authentication']['TYPO3']['FLOW3']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'TYPO3.FLOW3';
		$postArguments['__authentication']['TYPO3']['FLOW3']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getRawPostArguments')->will($this->returnValue($postArguments));
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\RequestInterface');

		$token = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword', array('dummy'));
		$token->_set('environment', $mockEnvironment);

		$token->updateCredentials($mockRequest);

		$this->assertSame(\TYPO3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED, $token->getAuthenticationStatus());
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException \TYPO3\FLOW3\Security\Exception\InvalidAuthenticationStatusException
	 */
	public function setAuthenticationStatusThrowsAnExceptionForAnInvalidStatus() {
		$token = new \TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword();
		$token->setAuthenticationStatus(-1);
	}

	/**
	 * @test
	 * @category unit
	 */
	public function getRolesReturnsTheRolesOfTheAuthenticatedAccount() {
		$token = new \TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword();
		$token->setAuthenticationStatus(\TYPO3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);

		$roles = array('role1', 'role2');

		$mockAccount = $this->getMock('TYPO3\FLOW3\Security\Account', array(), array(), '', FALSE);
		$mockAccount->expects($this->once())->method('getRoles')->will($this->returnValue($roles));

		$token->setAccount($mockAccount);

		$this->assertEquals($roles, $token->getRoles(), 'The wrong roles were returned');
	}

	/**
	 * @test
	 * @category unit
	 */
	public function getRolesReturnsAnEmptyArrayIfTheTokenIsNotAuthenticated() {
		$token = new \TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword();

		$mockAccount = $this->getMock('TYPO3\FLOW3\Security\Account', array(), array(), '', FALSE);
		$mockAccount->expects($this->never())->method('getRoles');

		$token->setAccount($mockAccount);

		$this->assertEquals(array(), $token->getRoles(), 'Roles have been returned, although the token was not authenticated.');
	}

	/**
	 * @test
	 * @category unit
	 */
	public function getRolesReturnsAnEmptyArrayIfNoAccountHasBeenSet() {
		$token = new \TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword();
		$token->setAuthenticationStatus(\TYPO3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);

		$this->assertEquals(array(), $token->getRoles(), 'Roles have been returned, although no account has been set.');
	}
}
?>