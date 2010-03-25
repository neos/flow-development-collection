<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Validator;

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
 * Validator for countable things
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class CountValidator extends \F3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($propertyValue) has a valid count in the given range.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @param \F3\FLOW3\Validation\Errors $errors An Errors object which will contain any errors which occurred during validation
	 * @return boolean TRUE if the value is within the range, otherwise FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function isValid($value) {
		$this->errors = array();
		if (!is_array($value) && !($value instanceof \Countable)) {
			$this->addError('The given subject was not countable.', 1253718666);
			return FALSE;
		}

		$min = (isset($this->options['minimum'])) ? intval($this->options['minimum']) : 0;
		$max = (isset($this->options['maximum'])) ? intval($this->options['maximum']) : PHP_INT_MAX;
		if (count($value) >= $min && count($value) <= $max) return TRUE;

		$this->addError('The count must be between ' . $min . ' and ' . $max . '.', 1253718831);
		return FALSE;
	}
}

?>