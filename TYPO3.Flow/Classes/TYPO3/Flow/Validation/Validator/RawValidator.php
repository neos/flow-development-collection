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
 * A validator which accepts any input
 *
 * @api
 * @Flow\Scope("singleton")
 */
class RawValidator extends AbstractValidator {

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