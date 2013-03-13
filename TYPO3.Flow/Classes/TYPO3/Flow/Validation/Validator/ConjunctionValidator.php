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
 * Validator to chain many validators in a conjunction (logical and).
 *
 * @api
 */
class ConjunctionValidator extends AbstractCompositeValidator {

	/**
	 * Checks if the given value is valid according to the validators of the conjunction.
	 * Every validator has to be valid, to make the whole conjunction valid.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\Flow\Error\Result
	 * @api
	 */
	public function validate($value) {
		$validators = $this->getValidators();
		if ($validators->count() > 0) {
			$result = NULL;
			foreach ($validators as $validator) {
				if ($result === NULL) {
					$result = $validator->validate($value);
				} else {
					$result->merge($validator->validate($value));
				}
			}
		} else {
			$result = new \TYPO3\Flow\Error\Result();
		}

		return $result;
	}
}

?>