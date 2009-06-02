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
 * @version $Id$
 */

/**
 * Testcase for for the PHP (OpenSSL) based RSAWalletService
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class RSAWalletServicePHPTest extends \F3\Testing\BaseTestCase {

	/**
	 * Set up this testcase.
	 * In this case this only marks the test to be skipped if openssl extension is not installed
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setUp() {
		if (!function_exists('openssl_pkey_new')) {
			$this->markTestSkipped('openssl_pkey_new() not available');
		}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function encryptingAndDecryptingBasicallyWorks() {
		$currentKeys = array();

		$setCallBack = function() use (&$currentKeys) {
			$args = func_get_args();
			$currentKeys[$args[0]] = $args[1];
		};

		$getCallBack = function() use (&$currentKeys) {
			$args = func_get_args();
			return $currentKeys[$args[0]];
		};

		$hasCallBack = function() use (&$currentKeys) {
			$args = func_get_args();
			return isset($currentKeys[$args[0]]);
		};

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('set')->will($this->returnCallback($setCallBack, '__invoke'));
		$mockCache->expects($this->any())->method('get')->will($this->returnCallback($getCallBack, '__invoke'));
		$mockCache->expects($this->any())->method('has')->will($this->returnCallback($hasCallBack, '__invoke'));

		$RSAWalletService = new RSAWalletServicePHP();
		$RSAWalletService->injectObjectFactory($this->objectFactory);
		$RSAWalletService->injectKeystoreCache($mockCache);

		$keyPairUUID = $RSAWalletService->generateNewKeypair();

		$plaintext = 'some very sensitive data!';
		$ciphertext = $RSAWalletService->encryptWithPublicKey($plaintext, $keyPairUUID);

		$this->assertNotEquals($ciphertext, $plaintext);
		$this->assertEquals($plaintext, $RSAWalletService->decrypt($ciphertext, $keyPairUUID));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkRSAEncryptedPasswordReturnsTrueForACorrectPassword() {
		$currentKeys = array();

		$setCallBack = function() use (&$currentKeys) {
			$args = func_get_args();
			$currentKeys[$args[0]] = $args[1];
		};

		$getCallBack = function() use (&$currentKeys) {
			$args = func_get_args();
			return $currentKeys[$args[0]];
		};

		$hasCallBack = function() use (&$currentKeys) {
			$args = func_get_args();
			return isset($currentKeys[$args[0]]);
		};

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('set')->will($this->returnCallback($setCallBack, '__invoke'));
		$mockCache->expects($this->any())->method('get')->will($this->returnCallback($getCallBack, '__invoke'));
		$mockCache->expects($this->any())->method('has')->will($this->returnCallback($hasCallBack, '__invoke'));

		$RSAWalletService = new RSAWalletServicePHP();
		$RSAWalletService->injectObjectFactory($this->objectFactory);
		$RSAWalletService->injectKeystoreCache($mockCache);

		$keyPairUUID = $RSAWalletService->generateNewKeypair();
		$encryptedPassword = $RSAWalletService->encryptWithPublicKey('password', $keyPairUUID);

		$passwordHash = 'af1e8a52451786a6b3bf78838e03a0a2';
		$salt = 'a709157e66e0197cafa0c2ba99f6e252';

		$this->assertTrue($RSAWalletService->checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $keyPairUUID));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkRSAEncryptedPasswordReturnsFalseForAnIncorrectPassword() {
		$currentKeys = array();

		$setCallBack = function() use (&$currentKeys) {
			$args = func_get_args();
			$currentKeys[$args[0]] = $args[1];
		};

		$getCallBack = function() use (&$currentKeys) {
			$args = func_get_args();
			return $currentKeys[$args[0]];
		};

		$hasCallBack = function() use (&$currentKeys) {
			$args = func_get_args();
			return isset($currentKeys[$args[0]]);
		};

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('set')->will($this->returnCallback($setCallBack, '__invoke'));
		$mockCache->expects($this->any())->method('get')->will($this->returnCallback($getCallBack, '__invoke'));
		$mockCache->expects($this->any())->method('has')->will($this->returnCallback($hasCallBack, '__invoke'));

		$RSAWalletService = new RSAWalletServicePHP();
		$RSAWalletService->injectObjectFactory($this->objectFactory);
		$RSAWalletService->injectKeystoreCache($mockCache);

		$keyPairUUID = $RSAWalletService->generateNewKeypair();
		$encryptedPassword = $RSAWalletService->encryptWithPublicKey('wrong password', $keyPairUUID);

		$passwordHash = 'af1e8a52451786a6b3bf78838e03a0a2';
		$salt = 'a709157e66e0197cafa0c2ba99f6e252';

		$this->assertFalse($RSAWalletService->checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $keyPairUUID));
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException \F3\FLOW3\Security\Exception\DecryptionNotAllowed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decryptingWithAKeypairUUIDMarkedForPasswordUsageThrowsAnException() {
		$currentKeys = array();

		$setCallBack = function() use (&$currentKeys) {
			$args = func_get_args();
			$currentKeys[$args[0]] = $args[1];
		};

		$getCallBack = function() use (&$currentKeys) {
			$args = func_get_args();
			return $currentKeys[$args[0]];
		};

		$hasCallBack = function() use (&$currentKeys) {
			$args = func_get_args();
			return isset($currentKeys[$args[0]]);
		};

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('set')->will($this->returnCallback($setCallBack, '__invoke'));
		$mockCache->expects($this->any())->method('get')->will($this->returnCallback($getCallBack, '__invoke'));
		$mockCache->expects($this->any())->method('has')->will($this->returnCallback($hasCallBack, '__invoke'));

		$RSAWalletService = new RSAWalletServicePHP();
		$RSAWalletService->injectObjectFactory($this->objectFactory);
		$RSAWalletService->injectKeystoreCache($mockCache);

		$keyPairUUID = $RSAWalletService->generateNewKeypair(TRUE);
		$decryptedPassword = $RSAWalletService->decrypt('some cipher', $keyPairUUID);
	}
}
?>