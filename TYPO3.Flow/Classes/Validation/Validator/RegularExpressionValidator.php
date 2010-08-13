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
 * Validator based on regular expressions
 *
 * The regular expression is specified in the options by using the array key "regularExpression"
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class RegularExpressionValidator extends \F3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($value) matches the given regular expression.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function isValid($value) {
		$this->errors = array();
		if (!isset($this->options['regularExpression'])) {
			$this->addError('The regular expression was empty.', 1221565132);
			return FALSE;
		}
		$result = preg_match($this->options['regularExpression'], $value);
		if ($result === 0) {
			$this->addError('The given subject did not match the pattern. Got: "' . $value . '"', 1221565130);
			return FALSE;
		}
		if ($result === FALSE) {
			$this->addError('The regular expression "' . $this->options['regularExpression'] . '" contained an error.', 1221565131);
			return FALSE;
		}
		return TRUE;
	}
}

?>