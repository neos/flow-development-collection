<?php
namespace Neos\Flow\Security\Cryptography;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Exception as SecurityException;
use Neos\Flow\Security\Exception\DecryptionNotAllowedException;
use Neos\Flow\Security\Exception\InvalidKeyPairIdException;
use Neos\Flow\Security\Exception\MissingConfigurationException;

/**
 * Implementation of the RSAWalletServiceInterface using PHP's OpenSSL extension
 *
 * @Flow\Scope("singleton")
 */
class RsaWalletServicePhp implements RsaWalletServiceInterface
{
    /**
     * @var string
     */
    protected $keystorePathAndFilename;

    /**
     * @var array
     */
    protected $keys = [];

    /**
     * The openSSL configuration
     * @var array
     */
    protected $openSSLConfiguration = [];

    /**
     * @var boolean
     */
    protected $saveKeysOnShutdown = true;

    /**
     * Injects the OpenSSL configuration to be used
     *
     * @param array $settings
     * @return void
     * @throws MissingConfigurationException
     */
    public function injectSettings(array $settings)
    {
        if (isset($settings['security']['cryptography']['RSAWalletServicePHP']['openSSLConfiguration'])
            && is_array($settings['security']['cryptography']['RSAWalletServicePHP']['openSSLConfiguration'])) {
            $this->openSSLConfiguration = $settings['security']['cryptography']['RSAWalletServicePHP']['openSSLConfiguration'];
        }

        if (isset($settings['security']['cryptography']['RSAWalletServicePHP']['keystorePath'])) {
            $this->keystorePathAndFilename = $settings['security']['cryptography']['RSAWalletServicePHP']['keystorePath'];
        } else {
            throw new MissingConfigurationException('The configuration setting Neos.Flow.security.cryptography.RSAWalletServicePHP.keystorePath is missing. Please specify it in your Settings.yaml file. Beware: This file must not be accessible by the public!', 1305711354);
        }
    }

    /**
     * Initializes the rsa wallet service by fetching the keys from the keystore file
     *
     * @return void
     */
    public function initializeObject()
    {
        if (file_exists($this->keystorePathAndFilename)) {
            $this->keys = unserialize(file_get_contents($this->keystorePathAndFilename));
        }
        $this->saveKeysOnShutdown = false;
    }

    /**
     * Generates a new keypair and returns a fingerprint to refer to it
     *
     * @param boolean $usedForPasswords TRUE if this keypair should be used to encrypt passwords (then decryption won't be allowed!).
     * @return string The RSA public key fingerprint for reference
     * @throws SecurityException
     */
    public function generateNewKeypair($usedForPasswords = false)
    {
        $keyResource = openssl_pkey_new($this->openSSLConfiguration);

        if ($keyResource === false) {
            throw new SecurityException('OpenSSL private key generation failed.', 1254838154);
        }

        $modulus = $this->getModulus($keyResource);
        $privateKeyString = $this->getPrivateKeyString($keyResource);
        $publicKeyString = $this->getPublicKeyString($keyResource);

        $privateKey = new OpenSslRsaKey($modulus, $privateKeyString);
        $publicKey = new OpenSslRsaKey($modulus, $publicKeyString);

        return $this->storeKeyPair($publicKey, $privateKey, $usedForPasswords);
    }

    /**
     * Adds the specified keypair to the local store and returns a fingerprint to refer to it.
     *
     * @param string $privateKeyString The private key in its string representation
     * @param boolean $usedForPasswords TRUE if this keypair should be used to encrypt passwords (then decryption won't be allowed!).
     * @return string The RSA public key fingerprint for reference
     */
    public function registerKeyPairFromPrivateKeyString($privateKeyString, $usedForPasswords = false)
    {
        $keyResource = openssl_pkey_get_private($privateKeyString);

        $modulus = $this->getModulus($keyResource);
        $publicKeyString = $this->getPublicKeyString($keyResource);

        $privateKey = new OpenSslRsaKey($modulus, $privateKeyString);
        $publicKey = new OpenSslRsaKey($modulus, $publicKeyString);

        return $this->storeKeyPair($publicKey, $privateKey, $usedForPasswords);
    }

    /**
     * Adds the specified public key to the wallet and returns a fingerprint to refer to it.
     * This is helpful if you have not private key and want to use this key only to
     * verify incoming data.
     *
     * @param string $publicKeyString The public key in its string representation
     * @return string The RSA public key fingerprint for reference
     */
    public function registerPublicKeyFromString($publicKeyString)
    {
        $keyResource = openssl_pkey_get_public($publicKeyString);

        $modulus = $this->getModulus($keyResource);
        $publicKey = new OpenSslRsaKey($modulus, $publicKeyString);

        return $this->storeKeyPair($publicKey, null, false);
    }

    /**
     * Returns the public key for the given fingerprint
     *
     * @param string $fingerprint The fingerprint of the stored key
     * @return OpenSslRsaKey The public key
     * @throws InvalidKeyPairIdException If the given fingerprint identifies no valid key pair
     */
    public function getPublicKey($fingerprint)
    {
        if ($fingerprint === null || !isset($this->keys[$fingerprint])) {
            throw new InvalidKeyPairIdException('Invalid keypair fingerprint given', 1231438860);
        }

        return $this->keys[$fingerprint]['publicKey'];
    }

