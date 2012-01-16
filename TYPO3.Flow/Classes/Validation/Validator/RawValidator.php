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
 * A validator which accepts any input
 *
 * @api
 * @FLOW3\Scope("singleton")
 */
class RawValidator implements \TYPO3\FLOW3\Validation\Validator\ValidatorInterface {

	/**
	 * Always returns an empty result.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\FLOW3\Error\Result
	 * @api
	 */
	public function validate($value) {
		return new \TYPO3\FLOW3\Error\Result();
	}

	/**
	 * Returns the options of this validator (not used for this validator)
	 *
	 * @return array
	 */
	public function getOptions() {
		return array();
	}
}
?>