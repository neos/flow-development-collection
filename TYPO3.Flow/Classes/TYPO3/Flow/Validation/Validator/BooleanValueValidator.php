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
 * Validator for a specific boolean value.
 *
 * @api
 * @Flow\Scope("prototype")
 */
class BooleanValueValidator extends AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'expectedValue' => array(TRUE, 'The expected boolean value', 'boolean')
	);

	/**
	 * Checks if the given value is a specific boolean value.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($value) {
		if ($value !== $this->options['expectedValue']) {
			$this->addError('The given value is expected to be %1$s.', 1361044943, array($this->options['expectedValue'] ? 'TRUE' : 'FALSE'));
		}
	}
}