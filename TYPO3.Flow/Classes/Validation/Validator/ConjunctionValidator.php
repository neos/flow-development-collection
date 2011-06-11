<?php
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
 * Validator to chain many validators in a conjunction (logical and). So every
 * validator has to be valid, to make the whole conjunction valid.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class ConjunctionValidator extends \F3\FLOW3\Validation\Validator\AbstractCompositeValidator {

	/**
	 * Checks if the given value is valid according to the validators of the conjunction.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \F3\FLOW3\Error\Result
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function validate($value) {
		$result = new \F3\FLOW3\Error\Result();
		foreach ($this->validators as $validator) {
			$result->merge($validator->validate($value));
		}
		return $result;
	}
}

?>