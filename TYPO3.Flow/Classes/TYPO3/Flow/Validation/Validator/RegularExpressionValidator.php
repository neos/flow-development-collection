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
 * Validator based on regular expressions.
 *
 * @api
 */
class RegularExpressionValidator extends AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'regularExpression' => array('', 'The regular expression to use for validation, used as given', 'string', TRUE)
	);

	/**
	 * Checks if the given value matches the specified regular expression.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException
	 * @api
	 */
	protected function isValid($value) {
		$result = preg_match($this->options['regularExpression'], $value);
		if ($result === 0) {
			$this->addError('The given subject did not match the pattern. Got: %1$s', 1221565130, array($value));
		}
		if ($result === FALSE) {
			throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('regularExpression "' . $this->options['regularExpression'] . '" in RegularExpressionValidator contained an error.', 1298273089);
		}
	}
}
