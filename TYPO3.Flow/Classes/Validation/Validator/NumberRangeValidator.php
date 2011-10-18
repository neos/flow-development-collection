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
 * Validator for general numbers
 *
 * @api
 */
class NumberRangeValidator extends \TYPO3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($propertyValue) is a valid number in the given range.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @param \TYPO3\FLOW3\Validation\Errors $errors An Errors object which will contain any errors which occurred during validation
	 * @return void
	 * @api
	 */
	protected function isValid($value) {
		if (!is_numeric($value)) {
			$this->addError('A valid number is expected.', 1221563685);
			return;
		}

		$minimum = (isset($this->options['minimum'])) ? intval($this->options['minimum']) : 0;
		$maximum = (isset($this->options['maximum'])) ? intval($this->options['maximum']) : PHP_INT_MAX;
		if ($minimum > $maximum) {
			$x = $minimum;
			$minimum = $maximum;
			$maximum = $x;
		}
		if ($value >= $minimum && $value <= $maximum) {
			return;
		}

		$this->addError('Please enter a valid number between %1$d and %2$d.', 1221561046, array($minimum, $maximum));
	}
}

?>