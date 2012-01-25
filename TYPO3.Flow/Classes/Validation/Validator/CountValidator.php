<?php
namespace TYPO3\FLOW3\Validation\Validator;

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
 * Validator for countable things
 *
 * @api
 */
class CountValidator extends \TYPO3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * The given $value is valid if it is an array or \Countable that contains the specified amount of elements.
	 * Note: a value of NULL or empty string ('') is considered valid
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($value) {
		if (!is_array($value) && !($value instanceof \Countable)) {
			$this->addError('The given subject was not countable.', 1253718666);
			return;
		}

		$minimum = (isset($this->options['minimum'])) ? intval($this->options['minimum']) : 0;
		$maximum = (isset($this->options['maximum'])) ? intval($this->options['maximum']) : PHP_INT_MAX;
		if (count($value) < $minimum || count($value) > $maximum) {
			$this->addError('The count must be between %1$d and %2$d.', 1253718831, array($minimum, $maximum));
		}
	}
}

?>