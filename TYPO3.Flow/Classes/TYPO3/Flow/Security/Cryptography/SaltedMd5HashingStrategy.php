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
 * A salted MD5 based password hashing strategy
 *
 */
class SaltedMd5HashingStrategy implements \TYPO3\Flow\Security\Cryptography\PasswordHashingStrategyInterface {

	/**
	 * Generates a salted md5 hash over the given string.
	 *
	 * @param string $clearString The unencrypted string which is the subject to be hashed
	 * @return string Salted hash and the salt, separated by a comma ","
	 */
	static public function generateSaltedMd5($clearString) {
		$salt = substr(md5(uniqid(rand(), TRUE)), 0, rand(6, 10));
		return (md5(md5($clearString) . $salt) . ',' . $salt);
	}

	/**
	 * Tests if the given string would produce the same hash given the specified salt.
	 * Use this method to validate hashes generated with generateSlatedMd5().
	 *
	 * @param string $clearString
	 * @param string $hashedStringAndSalt
	 * @return boolean TRUE if the clear string matches, otherwise FALSE
	 * @throws \InvalidArgumentException
	 */
	static public function validateSaltedMd5($clearString, $hashedStringAndSalt) {
		if (strpos($hashedStringAndSalt, ',') === FALSE) {
			throw new \InvalidArgumentException('The hashed string must contain a salt, separated with comma from the hashed.', 1269872776);
		}
		list($passwordHash, $salt) = explode(',', $hashedStringAndSalt);
		return (md5(md5($clearString) . $salt) === $passwordHash);
	}

	/**
	 * Hash a password using salted MD5
	 *
	 * @param string $password The cleartext password
	 * @param string $staticSalt ignored parameter
	 * @return string A hashed password with salt
	 */
	public function hashPassword($password, $staticSalt = NULL) {
		return self::generateSaltedMd5($password);
	}

	/**
	 * Validate a hashed password using salted MD5
	 *
	 * @param string $password The cleartext password
	 * @param string $hashedPasswordAndSalt The hashed password with salt
	 * @param string $staticSalt ignored parameter
	 * @return boolean TRUE if the given password matches the hashed password
	 */
	public function validatePassword($password, $hashedPasswordAndSalt, $staticSalt = NULL) {
		return self::validateSaltedMd5($password, $hashedPasswordAndSalt);
	}

}
?>