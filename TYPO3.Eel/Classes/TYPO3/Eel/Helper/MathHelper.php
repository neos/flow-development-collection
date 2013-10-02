<?php
namespace TYPO3\Eel\Helper;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * String helpers for Eel contexts
 */
class MathHelper implements ProtectedContextAwareInterface {

	/**
	 * Rounds the subject to the given precision
	 *
	 * The precision defines the number of digits after the decimal point.
	 * Negative values are also supported (-1 rounds to full 10ths).
	 *
	 * @param float $subject The value to round
	 * @param integer $precision The precision (digits after decimal point) to use, defaults to 0
	 * @return float The rounded value
	 */
	public function round($subject, $precision = 0) {
		if (!is_numeric($subject)) {
			throw new \TYPO3\TypoScript\Exception('Expected an integer or float passed, ' . gettype($subject) . ' given', 1381917394);
		}
		$subject = floatval($subject);
		if ($precision != NULL && !is_int($precision)) {
			throw new \TYPO3\TypoScript\Exception('Precision must be an integer', 1381917394);
		}
		return round($subject, $precision);
	}

	/**
	 * All methods are considered safe
	 *
	 * @param string $methodName
	 * @return boolean
	 */
	public function allowsCallOfMethod($methodName) {
		return TRUE;
	}

}
