<?php
declare(encoding = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Validation
 * @version $Id: F3_FLOW3_Validation_Validator_Chain.php 688 2008-04-03 09:35:36Z andi $
 */

/**
 * Object validator to chain many object validators
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id: F3_FLOW3_Validation_Validator_Chain.php 688 2008-04-03 09:35:36Z andi $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Validation_Validator_ObjectValidatorChain implements F3_FLOW3_Validation_ObjectValidatorInterface {

	/*
	 * @var array
	 */
	protected $validators = array();


	/**
	 * Checks if classes of the given type can be validated with this
	 * validator chain. All chained validators have to be able to validate the given class.
	 *
	 * @param  string $className: Specifies the class type which is supposed to be validated. The check succeeds if this validator can handle the specified class or any subclass of it.
	 * @return boolean TRUE if this validator can validate the class type or FALSE if it can't
	 */
	public function canValidate($className) {
		$canValidate = TRUE;

		foreach ($this->validators as $validator) {
			$canValidate &= $validator->canValidate($className);
		}

		return (boolean)$canValidate;
	}

	/**
	 * Validates the given object. Any errors will be stored in the passed errors
	 * object. If validation succeeds completely, this method returns TRUE. If at
	 * least one error occurred, the result is FALSE.
	 *
	 * @param  object $object: The object which is supposed to be validated.
	 * @param  F3_FLOW3_Validation_Errors $errors: Here any occured validation error is stored
	 * @return boolean TRUE if validation succeeded completely, FALSE if at least one error occurred.
	 */
	public function validate($object, F3_FLOW3_Validation_Errors &$errors) {
		$objectIsValid = TRUE;

		foreach ($this->validators as $validator) {
			$objectIsValid &= $validator->validate($object, $errors);
		}

		return (boolean)$objectIsValid;
	}

	/**
	 * Validates a specific property ($propertyName) of the given object. Any errors will be stored
	 * in the given errors object. If validation succeeds, this method returns TRUE, else it will return FALSE.
	 *
	 * @param  object $object: The object of which the property should be validated
	 * @param  string $propertyName: The name of the property that should be validated
	 * @param  F3_FLOW3_Validation_Errors $errors: Here any occured validation error is stored
	 * @return boolean TRUE if the property could be validated, FALSE if an error occured
	 */
	public function validateProperty($object, $propertyName, F3_FLOW3_Validation_Errors &$errors) {
		$propertyIsValid = TRUE;

		foreach ($this->validators as $validator) {
			$propertyIsValid &= $validator->validateProperty($object, $propertyName, $errors);
		}

		return (boolean)$propertyIsValid;
	}

	/**
	 * Returns TRUE, if the given propterty ($proptertyValue) is a valid value for the property ($propertyName) of the class ($className).
	 * Any errors will be stored in the given errors object. If at least one error occurred, the result is FALSE.
	 *
	 * @param  string $className: The propterty's class name
	 * @param  string $propertyName: The name of the property for wich the value should be validated
	 * @param  object $propertyValue: The value that should be validated
	 * @return boolean TRUE if the value could be validated for the given property, FALSE if an error occured
	 */
	public function isValidProperty($className, $propertyName, $propertyValue, F3_FLOW3_Validation_Errors &$errors) {
		$propertyIsValid = TRUE;

		foreach ($this->validators as $validator) {
			$propertyIsValid &= $validator->isValidProperty($className, $propertyName, $propertyValue, $errors);
		}

		return (boolean)$propertyIsValid;
	}

	/**
	 * Adds a new validator to the chain. Returns the index of the chain entry.
	 *
	 * @param F3_FLOW3_Validation_ValidatorInterface The validator that should be added
	 * @return integer The index of the new chain entry
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addValidator(F3_FLOW3_Validation_ObjectValidatorInterface $validator) {
		$this->validators[] = $validator;
		return count($this->validators) - 1;
	}

	/**
	 * Returns the validator with the given index of the chain.
	 *
	 * @param  integer The index of the validator that should be returned
	 * @return F3_FLOW3_Validation_ValidatorInterface The requested validator
	 * @throws F3_FLOW3_Validation_Exception_InvalidChainIndex
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValidator($index) {
		if(!isset($this->validators[$index])) throw new F3_FLOW3_Validation_Exception_InvalidChainIndex(1207215864);
		return $this->validators[$index];
	}

	/**
	 * Removes the validator with the given index of the chain.
	 *
	 * @param integer The index of thevalidator that should be removed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removeValidator($index) {
		if(!isset($this->validators[$index])) throw new F3_FLOW3_Validation_Exception_InvalidChainIndex(1207020177);
		unset($this->validators[$index]);
	}
}

?>
