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
 * @version $Id: TextValidator.php 1990 2009-03-12 13:59:17Z robert $
 */

/**
 * Validator for string length
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id: TextValidator.php 1990 2009-03-12 13:59:17Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class StringLengthValidator extends \F3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($value) is a valid string and its length
	 * is between 'minLength' (defaults to 0 if not specified) and 'maxLength' (defaults to infinite if not specified)
	 * to be specified in the $validationOptions.
	 *
	 * If at least one error occurred, the result is FALSE and any errors will
	 * be stored in the given errors object.
	 *
	 * @param mixed $value The value that should be validated
	 * @param \F3\FLOW3\Validation\Errors $errors An Errors object which will contain any errors which occurred during validation
	 * @param array $validationOptions array with the keys 'minLength' and 'maxLength'
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 * @throws F3\FLOW3\Validation\Exception\InvalidValidationOptions
	 * @throws F3\FLOW3\Validation\Exception\InvalidSubject
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isValid($value, \F3\FLOW3\Validation\Errors $errors, array $validationOptions = array()) {
		if (isset($validationOptions['minLength'])
			&& isset($validationOptions['maxLength'])
			&& $validationOptions['maxLength'] < $validationOptions['minLength']) {

			throw new \F3\FLOW3\Validation\Exception\InvalidValidationOptions('The \'maxLength\' is shorter than the \'minLength\' in the StringLengthValidator.', 1238107096);
		}

		if (is_object($value) && !method_exists($value, '__toString')) throw new \F3\FLOW3\Validation\Exception\InvalidSubject('The given object could not be converted to a string.', 1238110957);

		$stringLength = \F3\PHP6\Functions::strlen($value);

		$isValid = TRUE;
		if (isset($validationOptions['minLength']) && $stringLength < $validationOptions['minLength']) $isValid = FALSE;
		if (isset($validationOptions['maxLength']) && $stringLength > $validationOptions['maxLength']) $isValid = FALSE;

		if ($isValid === FALSE) $errors->append($this->objectFactory->create('F3\FLOW3\Validation\Error', 'The length of the given string was not between minLength and maxLength.', 1238108067));

		return $isValid;
	}
}

?>