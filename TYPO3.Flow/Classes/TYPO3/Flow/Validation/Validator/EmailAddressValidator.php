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
 * Validator for email addresses
 *
 * @api
 * @FLOW3\Scope("singleton")
 */
class EmailAddressValidator extends AbstractValidator {

	/**
	 * Checks if the given value is a valid email address.
	 *
	 * Note: a value of NULL or empty string ('') is considered valid
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($value) {
		if (!is_string($value) || !$this->validEmail($value)) {
			$this->addError('Please specify a valid email address.', 1221559976);
		}
	}

	/**
	 * Checking syntax of input email address
	 *
	 * @param string $emailAddress Input string to evaluate
	 * @return boolean Returns TRUE if the $email address (input string) is valid
	 */
	protected function validEmail($emailAddress) {
			// Enforce maximum length to prevent libpcre recursion crash bug #52929 in PHP
			// fixed in PHP 5.3.4; length restriction per SMTP RFC 2821
		if (strlen($emailAddress) > 320) {
			return FALSE;
		}

		return (filter_var($emailAddress, FILTER_VALIDATE_EMAIL) !== FALSE);
	}
}

?>