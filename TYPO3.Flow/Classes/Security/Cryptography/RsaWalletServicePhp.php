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
 * Implementation of the RSAWalletServiceInterface using PHP's OpenSSL extension
 *
 * @version $Id: RsaWalletServicePhp.php -1   $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
final class RsaWalletServicePhp implements \F3\FLOW3\Security\Cryptography\RsaWalletServiceInterface {

	/**
	 * The openSSL configuration
	 * @var array
	 */
	protected $openSSLConfiguration = array();

	/**
	 * The keystore cache
	 * @var \F3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $keystoreCache;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager The object factory
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the openSSL configuration to be used
	 *
	 * @param array $settings
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSettings(array $settings) {
		if (isset($settings['security']['cryptography']['RSAWalletServicePHP']['openSSLConfiguration'])
			&& is_array($settings['security']['cryptography']['RSAWalletServicePHP']['openSSLConfiguration'])) {

			$this->openSSLConfiguration = $settings['security']['cryptography']['RSAWalletServicePHP']['openSSLConfiguration'];
		}
	}

	/**
	 * Injects the cache for storing rsa keys
	 *
	 * @param \F3\FLOW3\Cache\Frontend\VariableFrontend $keystoreCache
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectKeystoreCache(\F3\FLOW3\Cache\Frontend\VariableFrontend $keystoreCache) {
		$this->keystoreCache = $keystoreCache;
	}

	/**
	 * Generates a new keypair and returns a UUID to refer to it
	 *
	 * @param boolean $usedForPasswords TRUE if this keypair should be used to decrypt passwords. (Decryption won't be allowed!)
	 * @return uuid An UUID that identifies the generated keypair
	 */
	public function generateNewKeypair($usedForPasswords = FALSE) {

		$keyResource = openssl_pkey_new($this->openSSLConfiguration);

		if ($keyResource === FALSE) {
			throw new \F3\FLOW3\Security\Exception('OpenSSL private key generation failed.', 1254838154);
		}

		$modulus = $this->getModulus($keyResource);
		$privateKeyString = $this->getPrivateKeyString($keyResource);
		$publicKeyString = $this->getPublicKeyString($keyResource);

		$privateKey = $this->objectManager->create('F3\FLOW3\Security\Cryptography\OpenSslRsaKey', $modulus, $privateKeyString);
		$publicKey = $this->objectManager->create('F3\FLOW3\Security\Cryptography\OpenSslRsaKey', $modulus, $publicKeyString);

		return $this->storeKeyPair($publicKey, $privateKey, $usedForPasswords);
	}