    /**
     * Encrypts the given plaintext with the public key identified by the given fingerprint
     *
     * @param string $plaintext The plaintext to encrypt
     * @param string $fingerprint The fingerprint to identify to correct public key
     * @return string The ciphertext
     */
    public function encryptWithPublicKey($plaintext, $fingerprint)
    {
        $cipher = '';
        openssl_public_encrypt($plaintext, $cipher, $this->getPublicKey($fingerprint)->getKeyString());

        return $cipher;
    }

    /**
     * Decrypts the given cipher with the private key identified by the given fingerprint
     * Note: You should never decrypt a password with this function. Use checkRSAEncryptedPassword()
     * to check passwords!
     *
     * @param string $cipher cipher text to decrypt
     * @param string $fingerprint The fingerprint to identify the private key (RSA public key fingerprint)
     * @return string The decrypted text
     * @throws InvalidKeyPairIdException If the given fingerprint identifies no valid keypair
     * @throws DecryptionNotAllowedException If the given fingerprint identifies a keypair for encrypted passwords
     */
    public function decrypt($cipher, $fingerprint)
    {
        if ($fingerprint === null || !isset($this->keys[$fingerprint])) {
            throw new InvalidKeyPairIdException('Invalid keypair fingerprint given', 1231438861);
        }

        $keyPair = $this->keys[$fingerprint];

        if ($keyPair['usedForPasswords']) {
            throw new DecryptionNotAllowedException('You are not allowed to decrypt passwords!', 1233655350);
        }

        return $this->decryptWithPrivateKey($cipher, $keyPair['privateKey']);
    }

    /**
     * Signs the given plaintext with the private key identified by the given fingerprint
     *
     * @param string $plaintext The plaintext to sign
     * @param string $fingerprint The fingerprint to identify the private key (RSA public key fingerprint)
     * @return string The signature of the given plaintext
     * @throws InvalidKeyPairIdException If the given fingerprint identifies no valid keypair
     */
    public function sign($plaintext, $fingerprint)
    {
        if ($fingerprint === null || !isset($this->keys[$fingerprint])) {
            throw new InvalidKeyPairIdException('Invalid keypair fingerprint given', 1299095799);
        }

        $signature = '';
        openssl_sign($plaintext, $signature, $this->keys[$fingerprint]['privateKey']->getKeyString());

        return $signature;
    }

    /**
     * Checks whether the given signature is valid for the given plaintext
     * with the public key identified by the given fingerprint
     *
     * @param string $plaintext The plaintext to sign
     * @param string $signature The signature that should be verified
     * @param string $fingerprint The fingerprint to identify the public key (RSA public key fingerprint)
     * @return boolean TRUE if the signature is correct for the given plaintext and public key
     * @throws InvalidKeyPairIdException
     */
    public function verifySignature($plaintext, $signature, $fingerprint)
    {
        if ($fingerprint === null || !isset($this->keys[$fingerprint])) {
            throw new InvalidKeyPairIdException('Invalid keypair fingerprint given', 1304959763);
        }

        $verifyResult = openssl_verify($plaintext, $signature, $this->getPublicKey($fingerprint)->getKeyString());

        return $verifyResult === 1;
    }

