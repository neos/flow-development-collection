<?php
declare(ENCODING = 'utf-8');
namespace TYPO3\FLOW3\Security\Cryptography;

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
 * Cryptographic algorithms
 *
 * Right now this class provides a PHP based PBKDF2 implementation.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class Algorithms {

	/**
	 * Compute a derived key from a password based on PBKDF2
	 *
	 * See PKCS #5 v2.0 http://tools.ietf.org/html/rfc2898 for implementation details.
	 * The implementation is tested with test vectors from http://tools.ietf.org/html/rfc6070 .
	 *
	 * @param string $password Input string / password
	 * @param string $salt The salt
	 * @param integer $iterationCount Hash iteration count
	 * @param integer $derivedKeyLength Derived key length
	 * @param string $algorithm Hash algorithm to use, see hash_algos(), defaults to sha256
	 * @return string The computed derived key as raw binary data
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	static public function pbkdf2($password, $salt, $iterationCount, $derivedKeyLength, $algorithm = 'sha256') {
		$hashLength = strlen(hash($algorithm, NULL, TRUE));
		$keyBlocksToCompute = ceil($derivedKeyLength / $hashLength);
		$derivedKey = '';

		for ($block = 1; $block <= $keyBlocksToCompute; $block++) {
			$iteratedBlock = hash_hmac($algorithm, $salt . pack('N', $block), $password, TRUE);

			for ($iteration = 1, $iteratedHash = $iteratedBlock; $iteration < $iterationCount; $iteration++) {
				$iteratedHash = hash_hmac($algorithm, $iteratedHash, $password, TRUE);
				$iteratedBlock ^= $iteratedHash;
			}

			$derivedKey .= $iteratedBlock;
		}

		return substr($derivedKey, 0, $derivedKeyLength);
	}

}
?>