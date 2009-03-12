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
 * Object validator to chain many object validators
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class ObjectValidatorChainValidator implements \F3\FLOW3\Validation\Validator\ObjectValidatorInterface {

	/**
	 * @var array
	 */
	protected $validators = array();

	/**
	 * Checks if classes of the given type can be validated with this validator chain.
	 * All chained validators have to be able to validate the given class.
	 *
	 * @param string $className Name of the class which should be validated.
	 * @return boolean TRUE if this validator chain can validate instances of the given class or FALSE if it can't
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canValidateType($className) {
		foreach ($this->validators as $validator) {
			if ($validator->canValidateType($className) !== TRUE) return FALSE;
		}
		return FALSE;
	}

	/**
	 * Validates the given object. Any errors will be stored in the passed errors
	 * object. If validation succeeds completely, this method returns TRUE. If at
	 * least one error occurred, the result is FALSE.
	 *
	 * If at least one error occurred, the result is FALSE and any errors will
	 * be stored in the given errors object.
	 *
	 * @param mixed $value The object that should be validated
	 * @param \F3\FLOW3\Validation\Errors $errors An Errors object which will contain any errors which occurred during validation
	 * @param array $validationOptions Not used
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isValid($object, \F3\FLOW3\Validation\Errors $errors, array $validationOptions = array()) {
		$objectIsValid = TRUE;
		foreach ($this->validators as $validator) {
			$objectIsValid &= $validator->isValid($object, $errors);
		}
		return (boolean)$objectIsValid;
	}

	/**
	 * Checks if the specified property of the given object is valid.
	 *
	 * If at least one error occurred, the result is FALSE and any errors will
	 * be stored in the given errors object.
	 *
	 * Depending on the validator implementation, additional options may be passed
	 * in an array.
	 *
	 * @param object $object The object containing the property to validate
	 * @param string $propertyName Name of the property to validate
	 * @param \F3\FLOW3\Validation\Errors $errors An Errors object which will contain any errors which occurred during validation
	 * @param array $validationOptions An optional array of further options, specific to the validator implementation
	 * @return boolean TRUE if the property value is valid, FALSE if an error occured
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasValidProperty($object, $propertyName, \F3\FLOW3\Validation\Errors $errors,  array $validationOptions = array()) {
		$propertyIsValid = TRUE;
		foreach ($this->validators as $validator) {
			$propertyIsValid &= $validator->hasValidProperty($object, $propertyName, $errors);
		}
		return (boolean)$propertyIsValid;
	}

	/**
	 * Checks if the given value would be valid as the specified property of the given class.
	 *
	 * If at least one error occurred, the result is FALSE and any errors will
	 * be stored in the given errors object.
	 *
	 * Depending on the validator implementation, additional options may be passed
	 * in an array.
	 *
	 * @param string $className Name of the class which would contain the property
	 * @param string $propertyName Name of the property
	 * @param string $propertyValue The value to validate as a potential property of the given class
	 * @param \F3\FLOW3\Validation\Errors $errors An Errors object which will contain any errors which occurred during validation
	 * @param array $validationOptions An optional array of further options, specific to the validator implementation
	 * @return boolean TRUE if the property value is valid, FALSE if an error occured
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValidProperty($className, $propertyName, $propertyValue, \F3\FLOW3\Validation\Errors $errors, array $validationOptions = array()) {
		$propertyIsValid = TRUE;
		foreach ($this->validators as $validator) {
			$propertyIsValid &= $validator->isValidProperty($className, $propertyName, $propertyValue, $errors);
		}
		return (boolean)$propertyIsValid;
	}

	/**
	 * Adds a new validator to the chain. Returns the index of the chain entry.
	 *
	 * @param \F3\FLOW3\Validation\Validator\ValidatorInterface $validator The validator that should be added
	 * @return integer The index of the new chain entry
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addValidator(\F3\FLOW3\Validation\Validator\ObjectValidatorInterface $validator) {
		$this->validators[] = $validator;
		return count($this->validators) - 1;
	}

	/**
	 * Returns the validator with the given index of the chain.
	 *
	 * @param integer $index The index of the validator that should be returned
	 * @return \F3\FLOW3\Validation\Validator\ValidatorInterface The requested validator
	 * @throws \F3\FLOW3\Validation\Exception\InvalidChainIndex
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValidator($index) {
		if (!isset($this->validators[$index])) throw new \F3\FLOW3\Validation\Exception\InvalidChainIndex('Invalid chain index.', 1207215864);
		return $this->validators[$index];
	}

	/**
	 * Removes the validator with the given index of the chain.
	 *
	 * @param integer $index The index of thevalidator that should be removed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removeValidator($index) {
		if (!isset($this->validators[$index])) throw new \F3\FLOW3\Validation\Exception\InvalidChainIndex('Invalid chain index.', 1207020177);
		unset($this->validators[$index]);
	}
}

?>