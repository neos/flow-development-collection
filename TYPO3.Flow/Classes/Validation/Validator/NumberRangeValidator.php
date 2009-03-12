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
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 */

/**
 * Validator for general numbers
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NumberRangeValidator extends \F3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($propertyValue) is a valid number in the given range.
	 *
	 * If at least one error occurred, the result is FALSE and any errors will
	 * be stored in the given errors object.
	 *
	 * @param mixed $value The value that should be validated
	 * @param \F3\FLOW3\Validation\Errors $errors An Errors object which will contain any errors which occurred during validation
	 * @param array $validationOptions An array specifying the startRange and endRange
	 * @return boolean TRUE if the value is within the range, otherwise FALSE
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValid($value, \F3\FLOW3\Validation\Errors $errors, array $validationOptions = array()) {
		if (!is_numeric($value)) {
			$errors->append($this->objectFactory->create('F3\FLOW3\Validation\Error', 'The given subject was not a valid number. Got: "' . $value . '"', 1221563685));
			return FALSE;
		}

		$startRange = (isset($validationOptions['startRange'])) ? intval($validationOptions['startRange']) : 0;
		$endRange = (isset($validationOptions['endRange'])) ? intval($validationOptions['endRange']) : PHP_INT_MAX;
		if ($startRange > $endRange) {
			$x = $startRange;
			$startRange = $endRange;
			$endRange = $x;
		}
		if ($value >= $startRange && $value <= $endRange) return TRUE;

		$errors->append($this->objectFactory->create('F3\FLOW3\Validation\Error', 'The given subject was not in the valid range (' . $startRange . ', ' . $endRange . '). Got: "' . $value . '"', 1221561046));
		return FALSE;
	}
}

?>