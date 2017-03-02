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

use Neos\Flow\Security\Exception\DecryptionNotAllowedException;
use Neos\Flow\Security\Exception\InvalidKeyPairIdException;

/**
 * RSA related service functions (e.g. used for the RSA authentication provider)
 *
 */
interface RsaWalletServiceInterface
{
    /**
     * Generates a new keypair and returns a fingerprint to refer to it
     *
     * @param boolean $usedForPasswords TRUE if this keypair should be used to encrypt passwords (then decryption won't be allowed!).
     * @return string An fingerprint that identifies the generated keypair
     */
    public function generateNewKeypair($usedForPasswords = false);

    /**
     * Adds the specified keypair to the local store and returns a fingerprint to refer to it.
     *
     * @param string $privateKeyString The private key in its string representation
     * @param boolean $usedForPasswords TRUE if this keypair should be used to encrypt passwords (then decryption won't be allowed!).
     * @return string The fingerprint used for storing
     */
    public function registerKeyPairFromPrivateKeyString($privateKeyString, $usedForPasswords = false);

    /**
     * Adds the specified public key to the wallet and returns a fingerprint to refer to it.
     * This is helpful if you have not private key and want to use this key only to
     * verify incoming data.
     *
     * @param string $publicKeyString The public key in its string representation
     * @return string The fingerprint used for storing
     */
    public function registerPublicKeyFromString($publicKeyString);

    /**
     * Returns the public key for the given fingerprint
     *
     * @param string $fingerprint The fingerprint
     * @return OpenSslRsaKey The public key
     * @throws InvalidKeyPairIdException If the given fingerprint identifies no valid key pair
     */
    public function getPublicKey($fingerprint);

    /**
     * Decrypts the given cypher with the private key identified by the given fingerprint
     * Note: You should never decrypt a password with this function. Use checkRSAEncryptedPassword()
     * to check passwords!
     *
     * @param string $cypher Cypher text to decrypt
     * @param string $fingerprint The fingerprint to identify to correct private key
     * @return string The decrypted text
     * @throws InvalidKeyPairIdException If the given fingerprint identifies no valid keypair
     * @throws DecryptionNotAllowedException If the given fingerprint identifies a keypair for encrypted passwords
     */
    public function decrypt($cypher, $fingerprint);

    /**
     * Signs the given plaintext with the private key identified by the given fingerprint
     *
     * @param string $plaintext The plaintext to sign
     * @param string $fingerprint The fingerprint to identify to correct private key
     * @return string The signature of the given plaintext
     * @throws InvalidKeyPairIdException If the given fingerprint identifies no valid keypair
     */
    public function sign($plaintext, $fingerprint);

    /**
     * Checks whether the given signature is valid for the given plaintext
     * with the public key identified by the given fingerprint
     *
     * @param string $plaintext The plaintext to sign
     * @param string $signature The signature that should be verified
     * @param string $fingerprint The fingerprint to identify to correct public key
     * @return boolean TRUE if the signature is correct for the given plaintext and public key
     */
    public function verifySignature($plaintext, $signature, $fingerprint);

    /**
     * Encrypts the given plaintext with the public key identified by the given fingerprint
     *
     * @param string $plaintext The plaintext to encrypt
     * @param string $fingerprint The fingerprint to identify to correct public key
     * @return string The ciphertext
     */
    public function encryptWithPublicKey($plaintext, $fingerprint);

    /**
     * Checks if the given encrypted password is correct by
     * comparing it's md5 hash. The salt is appended to the decrypted password string before hashing.
     *
     * @param string $encryptedPassword The received, RSA encrypted password to check
     * @param string $passwordHash The md5 hashed password string (md5(md5(password) . salt))
     * @param string $salt The salt used in the md5 password hash
     * @param string $fingerprint The fingerprint to identify to correct private key
     * @return boolean TRUE if the password is correct
     */
    public function checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $fingerprint);

    /**
     * Destroys the keypair identified by the given fingerprint
     *
     * @param string $fingerprint The fingerprint
     * @return void
     * @throws InvalidKeyPairIdException If the given fingerprint identifies no valid key pair
     */
    public function destroyKeypair($fingerprint);
}
