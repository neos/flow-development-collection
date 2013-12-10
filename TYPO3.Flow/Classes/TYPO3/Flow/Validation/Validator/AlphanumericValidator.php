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
 * Validator for alphanumeric strings.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class AlphanumericValidator extends AbstractValidator {

	/**
	 * The given $value is valid if it is an alphanumeric string, which is defined as [[:alnum:]].
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($value) {
		if (!is_string($value) || preg_match('/^[[:alnum:]]*$/u', $value) !== 1) {
			$this->addError('Only regular characters (a to z, umlauts, ...) and numbers are allowed.', 1221551320);
		}
	}
}
