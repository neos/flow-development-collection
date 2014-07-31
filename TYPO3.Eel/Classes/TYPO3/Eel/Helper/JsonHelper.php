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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Eel\ProtectedContextAwareInterface;


/**
 * JSON helpers for Eel contexts
 *
 * @Flow\Proxy(false)
 */
class JsonHelper implements ProtectedContextAwareInterface {

	/**
	 * JSON encode the given value
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function stringify($value) {
		return json_encode($value);
	}

	/**
	 * JSON decode the given string
	 *
	 * @param string $json
	 * @param boolean $associativeArrays
	 * @return mixed
	 */
	public function parse($json, $associativeArrays = TRUE) {
		return json_decode($json, $associativeArrays);
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
