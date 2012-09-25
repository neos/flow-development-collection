<?php
namespace TYPO3\Flow\Security\Cryptography;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Implementation of the RSAWalletServiceInterface using PHP's OpenSSL extension
 *
 * @Flow\Scope("singleton")
 */
class RsaWalletServicePhp implements \TYPO3\Flow\Security\Cryptography\RsaWalletServiceInterface {

	/**
	 * @var string
	 */
	protected $keystorePathAndFilename;

	/**
	 * @var array
	 */
	protected $keys = array();

	/**
	 * The openSSL configuration
	 * @var array
	 */
	protected $openSSLConfiguration = array();

	/**
	 * @var boolean
	 */
	protected $saveKeysOnShutdown = TRUE;

	/**
	 * Injects the OpenSSL configuration to be used
	 *
	 * @param array $settings
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\MissingConfigurationException
	 */
	public function injectSettings(array $settings) {
		if (isset($settings['security']['cryptography']['RSAWalletServicePHP']['openSSLConfiguration'])
			&& is_array($settings['security']['cryptography']['RSAWalletServicePHP']['openSSLConfiguration'])) {

			$this->openSSLConfiguration = $settings['security']['cryptography']['RSAWalletServicePHP']['openSSLConfiguration'];
		}

		if (isset($settings['security']['cryptography']['RSAWalletServicePHP']['keystorePath'])) {
			$this->keystorePathAndFilename = $settings['security']['cryptography']['RSAWalletServicePHP']['keystorePath'];
		} else {
			throw new \TYPO3\Flow\Security\Exception\MissingConfigurationException('The configuration setting TYPO3.Flow.security.cryptography.RSAWalletServicePHP.keystorePath is missing. Please specify it in your Settings.yaml file. Beware: This file must not be accessible by the public!', 1305711354);
		}
	}

	/**
	 * Initializes the rsa wallet service by fetching the keys from the keystore file
	 *
	 * @return void
	 */
	public function initializeObject() {
		if (file_exists($this->keystorePathAndFilename)) {
			$this->keys = unserialize(file_get_contents($this->keystorePathAndFilename));
		}
		$this->saveKeysOnShutdown = FALSE;
	}

	/**
	 * Generates a new keypair and returns a UUID to refer to it
	 *
	 * @param boolean $usedForPasswords TRUE if this keypair should be used to encrypt passwords (then decryption won't be allowed!).
	 * @return string An UUID that identifies the generated keypair
	 * @throws \TYPO3\Flow\Security\Exception
	 */
	public function generateNewKeypair($usedForPasswords = FALSE) {
		$keyResource = openssl_pkey_new($this->openSSLConfiguration);

		if ($keyResource === FALSE) {
			throw new \TYPO3\Flow\Security\Exception('OpenSSL private key generation failed.', 1254838154);
		}

		$modulus = $this->getModulus($keyResource);
		$privateKeyString = $this->getPrivateKeyString($keyResource);
		$publicKeyString = $this->getPublicKeyString($keyResource);

		$privateKey = new OpenSslRsaKey($modulus, $privateKeyString);
		$publicKey = new OpenSslRsaKey($modulus, $publicKeyString);

		return $this->storeKeyPair($publicKey, $privateKey, $usedForPasswords);
	}

	/**
	 * Adds the specified keypair to the local store and returns a UUID to refer to it.
	 *
	 * @param string $privateKeyString The private key in its string representation
	 * @param boolean $usedForPasswords TRUE if this keypair should be used to encrypt passwords (then decryption won't be allowed!).
	 * @return string The UUID used for storing
	 */
	public function registerKeyPairFromPrivateKeyString($privateKeyString, $usedForPasswords = FALSE) {
		$keyResource = openssl_pkey_get_private($privateKeyString);

		$modulus = $this->getModulus($keyResource);
		$publicKeyString = $this->getPublicKeyString($keyResource);

		$privateKey = new OpenSslRsaKey($modulus, $privateKeyString);
		$publicKey = new OpenSslRsaKey($modulus, $publicKeyString);

		return $this->storeKeyPair($publicKey, $privateKey, $usedForPasswords);
	}

