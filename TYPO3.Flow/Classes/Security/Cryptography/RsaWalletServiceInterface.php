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
 * RSA related service functions (e.g. used for the RSA authentication provider)
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 */
interface RsaWalletServiceInterface {


	/**
	 * Generates a new keypair and returns a UUID to refer to it
	 *
	 * @param boolean $usedForPasswords TRUE if this keypair should be used to decrypt passwords. (Decryption won't be allowed!)
	 * @return integer An UUID that identifies the generated keypair
	 */
	public function generateNewKeypair($usedForPasswords);

	/**
	 * Returns the public key for the given UUID
	 *
	 * @param UUID $uuid The UUID
	 * @return F3\FLOW3\Security\Cryptography\RSAKey The public key
	 * @throws F3\FLOW3\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid key pair
	 */
	public function getPublicKey($uuid);

	/**
	 * Decrypts the given cypher with the private key identified by the given UUID
	 * Note: You should never decrypt a password with this function. Use checkRSAEncryptedPassword()
	 * to check passwords!
	 *
	 * @param string $cypher Cypher text to decrypt
	 * @param UUID $uuid The uuid to identify to correct private key
	 * @return string The decrypted text
	 * @throws F3\FLOW3\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid keypair
	 * @throws F3\FLOW3\Security\Exception\DecryptionNotAllowedException If the given UUID identifies a keypair for encrypted passwords
	 */
	public function decrypt($cypher, $uuid);

	/**
	 * Encrypts the given plaintext with the public key identified by the given UUID
	 *
	 * @param string $plaintext The plaintext to encrypt
	 * @param UUID $uuid The uuid to identify to correct public key
	 * @return string The ciphertext
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function encryptWithPublicKey($plaintext, $uuid);

	/**
	 * Checks if the given encrypted password is correct by
	 * comparing it's md5 hash. The salt is appendend to the decrypted password string before hashing.
	 *
	 * @param string $encryptedPassword The received, RSA encrypted password to check
	 * @param string $passwordHash The md5 hashed password string (md5(md5(password) . salt))
	 * @param string $salt The salt used in the md5 password hash
	 * @param UUID $uuid The uuid to identify to correct private key
	 * @return boolean TRUE if the password is correct
	 */
	public function checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $uuid);

	/**
	 * Destroys the keypair identified by the given UUID
	 *
	 * @param UUID $uuid The UUID
	 * @return void
	 * @throws F3\FLOW3\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid key pair
	 */
	public function destroyKeypair($uuid);
}
?>