    /**
     * Checks if the given encrypted password is correct by
     * comparing it's md5 hash. The salt is appended to the decrypted password string before hashing.
     *
     * @param string $encryptedPassword The received, RSA encrypted password to check
     * @param string $passwordHash The md5 hashed password string (md5(md5(password) . salt))
     * @param string $salt The salt used in the md5 password hash
     * @param string $fingerprint The fingerprint to identify the private key (RSA public key fingerprint)
     * @return boolean TRUE if the password is correct
     * @throws InvalidKeyPairIdException If the given fingerprint identifies no valid keypair
     */
    public function checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $fingerprint)
    {
        if ($fingerprint === null || !isset($this->keys[$fingerprint])) {
            throw new InvalidKeyPairIdException('Invalid keypair fingerprint given', 1233655216);
        }

        $decryptedPassword = $this->decryptWithPrivateKey($encryptedPassword, $this->keys[$fingerprint]['privateKey']);

        return ($passwordHash === md5(md5($decryptedPassword) . $salt));
    }

    /**
     * Destroys the keypair identified by the given fingerprint
     *
     * @param string $fingerprint The fingerprint
     * @return void
     * @throws InvalidKeyPairIdException If the given fingerprint identifies no valid key pair
     */
    public function destroyKeypair($fingerprint)
    {
        if ($fingerprint === null || !isset($this->keys[$fingerprint])) {
            throw new InvalidKeyPairIdException('Invalid keypair fingerprint given', 1231438863);
        }

        unset($this->keys[$fingerprint]);
        $this->saveKeysOnShutdown = true;
    }

    /**
     * Exports the private key string from the KeyResource
     *
     * @param resource $keyResource The key resource
     * @return string The private key string
     */
    private function getPrivateKeyString($keyResource)
    {
        openssl_pkey_export($keyResource, $privateKeyString, null, $this->openSSLConfiguration);
        return $privateKeyString;
    }

    /**
     * Exports the public key string from the KeyResource
     *
     * @param resource $keyResource The key resource
     * @return string The public key string
     */
    private function getPublicKeyString($keyResource)
    {
        $keyDetails = openssl_pkey_get_details($keyResource);

        return $keyDetails['key'];
    }

    /**
     * Exports the public modulus HEX string from the KeyResource
     *
     * @param resource $keyResource The key resource
     * @return string The HEX public modulus string
     */
    private function getModulus($keyResource)
    {
        $keyDetails = openssl_pkey_get_details($keyResource);
        return strtoupper(bin2hex($keyDetails['rsa']['n']));
    }

    /**
     * Decrypts the given ciphertext with the given private key
     *
     * @param string $cipher The ciphertext to decrypt
     * @param OpenSslRsaKey $privateKey The private key
     * @return string The decrypted plaintext
     */
    private function decryptWithPrivateKey($cipher, OpenSslRsaKey $privateKey)
    {
        $decrypted = '';
        $key = openssl_pkey_get_private($privateKey->getKeyString());
        openssl_private_decrypt($cipher, $decrypted, $key);

        return $decrypted;
    }

    /**
     * Stores the given keypair and returns its fingerprint.
     *
     * The SSH fingerprint of the RSA public key will be used as an identifier for
     * consistent key access.
     *
     * @param OpenSslRsaKey $publicKey The public key
     * @param OpenSslRsaKey $privateKey The private key
     * @param boolean $usedForPasswords TRUE if this keypair should be used to encrypt passwords (then decryption won't be allowed!).
     * @return string The fingerprint which is used as an identifier for storing the key pair
     */
    private function storeKeyPair($publicKey, $privateKey, $usedForPasswords)
    {
        $publicKeyFingerprint = $this->getFingerprintByPublicKey($publicKey->getKeyString());

        $keyPair = [];
        $keyPair['publicKey'] = $publicKey;
        $keyPair['privateKey'] = $privateKey;
        $keyPair['usedForPasswords'] = $usedForPasswords;

        $this->keys[$publicKeyFingerprint] = $keyPair;
        $this->saveKeysOnShutdown = true;

        return $publicKeyFingerprint;
    }

    /**
     * Stores the keys array in the keystore file
     *
     * @return void
     * @throws SecurityException
     */
    public function shutdownObject()
    {
        if ($this->saveKeysOnShutdown === false) {
            return;
        }

        $temporaryKeystorePathAndFilename = $this->keystorePathAndFilename . uniqid() . '.temp';
        $result = file_put_contents($temporaryKeystorePathAndFilename, serialize($this->keys));

        if ($result === false) {
            throw new SecurityException('The temporary keystore file "' . $temporaryKeystorePathAndFilename . '" could not be written.', 1305812921);
        }
        $i = 0;
        while (($result = rename($temporaryKeystorePathAndFilename, $this->keystorePathAndFilename)) === false && $i < 5) {
            $i++;
        }
        if ($result === false) {
            throw new SecurityException('The keystore file "' . $this->keystorePathAndFilename . '" could not be written.', 1305812938);
        }
    }

    /**
     * Generate an OpenSSH fingerprint for a RSA public key
     *
     * See <http://tools.ietf.org/html/rfc4253#page-15> for reference of OpenSSH
     * "ssh-rsa" key format. The fingerprint is obtained by applying an MD5
     * hash on the raw public key bytes.
     *
     * If you have a PEM encoded private key, you can generate the same fingerprint
     * using this:
     *
     *  ssh-keygen -yf my-key.pem > my-key.pub
     *  ssh-keygen -lf my-key.pub
     *
     * @param string $publicKeyString RSA public key, PKCS1 encoded
     * @return string The public key fingerprint
     */
    public function getFingerprintByPublicKey($publicKeyString)
    {
        $keyResource = openssl_pkey_get_public($publicKeyString);
        $keyDetails = openssl_pkey_get_details($keyResource);
        $modulus = $this->sshConvertMpint($keyDetails['rsa']['n']);
        $publicExponent = $this->sshConvertMpint($keyDetails['rsa']['e']);

        $rsaPublicKey = pack('Na*Na*Na*', strlen('ssh-rsa'), 'ssh-rsa', strlen($publicExponent), $publicExponent, strlen($modulus), $modulus);

        return md5($rsaPublicKey);
    }

    /**
     * Convert a binary representation of a multiple precision integer
     * to mpint format defined for SSH RSA key exchange (used in "ssh-rsa" format).
     *
     * See <http://tools.ietf.org/html/rfc4251#page-8> for mpint encoding
     *
     * @param string $bytes Binary representation of integer
     * @return string The mpint encoded integer
     */
    private function sshConvertMpint($bytes)
    {
        if (empty($bytes)) {
            $bytes = chr(0);
        }

        if (ord($bytes[0]) & 0x80) {
            $bytes = chr(0) . $bytes;
        }
        return $bytes;
    }
}