	/**
	 * Adds the specified public key to the wallet and returns a UUID to refer to it.
	 * This is helpful if you have not private key and want to use this key only to
	 * verify incoming data.
	 *
	 * @param string $publicKeyString The public key in its string representation
	 * @return string The UUID used for storing
	 */
	public function registerPublicKeyFromString($publicKeyString) {
		$keyResource = openssl_pkey_get_public($publicKeyString);

		$modulus = $this->getModulus($keyResource);
		$publicKey = new OpenSslRsaKey($modulus, $publicKeyString);

		return $this->storeKeyPair($publicKey, NULL, FALSE);
	}

	/**
	 * Returns the public key for the given UUID
	 *
	 * @param string $uuid The UUID
	 * @return \TYPO3\Flow\Security\Cryptography\OpenSslRsaKey The public key
	 * @throws \TYPO3\Flow\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid key pair
	 */
	public function getPublicKey($uuid) {
		if ($uuid === NULL || !isset($this->keys[$uuid])) {
			throw new \TYPO3\Flow\Security\Exception\InvalidKeyPairIdException('Invalid keypair UUID given', 1231438860);
		}

		return $this->keys[$uuid]['publicKey'];
	}

	/**
	 * Encrypts the given plaintext with the public key identified by the given UUID
	 *
	 * @param string $plaintext The plaintext to encrypt
	 * @param string $uuid The UUID to identify to correct public key
	 * @return string The ciphertext
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
	 * @param string $uuid The uuid to identify to correct private key
	 * @return string The decrypted text
	 * @throws \TYPO3\Flow\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid keypair
	 * @throws \TYPO3\Flow\Security\Exception\DecryptionNotAllowedException If the given UUID identifies a keypair for encrypted passwords
	 */
	public function decrypt($cipher, $uuid) {
		if ($uuid === NULL || !isset($this->keys[$uuid])) {
			throw new \TYPO3\Flow\Security\Exception\InvalidKeyPairIdException('Invalid keypair UUID given', 1231438861);
		}

		$keyPair = $this->keys[$uuid];

		if ($keyPair['usedForPasswords']) {
			throw new \TYPO3\Flow\Security\Exception\DecryptionNotAllowedException('You are not allowed to decrypt passwords!', 1233655350);
		}

		return $this->decryptWithPrivateKey($cipher, $keyPair['privateKey']);
	}

	/**
	 * Signs the given plaintext with the private key identified by the given UUID
	 *
	 * @param string $plaintext The plaintext to sign
	 * @param string $uuid The uuid to identify to correct private key
	 * @return string The signature of the given plaintext
	 * @throws \TYPO3\Flow\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid keypair
	 */
	public function sign($plaintext, $uuid) {
		if ($uuid === NULL || !isset($this->keys[$uuid])) {
			throw new \TYPO3\Flow\Security\Exception\InvalidKeyPairIdException('Invalid keypair UUID given', 1299095799);
		}

		$signature = '';
		openssl_sign($plaintext, $signature, $this->keys[$uuid]['privateKey']);

		return $signature;
	}

	/**
	 * Checks whether the given signature is valid for the given plaintext
	 * with the public key identified by the given UUID
	 *
	 * @param string $plaintext The plaintext to sign
	 * @param string $signature The signature that should be verified
	 * @param string $uuid The uuid to identify to correct public key
	 * @return boolean TRUE if the signature is correct for the given plaintext and public key
	 * @throws \TYPO3\Flow\Security\Exception\InvalidKeyPairIdException
	 */
	public function verifySignature($plaintext, $signature, $uuid) {
		if ($uuid === NULL || !isset($this->keys[$uuid])) {
			throw new \TYPO3\Flow\Security\Exception\InvalidKeyPairIdException('Invalid keypair UUID given', 1304959763);
		}

		$verifyResult = openssl_verify($plaintext, $signature, $this->getPublicKey($uuid)->getKeyString());

		return $verifyResult === 1;
	}

