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
 * Validator based on regular expressions
 *
 * The regular expression is specified in the options by using the array key "regularExpression"
 *
 * @api
 * @FLOW3\Scope("prototype")
 */
class RegularExpressionValidator extends \TYPO3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($value) matches the given regular expression.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	protected function isValid($value) {
		if (!isset($this->options['regularExpression'])) {
			throw new \TYPO3\FLOW3\Validation\Exception\InvalidValidationOptionsException('"regularExpression" in RegularExpressionValidator was empty.', 1298273029);
		}
		$result = preg_match($this->options['regularExpression'], $value);
		if ($result === 0) {
			$this->addError('The given subject did not match the pattern. Got: %1$s', 1221565130, array($value));
		}
		if ($result === FALSE) {
			throw new \TYPO3\FLOW3\Validation\Exception\InvalidValidationOptionsException('regularExpression "' . $this->options['regularExpression'] . '" in RegularExpressionValidator contained an error.', 1298273089);
		}
	}
}

?>