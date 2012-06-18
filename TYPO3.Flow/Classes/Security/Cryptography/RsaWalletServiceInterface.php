<?php
namespace TYPO3\FLOW3\Security\Cryptography;

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
 * RSA related service functions (e.g. used for the RSA authentication provider)
 *
 */
interface RsaWalletServiceInterface {

	/**
	 * Generates a new keypair and returns a UUID to refer to it
	 *
	 * @param boolean $usedForPasswords TRUE if this keypair should be used to encrypt passwords (then decryption won't be allowed!).
	 * @return integer An UUID that identifies the generated keypair
	 */
	public function generateNewKeypair($usedForPasswords = FALSE);

	/**
	 * Adds the specified keypair to the local store and returns a UUID to refer to it.
	 *
	 * @param string $privateKeyString The private key in its string representation
	 * @param boolean $usedForPasswords TRUE if this keypair should be used to encrypt passwords (then decryption won't be allowed!).
	 * @return string The UUID used for storing
	 */
	public function registerKeyPairFromPrivateKeyString($privateKeyString, $usedForPasswords = FALSE);

	/**
	 * Adds the specified public key to the wallet and returns a UUID to refer to it.
	 * This is helpful if you have not private key and want to use this key only to
	 * verify incoming data.
	 *
	 * @param string $publicKeyString The public key in its string representation
	 * @return string The UUID used for storing
	 */
	public function registerPublicKeyFromString($publicKeyString);

	/**
	 * Returns the public key for the given UUID
	 *
	 * @param string $uuid The UUID
	 * @return \TYPO3\FLOW3\Security\Cryptography\OpenSslRsaKey The public key
	 * @throws \TYPO3\FLOW3\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid key pair
	 */
	public function getPublicKey($uuid);

	/**
	 * Decrypts the given cypher with the private key identified by the given UUID
	 * Note: You should never decrypt a password with this function. Use checkRSAEncryptedPassword()
	 * to check passwords!
	 *
	 * @param string $cypher Cypher text to decrypt
	 * @param string $uuid The uuid to identify to correct private key
	 * @return string The decrypted text
	 * @throws \TYPO3\FLOW3\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid keypair
	 * @throws \TYPO3\FLOW3\Security\Exception\DecryptionNotAllowedException If the given UUID identifies a keypair for encrypted passwords
	 */
	public function decrypt($cypher, $uuid);

	/**
	 * Signs the given plaintext with the private key identified by the given UUID
	 *
	 * @param string $plaintext The plaintext to sign
	 * @param string $uuid The uuid to identify to correct private key
	 * @return string The signature of the given plaintext
	 * @throws \TYPO3\FLOW3\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid keypair
	 */
	public function sign($plaintext, $uuid);

	/**
	 * Checks whether the given signature is valid for the given plaintext
	 * with the public key identified by the given UUID
	 *
	 * @param string $plaintext The plaintext to sign
	 * @param string $signature The signature that should be verified
	 * @param string $uuid The uuid to identify to correct public key
	 * @return boolean TRUE if the signature is correct for the given plaintext and public key
	 */
	public function verifySignature($plaintext, $signature, $uuid);

	/**
	 * Encrypts the given plaintext with the public key identified by the given UUID
	 *
	 * @param string $plaintext The plaintext to encrypt
	 * @param string $uuid The uuid to identify to correct public key
	 * @return string The ciphertext
	 */
	public function encryptWithPublicKey($plaintext, $uuid);

	/**
	 * Checks if the given encrypted password is correct by
	 * comparing it's md5 hash. The salt is appendend to the decrypted password string before hashing.
	 *
	 * @param string $encryptedPassword The received, RSA encrypted password to check
	 * @param string $passwordHash The md5 hashed password string (md5(md5(password) . salt))
	 * @param string $salt The salt used in the md5 password hash
	 * @param string $uuid The uuid to identify to correct private key
	 * @return boolean TRUE if the password is correct
	 */
	public function checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $uuid);

	/**
	 * Destroys the keypair identified by the given UUID
	 *
	 * @param string $uuid The UUID
	 * @return void
	 * @throws \TYPO3\FLOW3\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid key pair
	 */
	public function destroyKeypair($uuid);
}
?>