	/**
	 * Checks if the given encrypted password is correct by
	 * comparing it's md5 hash. The salt is appended to the decrypted password string before hashing.
	 *
	 * @param string $encryptedPassword The received, RSA encrypted password to check
	 * @param string $passwordHash The md5 hashed password string (md5(md5(password) . salt))
	 * @param string $salt The salt used in the md5 password hash
	 * @param string $uuid The uuid to identify to correct private key
	 * @return boolean TRUE if the password is correct
	 * @throws \TYPO3\Flow\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid keypair
	 */
	public function checkRSAEncryptedPassword($encryptedPassword, $passwordHash, $salt, $uuid) {
		if ($uuid === NULL || !isset($this->keys[$uuid])) {
			throw new \TYPO3\Flow\Security\Exception\InvalidKeyPairIdException('Invalid keypair UUID given', 1233655216);
		}

		$decryptedPassword = $this->decryptWithPrivateKey($encryptedPassword, $this->keys[$uuid]['privateKey']);

		return ($passwordHash === md5(md5($decryptedPassword) . $salt));
	}

	/**
	 * Destroys the keypair identified by the given UUID
	 *
	 * @param string $uuid The UUID
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\InvalidKeyPairIdException If the given UUID identifies no valid key pair
	 */
	public function destroyKeypair($uuid) {
		if ($uuid === NULL || !isset($this->keys[$uuid])) {
			throw new \TYPO3\Flow\Security\Exception\InvalidKeyPairIdException('Invalid keypair UUID given', 1231438863);
		}

		unset($this->keys[$uuid]);
		$this->saveKeysOnShutdown = TRUE;
	}

	/**
	 * Exports the private key string from the KeyResource
	 *
	 * @param resource $keyResource The key Resource
	 * @return string The private key string
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
	 */
	private function getModulus($keyResource) {
		$keyDetails = openssl_pkey_get_details($keyResource);
		return strtoupper(bin2hex($keyDetails['rsa']['n']));
	}

	/**
	 * Decrypts the given ciphertext with the given private key
	 *
	 * @param string $cipher The ciphertext to decrypt
	 * @param \TYPO3\Flow\Security\Cryptography\OpenSslRsaKey $privateKey The private key
	 * @return string The decrypted plaintext
	 */
	private function decryptWithPrivateKey($cipher, \TYPO3\Flow\Security\Cryptography\OpenSslRsaKey $privateKey) {
		$decrypted = '';
		$key = openssl_pkey_get_private($privateKey->getKeyString());
		openssl_private_decrypt($cipher, $decrypted, $key);

		return $decrypted;
	}

	/**
	 * Stores the given keypair under the returned UUID.
	 *
	 * @param \TYPO3\Flow\Security\Cryptography\OpenSslRsaKey $publicKey The public key
	 * @param \TYPO3\Flow\Security\Cryptography\OpenSslRsaKey $privateKey The private key
	 * @param boolean $usedForPasswords TRUE if this keypair should be used to encrypt passwords (then decryption won't be allowed!).
	 * @return string The UUID used for storing
	 */
	private function storeKeyPair($publicKey, $privateKey, $usedForPasswords) {
		$keyPairUUID = str_replace('-', '_', \TYPO3\Flow\Utility\Algorithms::generateUUID());

		$keyPair = array();
		$keyPair['publicKey'] = $publicKey;
		$keyPair['privateKey'] = $privateKey;
		$keyPair['usedForPasswords'] = $usedForPasswords;

		$this->keys[$keyPairUUID] = $keyPair;
		$this->saveKeysOnShutdown = TRUE;

		return $keyPairUUID;
	}

	/**
	 * Stores the keys array in the keystore file
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception
	 */
	public function shutdownObject() {
		if ($this->saveKeysOnShutdown === FALSE) {
			return;
		}

		$temporaryKeystorePathAndFilename = $this->keystorePathAndFilename . uniqid() . '.temp';
		$result = file_put_contents($temporaryKeystorePathAndFilename, serialize($this->keys));

		if ($result === FALSE) {
			throw new \TYPO3\Flow\Security\Exception('The temporary keystore file "' . $temporaryKeystorePathAndFilename . '" could not be written.', 1305812921);
		}
		$i = 0;
		while (($result = rename($temporaryKeystorePathAndFilename, $this->keystorePathAndFilename)) === FALSE && $i < 5) {
			$i++;
		}
		if ($result === FALSE) {
			throw new \TYPO3\Flow\Security\Exception('The keystore file "' . $this->keystorePathAndFilename . '" could not be written.', 1305812938);
		}
	}
}
?>