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

/**
 * Hashing passwords using BCrypt
 */
class BCryptHashingStrategy implements \TYPO3\Flow\Security\Cryptography\PasswordHashingStrategyInterface {

	/**
	 * Number of rounds to use with BCrypt (04 - 31), must be two digit
	 * @var string
	 */
	protected $cost;

	/**
	 * Construct a PBKDF2 hashing strategy with the given parameters
	 *
	 * @param integer $cost
	 * @throws \InvalidArgumentException
	 */
	public function __construct($cost) {
		if ($cost < 4 || $cost > 31) {
			throw new \InvalidArgumentException('BCrypt cost must be between 04 and 31.', 1318447710);
		}

		$this->cost = sprintf('%02d', $cost);
	}

	/**
	 * Creates a BCrypt hash
	 *
	 * @param string $password   The plaintext password to hash
	 * @param string $staticSalt Optional static salt that will not be stored in the hashed password
	 * @return string the result of the crypt() call
	 */
	public function hashPassword($password, $staticSalt = NULL) {
		$dynamicSalt = \TYPO3\Flow\Utility\Algorithms::generateRandomString(22, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789./');
		return crypt($password, '$2a$' . $this->cost . '$' . $dynamicSalt);
	}

	/**
	 * Validate a password against a derived key (hashed password) and salt using BCrypt.
	 * Provides a fallback if the stored credentialsSource was created using PBKDF2.
	 *
	 * @param string $password The cleartext password
	 * @param string $hashedPasswordAndSalt The derived key and salt in as returned by crypt() for verification
	 * @param string $staticSalt Optional static salt that will be appended to the dynamic salt
	 * @return boolean TRUE if the given password matches the hashed password
	 */
	public function validatePassword($password, $hashedPasswordAndSalt, $staticSalt = NULL) {
		$cryptSalt = '$2a$' . $this->cost . '$' . substr($hashedPasswordAndSalt, 7, 29);
		return crypt($password, $cryptSalt) === $hashedPasswordAndSalt;
	}

}
?>