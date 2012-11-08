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
 * Validator for general numbers
 *
 * @api
 */
class NumberRangeValidator extends AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'minimum' => array(0, 'The minimum value to accept', 'integer'),
		'maximum' => array(PHP_INT_MAX, 'The maximum value to accept', 'integer')
	);

	/**
	 * The given value is valid if it is a number in the specified range.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($value) {
		if (!is_numeric($value)) {
			$this->addError('A valid number is expected.', 1221563685);
			return;
		}

		$minimum = $this->options['minimum'];
		$maximum = $this->options['maximum'];
		if ($minimum > $maximum) {
			$x = $minimum;
			$minimum = $maximum;
			$maximum = $x;
		}
		if ($value < $minimum || $value > $maximum) {
			$this->addError('Please enter a valid number between %1$d and %2$d.', 1221561046, array($minimum, $maximum));
		}
	}
}

?>