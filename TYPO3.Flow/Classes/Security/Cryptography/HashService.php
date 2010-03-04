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
 * @version $Id$
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
	public function injectSettings(array $settings) {
		if (empty($settings['security']['cryptography']['hashService']['encryptionKey'])) {
			throw new \F3\FLOW3\Security\Exception\MissingConfigurationException('You must configure an encryption key for the HashService', 1258991855);
		}
		$this->encryptionKey = $settings['security']['cryptography']['hashService']['encryptionKey'];
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
	 * Test if a string $string matches the HMAC given by $hash.
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
}
?>