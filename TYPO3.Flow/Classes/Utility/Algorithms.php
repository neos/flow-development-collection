<?php
namespace TYPO3\FLOW3\Utility;

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
 * A utility class for various algorithms.
 *
 * @FLOW3\Scope("singleton")
 */
class Algorithms {

	/**
	 * Generates a universally unique identifier (UUID) according to RFC 4122.
	 * The algorithm used here, might not be completely random.
	 *
	 * @return string The universally unique id
	 * @todo check for randomness, optionally generate type 1 and type 5 UUIDs, use php5-uuid extension if available
	 */
	static public function generateUUID() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
	}

	/**
	 * Returns a string of random bytes.
	 *
	 * @param integer $count Number of bytes to generate
	 * @return string
	 */
	static public function generateRandomBytes($count) {
		$bytes = '';

		if (file_exists('/dev/urandom')) {
			$bytes = file_get_contents('/dev/urandom', NULL, NULL, NULL, $count);
		}

			// urandom did not deliver (enough) data
		if (strlen($bytes) < $count) {
			$randomState = microtime() . getmypid();
			while (strlen($bytes) < $count) {
				$randomState = md5(microtime() . mt_rand() . $randomState);
				$bytes .= md5(mt_rand() . $randomState, TRUE);
			}
			$bytes = substr($bytes, -$count, $count);
		}
		return $bytes;
	}

}
?>