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

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A hash service which should be used to generate and validate hashes.
 *
 * @FLOW3\Scope("singleton")
 */
class HashService {

	/**
	 * A private, unique key used for encryption tasks.
	 * @var string
	 */
	protected $encryptionKey;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Cryptography\PasswordHashingStrategyInterface
	 */
	protected $passwordHashingStrategy;

	/**
	 */
	public function __construct() {
		if (!file_exists(FLOW3_PATH_DATA . 'Persistent/EncryptionKey')) {
			file_put_contents(FLOW3_PATH_DATA . 'Persistent/EncryptionKey', bin2hex(\TYPO3\FLOW3\Utility\Algorithms::generateRandomBytes(96)));
		}
		$this->encryptionKey = file_get_contents(FLOW3_PATH_DATA . 'Persistent/EncryptionKey');

		if (empty($this->encryptionKey)) {
			throw new \TYPO3\FLOW3\Security\Exception\MissingConfigurationException('No encryption key for the HashService was found and none could be created at "' . FLOW3_PATH_DATA . 'Persistent/EncryptionKey"', 1258991855);
		}
	}

	/**
	 * Generate a hash (HMAC) for a given string
	 *
	 * @param string $string The string for which a hash should be generated
	 * @return string The hash of the string
	 * @throws TYPO3\FLOW3\Security\Exception\InvalidArgumentForHashGenerationException if something else than a string was given as parameter
	 */
	public function generateHmac($string) {
		if (!is_string($string)) {
			throw new \TYPO3\FLOW3\Security\Exception\InvalidArgumentForHashGenerationException('A hash can only be generated for a string, but "' . gettype($string) . '" was given.', 1255069587);
		}

		return hash_hmac('sha1', $string, $this->encryptionKey);
	}

	/**
	 * Appends a hash (HMAC) to a given string and returns the result
	 *
	 * @param string $string The string for which a hash should be generated
	 * @return string The original string with HMAC of the string appended
	 * @see generateHmac()
	 * @todo Mark as API once it is more stable
	 */
	public function appendHmac($string) {
		$hmac = $this->generateHmac($string);
		return $string . $hmac;
	}

	/**
	 * Tests if a string $string matches the HMAC given by $hash.
	 *
	 * @param string $string The string which should be validated
	 * @param string $hmac The hash of the string
	 * @return boolean TRUE if string and hash fit together, FALSE otherwise.
	 */
	public function validateHmac($string, $hmac) {
		return ($this->generateHmac($string) === $hmac);
	}

	/**
	 * Tests if the last 40 characters of a given string $string
	 * matches the HMAC of the rest of the string and, if true,
	 * returns the string without the HMAC. In case of a HMAC
	 * validation error, an exception is thrown.
	 *
	 * @param string $string The string with the HMAC appended (in the format 'string<HMAC>')
	 * @return string the original string without the HMAC, if validation was successful
	 * @see validateHmac()
	 * @throws \TYPO3\FLOW3\Security\Exception\InvalidArgumentForHashGenerationException if the given string is not well-formatted
	 * @throws \TYPO3\FLOW3\Security\Exception\InvalidHashException if the hash did not fit to the data.
	 * @todo Mark as API once it is more stable
	 */
	public function validateAndStripHmac($string) {
		if (!is_string($string)) {
			throw new \TYPO3\FLOW3\Security\Exception\InvalidArgumentForHashGenerationException('A hash can only be validated for a string, but "' . gettype($string) . '" was given.', 1320829762);
		}
		if (strlen($string) < 40) {
			throw new \TYPO3\FLOW3\Security\Exception\InvalidArgumentForHashGenerationException('A hashed string must contain at least 40 characters, the given string was only ' . strlen($string) . ' characters long.', 1320830276);
		}
		$stringWithoutHmac = substr($string, 0, -40);
		if ($this->validateHmac($stringWithoutHmac, substr($string, -40)) !== TRUE) {
			throw new \TYPO3\FLOW3\Security\Exception\InvalidHashException('The given string was not appended with a valid HMAC.', 1320830018);
		}
		return $stringWithoutHmac;
	}

	/**
	 * Generates a salted md5 hash over the given string.
	 *
	 * @param string $clearString The unencrypted string which is the subject to be hashed
	 * @return string Salted hash and the salt, separated by a comma ","
	 * @deprecated Use hashPassword(...) instead
	 */
	public function generateSaltedMd5($clearString) {
		return \TYPO3\FLOW3\Security\Cryptography\SaltedMd5HashingStrategy::generateSaltedMd5($clearString);
	}

	/**
	 * Tests if the given string would produce the same hash given the specified salt.
	 * Use this method to validate hashes generated with generateSlatedMd5().
	 *
	 * @param string $clearString
	 * @param string $hashedStringAndSalt
	 * @return boolean TRUE if the clear string matches, otherwise FALSE
	 * @deprecated Use validatePassword(...) instead
	 */
	public function validateSaltedMd5($clearString, $hashedStringAndSalt) {
		return \TYPO3\FLOW3\Security\Cryptography\SaltedMd5HashingStrategy::validateSaltedMd5($clearString, $hashedStringAndSalt);
	}

	/**
	 * Hash a password using the configured password hashing strategy
	 *
	 * @param string $password The cleartext password
	 * @return string A hashed password with salt (if used)
	 * @api
	 */
	public function hashPassword($password) {
		return $this->passwordHashingStrategy->hashPassword($password, $this->encryptionKey);
	}

	/**
	 * Validate a hashed password using the configured password hashing strategy
	 *
	 * @param string $password The cleartext password
	 * @param string $hashedPasswordAndSalt The hashed password with salt (if used)
	 * @return boolean TRUE if the given password matches the hashed password
	 * @api
	 */
	public function validatePassword($password, $hashedPasswordAndSalt) {
		return $this->passwordHashingStrategy->validatePassword($password, $hashedPasswordAndSalt, $this->encryptionKey);
	}

	/**
	 * Inject the password hashing strategy
	 *
	 * @param \TYPO3\FLOW3\Security\Cryptography\PasswordHashingStrategyInterface $passwordHashingStrategy
	 * @return void
	 */
	public function setPasswordHashingStrategy(\TYPO3\FLOW3\Security\Cryptography\PasswordHashingStrategyInterface $passwordHashingStrategy) {
		$this->passwordHashingStrategy = $passwordHashingStrategy;
	}

}
?>