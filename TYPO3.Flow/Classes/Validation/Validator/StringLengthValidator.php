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
 * Validator for string length
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class StringLengthValidator extends \F3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($value) is a valid string and its length
	 * is between 'minimum' (defaults to 0 if not specified) and 'maximum' (defaults to infinite if not specified)
	 * to be specified in the validation options.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 * @throws F3\FLOW3\Validation\Exception\InvalidSubjectException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isValid($value) {
		$this->errors = array();
		if (isset($this->options['minimum']) && isset($this->options['maximum'])
			&& $this->options['maximum'] < $this->options['minimum']) {
			throw new \F3\FLOW3\Validation\Exception\InvalidValidationOptionsException('The \'maximum\' is shorter than the \'minimum\' in the StringLengthValidator.', 1238107096);
		}

		if (is_object($value)) {
			if (!method_exists($value, '__toString')) {
				throw new \F3\FLOW3\Validation\Exception\InvalidSubjectException('The given object could not be converted to a string.', 1238110957);
			}
		} elseif (!is_string($value)) {
			$this->addError('The given value was not a valid string.', 1269883975);
			return FALSE;
		}

		$stringLength = strlen($value);
		$isValid = TRUE;
		if (isset($this->options['minimum']) && $stringLength < $this->options['minimum']) $isValid = FALSE;
		if (isset($this->options['maximum']) && $stringLength > $this->options['maximum']) $isValid = FALSE;

		if ($isValid === FALSE) {
			if (isset($this->options['minimum']) && isset($this->options['maximum'])) {
				$this->addError('The length of this text must be between ' . $this->options['minimum'] . ' and ' . $this->options['maximum'] . ' characters.', 1238108067);
			} elseif (isset($this->options['minimum'])) {
				$this->addError('This field must contain at least ' . $this->options['minimum'] . ' characters.', 1238108068);
			} else {
				$this->addError('This text may not exceed ' . $this->options['maximum'] . ' characters.', 1238108069);
			}
		}

		return $isValid;
	}
}

?>