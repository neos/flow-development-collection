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
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);

		$mockRSAWalletService = $this->getMock('F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface', array(), array(), '', FALSE);
		$mockRSAWalletService->expects($this->at(0))->method('generateNewKeypair')->with($this->equalTo(TRUE))->will($this->returnValue('uuid1'));
		$mockRSAWalletService->expects($this->at(2))->method('generateNewKeypair')->will($this->returnValue('uuid2'));
		$mockRSAWalletService->expects($this->at(1))->method('getPublicKey')->with($this->equalTo('uuid1'))->will($this->returnValue('publicKey1'));
		$mockRSAWalletService->expects($this->at(3))->method('getPublicKey')->with($this->equalTo('uuid2'))->will($this->returnValue('publicKey2'));

		$token = new \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword();
		$token->injectObjectFactory($mockObjectFactory);
		$token->injectEnvironment($mockEnvironment);
		$token->injectRSAWalletService($mockRSAWalletService);

		$this->assertEquals($token->generatePublicKeyForPassword(), 'publicKey1', 'The wrong public key was returned for password encryption.');
		$this->assertEquals($token->generatePublicKeyForUsername(), 'publicKey2', 'The wrong public key was returned for username encryption.');
		$this->assertEquals($token->getPasswordKeypairUUID(), 'uuid1', 'The wrong keypair UUID for password encryption was returend.');
		$this->assertEquals($token->getUsernameKeypairUUID(), 'uuid2', 'The wrong keypair UUID for username encryption was returend.');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invalidateCurrentKeypairsDestroysTheCorrectKeypairsInTheRSAWalletService() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);

		$mockRSAWalletService = $this->getMock('F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface', array(), array(), '', FALSE);

		$mockRSAWalletService->expects($this->at(0))->method('generateNewKeypair')->with($this->equalTo(TRUE))->will($this->returnValue('uuid1'));
		$mockRSAWalletService->expects($this->at(2))->method('generateNewKeypair')->will($this->returnValue('uuid2'));
		$mockRSAWalletService->expects($this->at(4))->method('destroyKeypair')->with($this->equalTo('uuid1'));
		$mockRSAWalletService->expects($this->at(5))->method('destroyKeypair')->with($this->equalTo('uuid2'));

		$token = new \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword();
		$token->injectObjectFactory($mockObjectFactory);
		$token->injectEnvironment($mockEnvironment);
		$token->injectRSAWalletService($mockRSAWalletService);

		$token->generatePublicKeyForPassword();
		$token->generatePublicKeyForUsername();

		$token->invalidateCurrentKeypairs();
	}
}
?>