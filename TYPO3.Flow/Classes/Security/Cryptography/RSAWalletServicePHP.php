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
 * @subpackage Security
 * @version $Id:$
 */

/**
 * Implementation of the RSAWalletServiceInterface using PHP's OpenSSL extension
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
final class RSAWalletServicePHP implements \F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface {

	/**
	 * The openSSL configuration
	 * @var array
	 */
	protected $openSSLConfiguration = array();

	/**
	 * Path to the keystore directory
	 * @var string
	 */
	protected $keystoreDirectory;

	/**
	 * Array of stored keys in the wallet
	 * @var array
	 */
	protected $keyStore = array();

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * Inject the settings to retrieve the keystore directory and open the RSAKeys file if exists
	 *
	 * @param array $settings The package settings
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSettings($settings) {
		$this->keystoreDirectory = $settings['security']['cryptography']['RSAWalletServicePHP']['keystore'];

		if (file_exists($this->keystoreDirectory . '/RSAKeys')) $this->keyStore = unserialize(file_get_contents($this->keystoreDirectory . '/RSAKeys'));
	}

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory The object factory
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Generates a new keypair and returns a UUID to refer to it
	 *
	 * @param boolean $usedForPasswords TRUE if this keypair should be used to decrypt passwords. (Decryption won't be allowed!)
	 * @return integer An UUID that identifies the generated keypair
	 */
	public function generateNewKeypair($usedForPasswords = FALSE) {

		$keyResource = openssl_pkey_new($this->openSSLConfiguration);

		$modulus = $this->getModulus($keyResource);
		$privateKeyString = $this->getPrivateKeyString($keyResource);
		$publicKeyString = $this->getPublicKeyString($keyResource);

		$privateKey = $this->objectFactory->create('F3\FLOW3\Security\Cryptography\OpenSSLRSAKey', $modulus, $privateKeyString);
		$publicKey = $this->objectFactory->create('F3\FLOW3\Security\Cryptography\OpenSSLRSAKey', $modulus, $publicKeyString);

		return $this->storeKeyPair($publicKey, $privateKey, $usedForPasswords);
	}

	/**
	 * Returns the public key for the given UUID
	 *
	 * @param UUID $UUID The UUID
	 * @return \F3\FLOW3\Security\Cryptography\OpenSSLRSAPublicKey The public key
	 * @throws \F3\FLOW3\Security\Exception\InvalidKeyPairID If the given UUID identifies no valid key pair
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPublicKey($UUID) {
		if (!isset($this->keyStore[$UUID])) throw new \F3\FLOW3\Security\Exception\InvalidKeyPairID('Invalid keypair UUID given', 1231438860);

		return $this->keyStore[$UUID]['publicKey'];
	}

	/**
	 * Encrypts the given plaintext with the public key identified by the given UUID
	 *
	 * @param string $plaintext The plaintext to encrypt
	 * @param UUID $uuid The UUID to identify to correct public key
	 * @return string The ciphertext
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function encryptWithPublicKey($plaintext, $UUID) {
		$cipher = '';
		openssl_public_encrypt($plaintext, &$cipher, $this->getPublicKey($UUID)->getKeyString());

		return $cipher;
	}

	/**
	 * Decrypts the given cipher with the private key identified by the given UUID
	 * Note: You should never decrypt a password with this function. Use checkRSAEncryptedPassword()
	 * to check passwords!
	 *
	 * @param string $cipher cipher text to decrypt
	 * @param UUID $UUID The uuid to identify to correct private key
	 * @return string The decrypted text
	 * @throws F3\FLOW3\Security\Exception\InvalidKeyPairID If the given UUID identifies no valid keypair
	 * @throws F3\FLOW3\Security\Exception\DecryptionNotAllowed If the given UUID identifies a keypair for encrypted passwords
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decrypt($cipher, $UUID) {
		if (!isset($this->keyStore[$UUID])) throw new \F3\FLOW3\Security\Exception\InvalidKeyPairID('Invalid keypair UUID given', 1231438861);
		if ($this->keyStore[$UUID]['usedForPasswords']) throw new \F3\FLOW3\Security\Exception\DecryptionNotAllowed('You are not allowed to decrypt passwords!', 1233655350);

		return $this->decryptWithPrivateKey($cipher, $this->keyStore[$UUID]['privateKey']);
	}

	/**
	 * Checks if the given encrypted password is correct by
	 * comparing it's md5 hash. The salt is appendend to the decrypted password string before hashing.
	 *
	 * @param string $encryptedPassword The received, RSA encrypted password to check
	 * @param string $passwordHash The md5 hashed password string (md5(md5(password) . salt))
	 * @param string $salt The salt used in the md5 password hash
	 * @param UUID $UUID The uuid to identify to correct private key
	 * @return boolean TRUE if the password is correct
	 * @throws F3\FLOW3\Security\Exception\InvalidKeyPairID If the given UUID identifies no valid keypair
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $UUID) {
		if (!isset($this->keyStore[$UUID])) throw new \F3\FLOW3\Security\Exception\InvalidKeyPairID('Invalid keypair UUID given', 1233655216);

		$decryptedPassword = $this->decryptWithPrivateKey($encryptedPassword, $this->keyStore[$UUID]['privateKey']);

		return ($passwordHash === md5(md5($decryptedPassword) . $salt));
	}

	/**
	 * Destroys the keypair identified by the given UUID
	 *
	 * @param UUID $UUID The UUDI
	 * @return void
	 * @throws F3\FLOW3\Security\Exception\InvalidKeyPairID If the given UUID identifies no valid key pair
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function destroyKeypair($UUID) {
		if (!isset($this->keyStore[$UUID])) throw new \F3\FLOW3\Security\Exception\InvalidKeyPairID('Invalid keypair UUID given', 1231438863);

		unset($this->keyStore[$UUID]);
		$this->writeData();
	}

	/**
	 * Ensures that all data is written to the keystore file
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function shutdownObject() {
		$this->writeData();
	}

	/**
	 * Exports the private key string from the KeyResource
	 *
	 * @param resource $keyResource The key Resource
	 * @return string The private key string
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	private function getPrivateKeyString($keyResource) {
		openssl_pkey_export($keyResource, &$privateKeyString, NULL, $this->openSSLConfiguration);
		return $privateKeyString;
	}

	/**
	 * Exports the public key string from the KeyResource
	 *
	 * @param resource $keyResource The key Resource
	 * @return string The public key string
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	private function getPublicKeyString($keyResource) {
		$keyDetails = openssl_pkey_get_details($keyResource);

		return $keyDetails['key'];
	}

	/**
	 * Exports the public modulus HEX string from the KeyResource
	 *
	 * @param resource $keyResource The key Resource
	 * @return string The HEX public modulus string
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	private function getModulus($keyResource) {
		$keyDetails = openssl_pkey_get_details($keyResource);
		return strtoupper(bin2hex($keyDetails['rsa']['n']));
	}

	/**
	 * Decrypts the given ciphertext with the given private key
	 *
	 * @param string $cipher The ciphertext to decrypt
	 * @param \F3\FLOW3\Security\Cryptography\OpenSSLRSAKey $privateKey The private key
	 * @return string The decrypted plaintext
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	private function decryptWithPrivateKey($cipher, \F3\FLOW3\Security\Cryptography\OpenSSLRSAKey $privateKey) {
		$decrypted = '';
		$key = openssl_pkey_get_private($privateKey->getKeyString());
		openssl_private_decrypt($cipher, &$decrypted, $key);

		return $decrypted;
	}

	/**
	 * Stores the given keypair with the given UUID.
	 *
	 * @param \F3\FLOW3\Security\Cryptography\OpenSSLRSAKey $publicKey The public key
	 * @param \F3\FLOW3\Security\Cryptography\OpenSSLRSAKey $privateKey The private key
	 * @param boolean $usedForPasswords TRUE if this keypair should be used to decrypt passwords. (Decryption won't be allowed!)
	 * @return UUID The UUID used for storing
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	private function storeKeyPair($publicKey, $privateKey, $usedForPasswords) {
		$keyPairUUID = \F3\FLOW3\Utility\Algorithms::generateUUID();

		$this->keyStore[$keyPairUUID] = array();
		$this->keyStore[$keyPairUUID]['publicKey'] = $publicKey;
		$this->keyStore[$keyPairUUID]['privateKey'] = $privateKey;
		$this->keyStore[$keyPairUUID]['usedForPasswords'] = $usedForPasswords;

		$this->writeData();

		return $keyPairUUID;
	}

	/**
	 * Write data to keystore file
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	private function writeData() {

		if (!is_writable($this->keystoreDirectory)) {
			try {
				\F3\FLOW3\Utility\Files::createDirectoryRecursively($this->keystoreDirectory);
			} catch(\Exception $exception) {
			}
			//if (!is_writable($this->keystoreDirectory)) throw new \F3\FLOW3\Security\Exception('The keystore directory "' . $this->keystoreDirectory . '" could not be created.', 1233700339);
		}

		file_put_contents($this->keystoreDirectory . '/RSAKeys', serialize($this->keyStore));
	}
}
?>