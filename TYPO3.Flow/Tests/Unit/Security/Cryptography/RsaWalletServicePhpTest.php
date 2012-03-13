<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Cryptography;

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
 * Testcase for for the PHP (OpenSSL) based RSAWalletService
 *
 */
class RsaWalletServicePhpTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * Set up this testcase.
	 * In this case this only marks the test to be skipped if openssl extension is not installed
	 *
	 * @return void
	 */
	public function setUp() {
		if (!function_exists('openssl_pkey_new')) {
			$this->markTestSkipped('openssl_pkey_new() not available');
		} else {
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
			$mockCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
			$mockCache->expects($this->any())->method('set')->will($this->returnCallback($setCallBack));
			$mockCache->expects($this->any())->method('get')->will($this->returnCallback($getCallBack));
			$mockCache->expects($this->any())->method('has')->will($this->returnCallback($hasCallBack));

			$this->rsaWalletService = $this->getAccessibleMock('TYPO3\FLOW3\Security\Cryptography\RsaWalletServicePhp', array('dummy'));
			$this->rsaWalletService->_set('keystoreCache', $mockCache);

			$this->keyPairUuid = $this->rsaWalletService->generateNewKeypair();
		}
	}

	/**
	 * @test
	 */
	public function encryptingAndDecryptingBasicallyWorks() {
		$plaintext = 'some very sensitive data!';
		$ciphertext = $this->rsaWalletService->encryptWithPublicKey($plaintext, $this->keyPairUuid);

		$this->assertNotEquals($ciphertext, $plaintext);
		$this->assertEquals($plaintext, $this->rsaWalletService->decrypt($ciphertext, $this->keyPairUuid));
	}

	/**
	 * @test
	 */
	public function checkRSAEncryptedPasswordReturnsTrueForACorrectPassword() {
		$encryptedPassword = $this->rsaWalletService->encryptWithPublicKey('password', $this->keyPairUuid);

		$passwordHash = 'af1e8a52451786a6b3bf78838e03a0a2';
		$salt = 'a709157e66e0197cafa0c2ba99f6e252';

		$this->assertTrue($this->rsaWalletService->checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $this->keyPairUuid));
	}

	/**
	 * @test
	 */
	public function checkRSAEncryptedPasswordReturnsFalseForAnIncorrectPassword() {
		$encryptedPassword = $this->rsaWalletService->encryptWithPublicKey('wrong password', $this->keyPairUuid);

		$passwordHash = 'af1e8a52451786a6b3bf78838e03a0a2';
		$salt = 'a709157e66e0197cafa0c2ba99f6e252';

		$this->assertFalse($this->rsaWalletService->checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $this->keyPairUuid));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Security\Exception\DecryptionNotAllowedException
	 */
	public function decryptingWithAKeypairUUIDMarkedForPasswordUsageThrowsAnException() {
		$this->keyPairUuid = $this->rsaWalletService->generateNewKeypair(TRUE);
		$this->rsaWalletService->decrypt('some cipher', $this->keyPairUuid);
	}
}
?>