	/**
	 * Returns the public key for the given UUID
	 *
	 * @param UUID $uuid The UUID
	 * @return \F3\FLOW3\Security\Cryptography\OpenSSLRSAPublicKey The public key
	 * @throws \F3\FLOW3\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid key pair
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPublicKey($uuid) {
		if ($uuid === NULL || !$this->keystoreCache->has($uuid)) throw new \F3\FLOW3\Security\Exception\InvalidKeyPairIdException('Invalid keypair UUID given', 1231438860);

		$keyPair = $this->keystoreCache->get($uuid);

		return $keyPair['publicKey'];
	}

	/**
	 * Encrypts the given plaintext with the public key identified by the given UUID
	 *
	 * @param string $plaintext The plaintext to encrypt
	 * @param UUID $uuid The UUID to identify to correct public key
	 * @return string The ciphertext
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function encryptWithPublicKey($plaintext, $uuid) {
		$cipher = '';
		openssl_public_encrypt($plaintext, $cipher, $this->getPublicKey($uuid)->getKeyString());

		return $cipher;
	}

	/**
	 * Decrypts the given cipher with the private key identified by the given UUID
	 * Note: You should never decrypt a password with this function. Use checkRSAEncryptedPassword()
	 * to check passwords!
	 *
	 * @param string $cipher cipher text to decrypt
	 * @param UUID $uuid The uuid to identify to correct private key
	 * @return string The decrypted text
	 * @throws F3\FLOW3\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid keypair
	 * @throws F3\FLOW3\Security\Exception\DecryptionNotAllowedException If the given UUID identifies a keypair for encrypted passwords
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decrypt($cipher, $uuid) {
		if ($uuid === NULL || !$this->keystoreCache->has($uuid)) throw new \F3\FLOW3\Security\Exception\InvalidKeyPairIdException('Invalid keypair UUID given', 1231438861);

		$keyPair = $this->keystoreCache->get($uuid);

		if ($keyPair['usedForPasswords']) throw new \F3\FLOW3\Security\Exception\DecryptionNotAllowedException('You are not allowed to decrypt passwords!', 1233655350);

		return $this->decryptWithPrivateKey($cipher, $keyPair['privateKey']);
	}

	/**
	 * Checks if the given encrypted password is correct by
	 * comparing it's md5 hash. The salt is appendend to the decrypted password string before hashing.
	 *
	 * @param string $encryptedPassword The received, RSA encrypted password to check
	 * @param string $passwordHash The md5 hashed password string (md5(md5(password) . salt))
	 * @param string $salt The salt used in the md5 password hash
	 * @param UUID $uuid The uuid to identify to correct private key
	 * @return boolean TRUE if the password is correct
	 * @throws F3\FLOW3\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid keypair
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $uuid) {
		if ($uuid === NULL || !$this->keystoreCache->has($uuid)) throw new \F3\FLOW3\Security\Exception\InvalidKeyPairIdException('Invalid keypair UUID given', 1233655216);

		$keyPair = $this->keystoreCache->get($uuid);

		$decryptedPassword = $this->decryptWithPrivateKey($encryptedPassword, $keyPair['privateKey']);

		return ($passwordHash === md5(md5($decryptedPassword) . $salt));
	}

	/**
	 * Destroys the keypair identified by the given UUID
	 *
	 * @param UUID $uuid The UUDI
	 * @return void
	 * @throws F3\FLOW3\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid key pair
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function destroyKeypair($uuid) {

		try {
			$this->keystoreCache->remove($uuid);
		} catch (\InvalidArgumentException $e) {
			 throw new \F3\FLOW3\Security\Exception\InvalidKeyPairIdException('Invalid keypair UUID given', 1231438863);
		}
	}

	/**
	 * Exports the private key string from the KeyResource
	 *
	 * @param resource $keyResource The key Resource
	 * @return string The private key string
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	private function getPrivateKeyString($keyResource) {
		openssl_pkey_export($keyResource, $privateKeyString, NULL, $this->openSSLConfiguration);
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
	 * @param \F3\FLOW3\Security\Cryptography\OpenSslRsaKey $privateKey The private key
	 * @return string The decrypted plaintext
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	private function decryptWithPrivateKey($cipher, \F3\FLOW3\Security\Cryptography\OpenSslRsaKey $privateKey) {
		$decrypted = '';
		$key = openssl_pkey_get_private($privateKey->getKeyString());
		openssl_private_decrypt($cipher, $decrypted, $key);

		return $decrypted;
	}

	/**
	 * Stores the given keypair under the returned UUID.
	 *
	 * @param \F3\FLOW3\Security\Cryptography\OpenSslRsaKey $publicKey The public key
	 * @param \F3\FLOW3\Security\Cryptography\OpenSslRsaKey $privateKey The private key
	 * @param boolean $usedForPasswords TRUE if this keypair should be used to decrypt passwords. (Decryption won't be allowed!)
	 * @return UUID The UUID used for storing
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	private function storeKeyPair($publicKey, $privateKey, $usedForPasswords) {
		$keyPairUUID = str_replace('-', '_', \F3\FLOW3\Utility\Algorithms::generateUUID());

		$keyPair = array();
		$keyPair['publicKey'] = $publicKey;
		$keyPair['privateKey'] = $privateKey;
		$keyPair['usedForPasswords'] = $usedForPasswords;

		$this->keystoreCache->set($keyPairUUID, $keyPair);

		return $keyPairUUID;
	}
}
?>