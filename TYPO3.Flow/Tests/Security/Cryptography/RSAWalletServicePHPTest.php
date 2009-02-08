<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Cryptography;

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
 * @version $Id: F3_FLOW3_Security_ContextHolderSessionTest.php 1707 2009-01-07 10:37:30Z k-fish $
 */

require_once('vfs/vfsStream.php');

/**
 * Testcase for for the PHP (OpenSSL) based RSAWalletService
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Security_ContextHolderSessionTest.php 1707 2009-01-07 10:37:30Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class RSAWalletServicePHPTest extends \F3\Testing\BaseTestCase {

	/**
	 * Set up environment
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));

		$settings = array();
		$settings['security']['cryptography']['RSAWalletServicePHP']['keystore'] = \vfsStream::url('testDirectory');

		$this->RSAWalletService = new RSAWalletServicePHP();
		$this->RSAWalletService->injectObjectFactory($this->objectFactory);
		$this->RSAWalletService->injectSettings($settings);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function encryptingAndDecryptingBasicallyWorks() {
		$keyPairUUID = $this->RSAWalletService->generateNewKeypair();

		$plaintext = 'some very sensitive data!';
		$ciphertext = $this->RSAWalletService->encryptWithPublicKey($plaintext, $keyPairUUID);

		$this->assertNotEquals($ciphertext, $plaintext);
		$this->assertEquals($plaintext, $this->RSAWalletService->decrypt($ciphertext, $keyPairUUID));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkRSAEncryptedPasswordReturnsTrueForACorrectPassword() {
		$keyPairUUID = $this->RSAWalletService->generateNewKeypair();
		$encryptedPassword = $this->RSAWalletService->encryptWithPublicKey('password', $keyPairUUID);

		$passwordHash = 'af1e8a52451786a6b3bf78838e03a0a2';
		$salt = 'a709157e66e0197cafa0c2ba99f6e252';

		$this->assertTrue($this->RSAWalletService->checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $keyPairUUID));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkRSAEncryptedPasswordReturnsFalseForAnIncorrectPassword() {
		$keyPairUUID = $this->RSAWalletService->generateNewKeypair();
		$encryptedPassword = $this->RSAWalletService->encryptWithPublicKey('wrong password', $keyPairUUID);

		$passwordHash = 'af1e8a52451786a6b3bf78838e03a0a2';
		$salt = 'a709157e66e0197cafa0c2ba99f6e252';

		$this->assertFalse($this->RSAWalletService->checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $keyPairUUID));
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException \F3\FLOW3\Security\Exception\DecryptionNotAllowed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decryptingWithAKeypairUUIDMarkedForPasswordUsageThrowsAnException() {
		$keyPairUUID = $this->RSAWalletService->generateNewKeypair(TRUE);
		$decryptedPassword = $this->RSAWalletService->decrypt('some cipher', $keyPairUUID);
	}
}
?>