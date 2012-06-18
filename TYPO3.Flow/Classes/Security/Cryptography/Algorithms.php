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

/**
 * Cryptographic algorithms
 *
 * Right now this class provides a PHP based PBKDF2 implementation.
 *
 */
class Algorithms {

	/**
	 * Compute a derived key from a password based on PBKDF2
	 *
	 * See PKCS #5 v2.0 http://tools.ietf.org/html/rfc2898 for implementation details.
	 * The implementation is tested with test vectors from http://tools.ietf.org/html/rfc6070 .
	 *
	 * If https://wiki.php.net/rfc/hash_pbkdf2 is ever part of PHP we should check for the
	 * existence of hash_pbkdf2() and use it if available.
	 *
	 * @param string $password Input string / password
	 * @param string $salt The salt
	 * @param integer $iterationCount Hash iteration count
	 * @param integer $derivedKeyLength Derived key length
	 * @param string $algorithm Hash algorithm to use, see hash_algos(), defaults to sha256
	 * @return string The computed derived key as raw binary data
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