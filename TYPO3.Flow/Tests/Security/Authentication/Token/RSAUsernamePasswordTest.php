<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication\Token;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Security_Authentication_Token_UsernamePasswordTest.php 1707 2009-01-07 10:37:30Z k-fish $
 */

/**
 * Testcase for RSA username/password authentication token
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Security_Authentication_Token_UsernamePasswordTest.php 1707 2009-01-07 10:37:30Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class RSAUsernamePasswordTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function credentialsAreSetCorrectlyFromPOSTArguments() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

		$POSTArguments = array(
			'F3_FLOW3_Security_Authentication_Token_RSAUsernamePassword_encryptedUsername' => 'some encrypted username',
			'F3_FLOW3_Security_Authentication_Token_RSAUsernamePassword_encryptedPassword' => 'some encrypted password'
		);

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getRawPOSTArguments')->will($this->returnValue($POSTArguments));

		$mockWalletService = $this->getMock('F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface', array(), array(), '', FALSE);

		$token = new \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword();
		$token->injectObjectFactory($mockObjectFactory);
		$token->injectEnvironment($mockEnvironment);
		$token->injectRSAWalletService($mockWalletService);

		$token->updateCredentials();

		$expectedCredentials = array ('encryptedUsername' => 'some encrypted username', 'encryptedPassword' => 'some encrypted password');
		$this->assertEquals($expectedCredentials, $token->getCredentials(), 'The credentials have not been extracted correctly from the POST arguments');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function differentKeypairsAreUsedForPasswordAndUsernameEncryption() {
		$generateNewKeypairCallback = function() {
			$args = func_get_args();

			if ($args[0] === TRUE) return 'uuidForPassword';
			elseif ($args[0] === FALSE) return 'uuidForUsername';
			else throw new \F3\FLOW3\Security\Exception\InvalidKeyPairID();
		};

		$getPublicKeyCallback = function() {
			$args = func_get_args();

			if ($args[0] === 'uuidForPassword') return 'publicKeyForPassword';
			elseif ($args[0] === 'uuidForUsername') return 'publicKeyForUsername';
			else throw new \F3\FLOW3\Security\Exception\InvalidKeyPairID();
		};

		$mockRSAWalletService = $this->getMock('F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface', array(), array(), '', FALSE);
		$mockRSAWalletService->expects($this->any())->method('generateNewKeypair')->will($this->returnCallback($generateNewKeypairCallback));
		$mockRSAWalletService->expects($this->any())->method('getPublicKey')->will($this->returnCallback($getPublicKeyCallback));

		$token = new \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword();
		$token->injectRSAWalletService($mockRSAWalletService);

		$this->assertEquals($token->generatePublicKeyForPassword(), 'publicKeyForPassword', 'The wrong public key was returned for password encryption.');
		$this->assertEquals($token->generatePublicKeyForUsername(), 'publicKeyForUsername', 'The wrong public key was returned for username encryption.');
		$this->assertEquals($token->getPasswordKeypairUUID(), 'uuidForPassword', 'The wrong keypair UUID for password encryption was returend.');
		$this->assertEquals($token->getUsernameKeypairUUID(), 'uuidForUsername', 'The wrong keypair UUID for username encryption was returend.');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function aNewPasswordKeypairIsGeneratedIfTheOldOneIsInvalid() {
		$getPublicKeyCallback = function() {
			$args = func_get_args();

			if ($args[0] === 'newKeypairUUID') return 'newPublicKey';
			else throw new \F3\FLOW3\Security\Exception\InvalidKeyPairID();
		};

		$mockRSAWalletService = $this->getMock('F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface', array(), array(), '', FALSE);
		$mockRSAWalletService->expects($this->once())->method('generateNewKeypair')->will($this->returnValue('newKeypairUUID'));
		$mockRSAWalletService->expects($this->exactly(2))->method('getPublicKey')->will($this->returnCallback($getPublicKeyCallback));

		$tokenClassName = $this->buildAccessibleProxy('F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword');
		$token = new $tokenClassName();
		$token->_set('passwordKeypairUUID', 'oldKeypairUUID');
		$token->injectRSAWalletService($mockRSAWalletService);

		$this->assertEquals($token->generatePublicKeyForPassword(), 'newPublicKey', 'The wrong public key was returned.');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function aNewUsernameKeypairIsGeneratedIfTheOldOneIsInvalid() {
		$getPublicKeyCallback = function() {
			$args = func_get_args();

			if ($args[0] === 'newKeypairUUID') return 'newPublicKey';
			else throw new \F3\FLOW3\Security\Exception\InvalidKeyPairID();
		};

		$mockRSAWalletService = $this->getMock('F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface', array(), array(), '', FALSE);
		$mockRSAWalletService->expects($this->once())->method('generateNewKeypair')->will($this->returnValue('newKeypairUUID'));
		$mockRSAWalletService->expects($this->exactly(2))->method('getPublicKey')->will($this->returnCallback($getPublicKeyCallback));

		$tokenClassName = $this->buildAccessibleProxy('F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword');
		$token = new $tokenClassName();
		$token->_set('usernameKeypairUUID', 'oldKeypairUUID');
		$token->injectRSAWalletService($mockRSAWalletService);

		$this->assertEquals($token->generatePublicKeyForUsername(), 'newPublicKey', 'The wrong public key was returned.');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invalidateCurrentKeypairsDestroysTheCorrectKeypairsInTheRSAWalletService() {
		$generateNewKeypairCallback = function() {
			$args = func_get_args();

			if ($args[0] === TRUE) return 'uuidForPassword';
			elseif ($args[0] === FALSE) return 'uuidForUsername';
			else throw new \F3\FLOW3\Security\Exception\InvalidKeyPairID();
		};

		$getPublicKeyCallback = function() {
			$args = func_get_args();

			if ($args[0] === 'uuidForPassword') return 'publicKeyForPassword';
			elseif ($args[0] === 'uuidForUsername') return 'publicKeyForUsername';
			else throw new \F3\FLOW3\Security\Exception\InvalidKeyPairID();
		};

		$destroyKeypairCallback = function() {
			$args = func_get_args();

			if ($args[0] !== 'uuidForPassword' && $args[0] !== 'uuidForUsername') throw new \Exception('destroyKeypair() was called with a wrong uuid. Got: ' . $args[0]);
		};

		$mockRSAWalletService = $this->getMock('F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface', array(), array(), '', FALSE);
		$mockRSAWalletService->expects($this->any())->method('generateNewKeypair')->will($this->returnCallback($generateNewKeypairCallback));
		$mockRSAWalletService->expects($this->any())->method('getPublicKey')->will($this->returnCallback($getPublicKeyCallback));
		$mockRSAWalletService->expects($this->any())->method('destroyKeypair')->will($this->returnCallback($destroyKeypairCallback));

		$token = new \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword();
		$token->injectRSAWalletService($mockRSAWalletService);

		$token->generatePublicKeyForPassword();
		$token->generatePublicKeyForUsername();

		$token->invalidateCurrentKeypairs();
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theAuthenticationStatusIsCorrectlyInitialized() {
		$token = new \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword();
		$this->assertSame(\F3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN, $token->getAuthenticationStatus());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isAuthenticatedReturnsTheCorrectValueForAGivenStatus() {
		$token1 = new \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword();
		$token1->setAuthenticationStatus(\F3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);
		$token2 = new \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword();
		$token2->setAuthenticationStatus(\F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED);
		$token3 = new \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword();
		$token3->setAuthenticationStatus(\F3\FLOW3\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);
		$token4 = new \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword();
		$token4->setAuthenticationStatus(\F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);

		$this->assertFalse($token1->isAuthenticated());
		$this->assertFalse($token2->isAuthenticated());
		$this->assertFalse($token3->isAuthenticated());
		$this->assertTrue($token4->isAuthenticated());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNewCredentialsArrived() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

		$POSTArguments = array(
			'F3_FLOW3_Security_Authentication_Token_RSAUsernamePassword_encryptedUsername' => 'some encrypted username',
			'F3_FLOW3_Security_Authentication_Token_RSAUsernamePassword_encryptedPassword' => 'some encrypted password'
		);

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getRawPOSTArguments')->will($this->returnValue($POSTArguments));

		$token = new \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword();
		$token->injectObjectFactory($mockObjectFactory);
		$token->injectEnvironment($mockEnvironment);

		$token->updateCredentials();

		$this->assertSame(\F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED, $token->getAuthenticationStatus());
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException \F3\FLOW3\Security\Exception\InvalidAuthenticationStatus
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAuthenticationStatusThrowsAnExceptionForAnInvalidStatus() {
		$token = new \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword();
		$token->setAuthenticationStatus(-1);
	}
}
?>