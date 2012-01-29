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
 * A validator for locale identifiers.
 *
 * This validator validates a string based on the expressions of the
 * FLOW3 I18n implementation.
 *
 * @FLOW3\Scope("singleton")
 */
class LocaleIdentifierValidator extends \TYPO3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * Is valid if the given property ($propertyValue) is empty or a valid "locale identifier".
	 *
	 * Set error if pattern does not match
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 */
	protected function isValid($value) {
		if (!empty($value) && !preg_match(\TYPO3\FLOW3\I18n\Locale::PATTERN_MATCH_LOCALEIDENTIFIER, $value)) {
			$this->addError('Value is no valid I18n locale identifier.', 1327090892);
		}
	}
}

?>