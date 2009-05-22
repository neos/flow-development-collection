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
 * A generic object validator which allows for specifying property validators
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class GenericObjectValidator extends \F3\FLOW3\Validation\Validator\AbstractObjectValidator {

	/**
	 * @var array
	 */
	protected $propertyValidators = array();

	/**
	 * Checks if the given value is valid according to the property validators
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValid($value) {
		if (!is_object($value)) {
			$this->addError('Value is no object.', 1241099148);
			return FALSE;
		}

		$result = TRUE;
		foreach (array_keys($this->propertyValidators) as $propertyName) {
			if ($this->isPropertyValid($value, $propertyName) === FALSE) {
				$result = FALSE;
			}
		}
		return $result;
	}

	/**
	 * Checks the given object can be validated by the validator implementation
	 *
	 * @param object $object The object to be checked
	 * @return boolean TRUE if the given value is an object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canValidate($object) {
		return is_object($object);
	}

	/**
	 * Checks if the specified property of the given object is valid.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param object $object The object containing the property to validate
	 * @param string $propertyName Name of the property to validate
	 * @return boolean TRUE if the property value is valid, FALSE if an error occured
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPropertyValid($object, $propertyName) {
		if (!is_object($object)) throw new \InvalidArgumentException('Object expected, ' . gettype($object) . ' given.', 1241099149);
		if (!isset($this->propertyValidators[$propertyName])) return TRUE;
		
		$result = TRUE;
		foreach ($this->propertyValidators[$propertyName] as $validator) {
			if ($validator->isValid(\F3\FLOW3\Reflection\ObjectAccess::getProperty($object, $propertyName)) === FALSE) {
				$this->addErrorsForProperty($validator->getErrors(), $propertyName);
				$result = FALSE;
			}
		}
		return $result;
	}
	
	/**
	 * @param array $errors Array of \F3\FLOW3\Validation\Error
	 * @param string $propertyName Name of the property to add errors
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com> 
	 */
	protected function addErrorsForProperty($errors, $propertyName) {
		if (!isset($this->errors[$propertyName])) {
			$this->errors[$propertyName] = $this->objectFactory->create('\F3\FLOW3\Validation\PropertyError', $propertyName);
		}
		$this->errors[$propertyName]->addErrors($errors);
	}

	/**
	 * Adds the given validator for validation of the specified property.
	 *
	 * @param string $propertyName Name of the property to validate
	 * @param \F3\FLOW3\Validation\Validator\ValidatorInterface $validator The property validator
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addPropertyValidator($propertyName, \F3\FLOW3\Validation\Validator\ValidatorInterface $validator) {
		if (!isset($this->propertyValidators[$propertyName])) {
			$this->propertyValidators[$propertyName] = new \SPLObjectStorage;
		}
		$this->propertyValidators[$propertyName]->attach($validator);
	}
}

?>