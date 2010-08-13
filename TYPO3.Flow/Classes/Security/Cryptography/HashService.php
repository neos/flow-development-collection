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
 * A hash service which should be used to generate and validate hashes.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class HashService {

	/**
	 * A private, unique key used for encryption tasks.
	 * @var string
	 */
	protected $encryptionKey;

	/**
	 * @param array $settings
	 * @return void
	 * @author Karsten Dambekalns <karsten@dambekalns.de>
	 */
	public function __construct() {
		if (!file_exists(FLOW3_PATH_DATA . 'Persistent/EncryptionKey')) {
			file_put_contents(FLOW3_PATH_DATA . 'Persistent/EncryptionKey', bin2hex(\F3\FLOW3\Utility\Algorithms::generateRandomBytes(96)));
		}
		$this->encryptionKey = file_get_contents(FLOW3_PATH_DATA . 'Persistent/EncryptionKey');
		
		if (empty($this->encryptionKey)) {
			throw new \F3\FLOW3\Security\Exception\MissingConfigurationException('No encryption key for the HashService was found and none could be created at "' . FLOW3_PATH_DATA . 'Persistent/EncryptionKey"', 1258991855);
		}
	}

	/**
	 * Generate a hash (HMAC) for a given string
	 *
	 * @param string $string The string for which a hash should be generated
	 * @return string The hash of the string
	 * @throws F3\FLOW3\Security\Exception\InvalidArgumentForHashGenerationException if something else than a string was given as parameter
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo Mark as API once it is more stable
	 */
	public function generateHmac($string) {
		if (!is_string($string)) throw new \F3\FLOW3\Security\Exception\InvalidArgumentForHashGenerationException('A hash can only be generated for a string, but "' . gettype($string) . '" was given.', 1255069587);

		return hash_hmac('sha1', $string, $this->encryptionKey);
	}

	/**
	 * Tests if a string $string matches the HMAC given by $hash.
	 *
	 * @param string $string The string which should be validated
	 * @param string $hmac The hash of the string
	 * @return boolean TRUE if string and hash fit together, FALSE otherwise.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo Mark as API once it is more stable
	 */
	public function validateHmac($string, $hmac) {
		return ($this->generateHmac($string) === $hmac);
	}

	/**
	 * Generates a salted md5 hash over the given string.
	 *
	 * @param string $clearString The unencrypted string which is the subject to be hashed
	 * @return string Salted hash and the salt, separated by a comma ","
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function generateSaltedMd5($clearString) {
		$salt = substr(md5(uniqid(rand(), TRUE)), 0, rand(6, 10));
		return (md5(md5($clearString) . $salt) . ',' . $salt);
	}

	/**
	 * Tests if the given string would produce the same hash given the specified salt.
	 * Use this method to validate hashes generated with generateSlatedMd5().
	 *
	 * @return boolean TRUE if the clear string matches, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function validateSaltedMd5($clearString, $hashedStringAndSalt) {
		if (strpos($hashedStringAndSalt, ',') === FALSE) {
			throw new \InvalidArgumentException('The hashed string must contain a salt, separated with comma from the hashed.', 1269872776);
		}
		list($passwordHash, $salt) = explode(',', $hashedStringAndSalt);
		return (md5(md5($clearString) . $salt) === $passwordHash);
	}
}
?>