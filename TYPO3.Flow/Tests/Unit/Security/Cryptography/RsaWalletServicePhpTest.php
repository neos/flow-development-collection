<?php
namespace TYPO3\Flow\Tests\Unit\Security\Cryptography;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use org\bovigo\vfs\vfsStream;

/**
 * Testcase for for the PHP (OpenSSL) based RSAWalletService
 */
class RsaWalletServicePhpTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Security\Cryptography\RsaWalletServicePhp
	 */
	protected $rsaWalletService;

	/**
	 * @var string
	 */
	protected $keyPairUuid;

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
			vfsStream::setup('Foo');
			$settings['security']['cryptography']['RSAWalletServicePHP']['keystorePath'] = 'vfs://Foo/EncryptionKey';

			$this->rsaWalletService = $this->getAccessibleMock('TYPO3\Flow\Security\Cryptography\RsaWalletServicePhp', array('dummy'));
			$this->rsaWalletService->injectSettings($settings);

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
	public function signAndVerifySignatureBasicallyWorks() {
		$plaintext = 'trustworthy data!';
		$signature = $this->rsaWalletService->sign($plaintext, $this->keyPairUuid);

		$this->assertTrue($this->rsaWalletService->verifySignature($plaintext, $signature, $this->keyPairUuid));
		$this->assertFalse($this->rsaWalletService->verifySignature('modified data!', $signature, $this->keyPairUuid));
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
	 * @expectedException \TYPO3\Flow\Security\Exception\DecryptionNotAllowedException
	 */
	public function decryptingWithAKeypairUUIDMarkedForPasswordUsageThrowsAnException() {
		$this->keyPairUuid = $this->rsaWalletService->generateNewKeypair(TRUE);
		$this->rsaWalletService->decrypt('some cipher', $this->keyPairUuid);
	}

	/**
	 * @test
	 */
	public function shutdownSavesKeysToKeystoreFileIfKeysWereModified() {
		$this->assertFalse(file_exists('vfs://Foo/EncryptionKey'));
		$keyPairUuid = $this->rsaWalletService->generateNewKeypair(TRUE);
		$this->rsaWalletService->shutdownObject();

		$this->assertTrue(file_exists('vfs://Foo/EncryptionKey'));

		$this->rsaWalletService->destroyKeypair($keyPairUuid);
		$this->rsaWalletService->initializeObject();

		$this->rsaWalletService->getPublicKey($keyPairUuid);
	}

	/**
	 * @test
	 */
	public function shutdownDoesNotSavesKeysToKeystoreFileIfKeysWereNotModified() {
		$this->assertFalse(file_exists('vfs://Foo/EncryptionKey'));
		$keyPairUuid = $this->rsaWalletService->generateNewKeypair(TRUE);
		$this->rsaWalletService->shutdownObject();
		$this->assertTrue(file_exists('vfs://Foo/EncryptionKey'));

		$this->rsaWalletService->initializeObject();
		$this->rsaWalletService->getPublicKey($keyPairUuid);

			// Hack: remove the file so we can actually detect if shutdown() would write it:
		unlink('vfs://Foo/EncryptionKey');

		$this->rsaWalletService->shutdownObject();
		$this->assertFalse(file_exists('vfs://Foo/EncryptionKey'));
	}

	/**
	 * @test
	 */
	public function getFingerprintByPublicKeyCalculatesCorrectFingerprint() {
		$keyString = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDP7ZWzP/6x3SXyt0Al9UvyCe8D
TG6y1t7ovmWGw+D2x4BtZfbEHtNhlWHFkLLXzGKdgmzm4WjSB1fWQ1lfu5L8wY+g
HofCDIScx7AMgIB7hRB9ZMDEyWN/1vgSm8+4K4jUcD6OGLJYTSAlaQ7e2ZGaAY5h
p2P76gIh+wUlPjsr/QIDAQAB
-----END PUBLIC KEY-----';

		$this->assertEquals('cfa6879e3dfcf709db4cfd8e61fdd782', $this->rsaWalletService->getFingerprintByPublicKey($keyString));
	}

	/**
	 * @test
	 */
	public function registerPublicKeyFromStringUsesFingerprintAsUuid() {
		$keyString = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDP7ZWzP/6x3SXyt0Al9UvyCe8D
TG6y1t7ovmWGw+D2x4BtZfbEHtNhlWHFkLLXzGKdgmzm4WjSB1fWQ1lfu5L8wY+g
HofCDIScx7AMgIB7hRB9ZMDEyWN/1vgSm8+4K4jUcD6OGLJYTSAlaQ7e2ZGaAY5h
p2P76gIh+wUlPjsr/QIDAQAB
-----END PUBLIC KEY-----';

		$this->assertEquals('cfa6879e3dfcf709db4cfd8e61fdd782', $this->rsaWalletService->registerPublicKeyFromString($keyString));
	}

}
