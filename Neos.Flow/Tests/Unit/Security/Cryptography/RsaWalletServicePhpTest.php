<?php
namespace Neos\Flow\Tests\Unit\Security\Cryptography;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use Neos\Flow\Security\Cryptography\RsaWalletServicePhp;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for for the PHP (OpenSSL) based RSAWalletService
 *
 * @requires function openssl_pkey_new
 */
class RsaWalletServicePhpTest extends UnitTestCase
{
    /**
     * @var RsaWalletServicePhp
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
    public function setUp()
    {
        vfsStream::setup('Foo');
        $settings['security']['cryptography']['RSAWalletServicePHP']['keystorePath'] = 'vfs://Foo/EncryptionKey';

        $this->rsaWalletService = $this->getAccessibleMock(RsaWalletServicePhp::class, ['dummy']);
        $this->rsaWalletService->injectSettings($settings);

        $this->keyPairUuid = $this->rsaWalletService->generateNewKeypair();
    }

    /**
     * @test
     */
    public function encryptingAndDecryptingBasicallyWorks()
    {
        $plaintext = 'some very sensitive data!';
        $ciphertext = $this->rsaWalletService->encryptWithPublicKey($plaintext, $this->keyPairUuid);

        $this->assertNotEquals($ciphertext, $plaintext);
        $this->assertEquals($plaintext, $this->rsaWalletService->decrypt($ciphertext, $this->keyPairUuid));
    }

    /**
     * @test
     */
    public function signAndVerifySignatureBasicallyWorks()
    {
        $plaintext = 'trustworthy data!';
        $signature = $this->rsaWalletService->sign($plaintext, $this->keyPairUuid);

        $this->assertTrue($this->rsaWalletService->verifySignature($plaintext, $signature, $this->keyPairUuid));
        $this->assertFalse($this->rsaWalletService->verifySignature('modified data!', $signature, $this->keyPairUuid));
    }

    /**
     * @test
     */
    public function checkRSAEncryptedPasswordReturnsTrueForACorrectPassword()
    {
        $encryptedPassword = $this->rsaWalletService->encryptWithPublicKey('password', $this->keyPairUuid);

        $passwordHash = 'af1e8a52451786a6b3bf78838e03a0a2';
        $salt = 'a709157e66e0197cafa0c2ba99f6e252';

        $this->assertTrue($this->rsaWalletService->checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $this->keyPairUuid));
    }

    /**
     * @test
     */
    public function checkRSAEncryptedPasswordReturnsFalseForAnIncorrectPassword()
    {
        $encryptedPassword = $this->rsaWalletService->encryptWithPublicKey('wrong password', $this->keyPairUuid);

        $passwordHash = 'af1e8a52451786a6b3bf78838e03a0a2';
        $salt = 'a709157e66e0197cafa0c2ba99f6e252';

        $this->assertFalse($this->rsaWalletService->checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $this->keyPairUuid));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\DecryptionNotAllowedException
     */
    public function decryptingWithAKeypairUUIDMarkedForPasswordUsageThrowsAnException()
    {
        $this->keyPairUuid = $this->rsaWalletService->generateNewKeypair(true);
        $this->rsaWalletService->decrypt('some cipher', $this->keyPairUuid);
    }

    /**
     * @test
     */
    public function shutdownSavesKeysToKeystoreFileIfKeysWereModified()
    {
        $this->assertFalse(file_exists('vfs://Foo/EncryptionKey'));
        $keyPairUuid = $this->rsaWalletService->generateNewKeypair(true);
        $this->rsaWalletService->shutdownObject();

        $this->assertTrue(file_exists('vfs://Foo/EncryptionKey'));

        $this->rsaWalletService->destroyKeypair($keyPairUuid);
        $this->rsaWalletService->initializeObject();

        $this->rsaWalletService->getPublicKey($keyPairUuid);
    }

    /**
     * @test
     */
    public function shutdownDoesNotSavesKeysToKeystoreFileIfKeysWereNotModified()
    {
        $this->assertFalse(file_exists('vfs://Foo/EncryptionKey'));
        $keyPairUuid = $this->rsaWalletService->generateNewKeypair(true);
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
    public function getFingerprintByPublicKeyCalculatesCorrectFingerprint()
    {
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
    public function registerPublicKeyFromStringUsesFingerprintAsUuid()
    {
        $keyString = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDP7ZWzP/6x3SXyt0Al9UvyCe8D
TG6y1t7ovmWGw+D2x4BtZfbEHtNhlWHFkLLXzGKdgmzm4WjSB1fWQ1lfu5L8wY+g
HofCDIScx7AMgIB7hRB9ZMDEyWN/1vgSm8+4K4jUcD6OGLJYTSAlaQ7e2ZGaAY5h
p2P76gIh+wUlPjsr/QIDAQAB
-----END PUBLIC KEY-----';

        $this->assertEquals('cfa6879e3dfcf709db4cfd8e61fdd782', $this->rsaWalletService->registerPublicKeyFromString($keyString));
    }
}
