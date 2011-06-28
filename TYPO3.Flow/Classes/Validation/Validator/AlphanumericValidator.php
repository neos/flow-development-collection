<?php
namespace TYPO3\FLOW3\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Validator for alphanumeric strings
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope singleton
 */
class AlphanumericValidator extends \TYPO3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($propertyValue) is a valid
	 * alphanumeric string, which is defined as [a-zA-Z0-9]*.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @throws \TYPO3\FLOW3\Validation\Exception\InvalidSubjectException if this validator cannot validate the given value
	 * @api
	 */
	protected function isValid($value) {
		if (is_string($value) && preg_match('/^[a-z0-9]*$/i', $value)) return;
		$this->addError('Only the characters a to z and numbers are allowed.', 1221551320);
	}
}

?>