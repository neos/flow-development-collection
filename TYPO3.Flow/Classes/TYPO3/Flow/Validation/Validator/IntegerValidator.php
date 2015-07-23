<?php
namespace TYPO3\Flow\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Validator for integers.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class IntegerValidator extends AbstractValidator {

	/**
	 * Checks if the given value is a valid integer.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($value) {
		if (filter_var($value, FILTER_VALIDATE_INT) === FALSE) {
			$this->addError('A valid integer number is expected.', 1221560494);
		}
	}
}
