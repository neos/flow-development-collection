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


/**
 * Validator for string length
 *
 * @api
 */
class StringLengthValidator extends AbstractValidator {

	/**
	 * Checks if the given $value is a valid string and its length is between 'minimum' (defaults to 0 if not specified)
	 * and 'maximum' (defaults to infinite if not specified) to be specified in the validation options.
	 * Note: a value of NULL or empty string ('') is considered valid
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException
	 * @api
	 */
	protected function isValid($value) {
		if (isset($this->options['minimum']) && isset($this->options['maximum'])
			&& $this->options['maximum'] < $this->options['minimum']) {
			throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('The \'maximum\' is less than the \'minimum\' in the StringLengthValidator.', 1238107096);
		}

		if (is_object($value)) {
			if (!method_exists($value, '__toString')) {
				$this->addError('The given object could not be converted to a string.', 1238110957);
				return;
			}
		} elseif (!is_string($value)) {
			$this->addError('The given value was not a valid string.', 1269883975);
			return;
		}

		$stringLength = strlen($value);
		$isValid = TRUE;
		if (isset($this->options['minimum']) && $stringLength < $this->options['minimum']) {
			$isValid = FALSE;
		}
		if (isset($this->options['maximum']) && $stringLength > $this->options['maximum']) {
			$isValid = FALSE;
		}

		if ($isValid === FALSE) {
			if (isset($this->options['minimum']) && isset($this->options['maximum'])) {
				$this->addError('The length of this text must be between %1$d and %2$d characters.', 1238108067, array($this->options['minimum'], $this->options['maximum']));
			} elseif (isset($this->options['minimum'])) {
				$this->addError('This field must contain at least %1$d characters.', 1238108068, array($this->options['minimum']));
			} else {
				$this->addError('This text may not exceed %1$d characters.', 1238108069, array($this->options['maximum']));
			}
		}
	}
}

?>