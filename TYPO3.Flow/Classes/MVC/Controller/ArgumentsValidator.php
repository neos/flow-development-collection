<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

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
 * A validator for controller arguments
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class ArgumentsValidator extends \F3\FLOW3\Validation\Validator\AbstractObjectValidator {

	/**
	 * Checks if the given value (ie. an Arguments object) is valid.
	 *
	 * If at least one error occurred, the result is FALSE and any errors can
	 * be retrieved through the getErrors() method.
	 *
	 * @param object $arguments The arguments object that should be validated
	 * @return boolean TRUE if all arguments are valid, FALSE if an error occured
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function isValid($arguments) {
		if (!$arguments instanceof \F3\FLOW3\MVC\Controller\Arguments) throw new \InvalidArgumentException('Expected \F3\FLOW3\MVC\Controller\Arguments, ' . gettype($arguments) . ' given.', 1241079561);
		$this->errors = array();

		$result = TRUE;
		foreach ($arguments->getArgumentNames() as $argumentName) {
			if ($this->isPropertyValid($arguments, $argumentName) === FALSE) {
				$result = FALSE;
			}
		}
		return $result;
	}

	/**
	 * Checks the given object can be validated by the validator implementation
	 *
	 * @param object $object The object to be checked
	 * @return boolean TRUE if this validator can validate instances of the given object or FALSE if it can't
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function canValidate($object) {
		return ($object instanceof \F3\FLOW3\MVC\Controller\Arguments);
	}

	/**
	 * Checks if the specified property (ie. the argument) of the given arguments
	 * object is valid. Validity is checked by first invoking the validation conjunction
	 * defined in the argument object.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param object $arguments The arguments object containing the property (argument) to validate
	 * @param string $argumentName Name of the property (ie. name of the argument) to validate
	 * @return boolean TRUE if the argument is valid, FALSE if an error occured
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function isPropertyValid($arguments, $argumentName) {
		if (!$arguments instanceof \F3\FLOW3\MVC\Controller\Arguments) throw new \InvalidArgumentException('Expected \F3\FLOW3\MVC\Controller\Arguments, ' . gettype($arguments) . ' given.', 1241079562);
		$argument = $arguments[$argumentName];

		$validatorConjunction = $argument->getValidator();
		if ($validatorConjunction === NULL) return TRUE;

		$argumentValue = $argument->getValue();
		if ($argumentValue === $argument->getDefaultValue() && $argument->isRequired() === FALSE) return TRUE;

		if ($validatorConjunction->isValid($argumentValue) === FALSE) {
			$this->addErrorsForArgument($validatorConjunction->getErrors(), $argumentName);
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Adds the given errors to $this->errors and creates an ArgumentError
	 * instance if needed.
	 *
	 * @param array $errors Array of \F3\FLOW3\Validation\Error
	 * @param string $argumentName Name of the argument to add errors for
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function addErrorsForArgument(array $errors, $argumentName) {
		if (!isset($this->errors[$argumentName])) {
			$this->errors[$argumentName] = $this->objectManager->create('F3\FLOW3\MVC\Controller\ArgumentError', $argumentName);
		}
		$this->errors[$argumentName]->addErrors($errors);
	}
}
?>