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
 * A validator for labels.
 *
 * Labels usually allow all kinds of letters, numbers, punctuation marks and
 * the space character. What you don't want in labels though are tabs, new
 * line characters or HTML tags. This validator is for such uses.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class LabelValidator extends \F3\FLOW3\Validation\Validator\AbstractValidator {

	const PATTERN_VALIDCHARACTERS = '/^[\p{L}\p{Sc} ,.:;?!%§&"\'\/+\-_=\(\)#0-9]*$/u';

	/**
	 * Returns TRUE, if the given property ($propertyValue) is a valid "label".
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isValid($value) {
		$this->errors = array();
		if (preg_match(self::PATTERN_VALIDCHARACTERS, $value) === 0) {
			$this->addError('Only letters, numbers, spaces and certain punctuation marks are expected.', 1272298003);
			return FALSE;
		}
		return TRUE;
	}
}

?>