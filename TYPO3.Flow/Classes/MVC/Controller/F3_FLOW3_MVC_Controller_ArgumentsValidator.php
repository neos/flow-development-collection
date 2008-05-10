<?php
declare(ENCODING = 'utf-8');

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
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Controller_ArgumentsValidator.php 467 2008-02-06 19:34:56Z robert $
 * @copyright Copyright belongs to the respective authors
 */

/**
 * Validator for the controller arguments object
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Controller_ArgumentsValidator.php 467 2008-02-06 19:34:56Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_MVC_Controller_ArgumentsValidator implements F3_FLOW3_Validation_ObjectValidatorInterface {
//TODO: call validators of the argument objects

	public $componentManger;

	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}

	/**
	 * Checks if classes of the given type can be validated with this
	 * validator.
	 *
	 * @param  string $className: Specifies the class type which is supposed to be validated. The check succeeds if this validator can handle the specified class or any subclass of it.
	 * @return boolean TRUE if this validator can validate the class type or FALSE if it can't
	 */
	public function canValidate($className) {
		return ($className === 'F3_FLOW3_MVC_Controller_Arguments');
	}

	/**
	 * Validates the given object. Any errors will be stored in the passed errors
	 * object. If validation succeeds completely, this method returns TRUE. If at
	 * least one error occurred, the result is FALSE.
	 *
	 * @param  object $object: The object which is supposed to be validated.
	 * @param  F3_FLOW3_Validation_Errors $errors: Here any occured validation error is stored
	 * @return boolean TRUE if validation succeeded completely, FALSE if at least one error occurred.
	 * @throws F3_FLOW3_Validation_Exception_InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 */
	public function validate($object, F3_FLOW3_Validation_Errors &$errors) {
		$isValid = TRUE;

		foreach($object as $argument) {
			$isValid &= $this->validateProperty($object, $argument->getName(), $errors);
		}
	}

	/**
	 * Validates a specific property ($propertyName) of the given object. Any errors will be stored
	 * in the given errors object. If validation succeeds, this method returns TRUE, else it will return FALSE.
	 *
	 * @param  object $object: The object of which the property should be validated
	 * @param  string $propertyName: The name of the property that should be validated
	 * @param  F3_FLOW3_Validation_Errors $errors: Here any occured validation error is stored
	 * @return boolean TRUE if the property could be validated, FALSE if an error occured
	 * @throws F3_FLOW3_Validation_Exception_InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 */
	public function validateProperty($object, $propertyName, F3_FLOW3_Validation_Errors &$errors) {
		$isValid = TRUE;
		if ($object[$propertyName]->getValidator() != NULL) $isValid &= $object[$propertyName]->getValidator()->isValidProperty($object[$propertyName]->getValue(), $errors);
		$isValid &= $object[$propertyName]->getDatatypeValidator()->isValidProperty($object[$propertyName]->getValue(), $errors);

		if (!$isValid) $errors[] = $this->componentManager->getComponent('F3_FLOW3_Validation_Error');
		return $isValid;
	}

	/**
	 * Returns TRUE, if the given propterty ($proptertyValue) is a valid value for the property ($propertyName) of the class ($className).
	 * Any errors will be stored in the given errors object. If at least one error occurred, the result is FALSE.
	 *
	 * @param  string $className: The propterty's class name
	 * @param  string $propertyName: The name of the property for wich the value should be validated
	 * @param  object $propertyValue: The value that should be validated
	 * @return boolean TRUE if the value could be validated for the given property, FALSE if an error occured
	 * @throws F3_FLOW3_Validation_Exception_InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 */
	public function isValidProperty($className, $propertyName, $propertyValue, F3_FLOW3_Validation_Errors &$errors) {
		return FALSE;
	}
}
?>