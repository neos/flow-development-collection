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

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Validator for alphanumeric strings
 *
 * @api
 * @FLOW3\Scope("singleton")
 */
class AlphanumericValidator extends \TYPO3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * The given $value is valid if it is an alphanumeric string, which is defined as [a-zA-Z0-9]
	 * Note: a value of NULL or empty string ('') is considered valid
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($value) {
		if (!is_string($value) || preg_match('/^[a-z0-9]*$/i', $value) !== 1) {
			$this->addError('Only the characters a to z and numbers are allowed.', 1221551320);
		}
	}
}

?>