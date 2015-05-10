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
 * Type helper for Eel contexts
 *
 * @Flow\Proxy(false)
 */
class TypeHelper implements ProtectedContextAwareInterface {

	/**
	 * Get the variable type
	 *
	 * @param mixed $variable
	 * @return string
	 */
	public function typeof($variable) {
		return gettype($variable);
	}

	/**
	 * Get the variable type
	 *
	 * @param mixed $variable
	 * @return string
	 * @see typeof()
	 */
	public function getType($variable) {
		return $this->typeof($variable);
	}

	/**
	 * Get the class name of the given variable or NULL if it wasn't an object
	 *
	 * @param object $variable
	 * @return string|NULL
	 */
	public function className($variable) {
		if (!is_object($variable)) {
			return NULL;
		}

		return get_class($variable);
	}

	/**
	 * Is the given variable an array.
	 *
	 * @param mixed $variable
	 * @return boolean
	 */
	public function isArray($variable) {
		return is_array($variable);
	}

	/**
	 * Is the given variable a string.
	 *
	 * @param mixed $variable
	 * @return boolean
	 */
	public function isString($variable) {
		return is_string($variable);
	}

	/**
	 * Is the given variable numeric.
	 *
	 * @param mixed $variable
	 * @return boolean
	 */
	public function isNumeric($variable) {
		return is_numeric($variable);
	}

	/**
	 * Is the given variable an integer.
	 *
	 * @param mixed $variable
	 * @return boolean
	 */
	public function isInteger($variable) {
		return is_int($variable);
	}

	/**
	 * Is the given variable a float.
	 *
	 * @param mixed $variable
	 * @return boolean
	 */
	public function isFloat($variable) {
		return is_float($variable);
	}

	/**
	 * Is the given variable a scalar.
	 *
	 * @param mixed $variable
	 * @return boolean
	 */
	public function isScalar($variable) {
		return is_scalar($variable);
	}

	/**
	 * Is the given variable boolean.
	 *
	 * @param mixed $variable
	 * @return boolean
	 */
	public function isBoolean($variable) {
		return is_bool($variable);
	}

	/**
	 * Is the given variable an object.
	 *
	 * @param mixed $variable
	 * @return boolean
	 */
	public function isObject($variable) {
		return is_object($variable);
	}

	/**
	 * Is the given variable of the provided object type.
	 *
	 * @param mixed $variable
	 * @param string $expectedObjectType
	 * @return boolean
	 */
	public function instance($variable, $expectedObjectType) {
		return ($variable instanceof $expectedObjectType);
	}

	/**
	 * @param string $methodName
	 * @return boolean
	 */
	public function allowsCallOfMethod($methodName) {
		return TRUE;
	}


}