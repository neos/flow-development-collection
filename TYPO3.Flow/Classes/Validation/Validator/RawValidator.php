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
class RawValidator extends \TYPO3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * This validator is always valid
	 *
	 * @param mixed $value The value that should be validated (not used here)
	 * @return void
	 * @api
	 */
	public function isValid($value) {
	}
}
?>