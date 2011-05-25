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
 * A generic object validator which allows for specifying property validators
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class GenericObjectValidator implements \F3\FLOW3\Validation\Validator\ValidatorInterface {

	/**
	 * @var array
	 */
	protected $propertyValidators = array();

	/**
	 *
	 * @var \SplObjectStorage
	 */
	static protected $instancesCurrentlyUnderValidation;

	/**
	 * Sets validation options for the validator
	 *
	 * @param array $validationOptions The validation options
	 * @return void
	 */
	public function __construct($validationOptions) {
	}

	/**
	 * Checks if the given value is valid according to the property validators
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @param boolean $resetInstancesCurrentlyUnderValidation Reserved for internal use!
	 * @return \F3\FLOW3\Error\Result
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function validate($object) {
		$messages = new \F3\FLOW3\Error\Result();

		if (self::$instancesCurrentlyUnderValidation === NULL) {
			self::$instancesCurrentlyUnderValidation = new \SplObjectStorage();
		}

		if ($object === NULL) {
			return $messages;
		}

		if (!is_object($object)) {
			$messages->addError(new \F3\FLOW3\Error\Error('Object expected, ' . gettype($object) . ' given.', 1241099149));
			return $messages;
		}

		if (self::$instancesCurrentlyUnderValidation->contains($object)) {
			return $messages;
		} else {
			self::$instancesCurrentlyUnderValidation->attach($object);
		}

		foreach ($this->propertyValidators as $propertyName => $validators) {
			$propertyValue = $this->getPropertyValue($object, $propertyName);
			$this->checkProperty($propertyValue, $validators, $messages->forProperty($propertyName));
		}

		self::$instancesCurrentlyUnderValidation->detach($object);
		return $messages;
	}

	/**
	 * Load the property value to be used for validation.
	 *
	 * In case the object is a doctrine proxy, we need to load the real instance first.
	 *
	 * @param object $object
	 * @param string $propertyName
	 * @return mixed
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function getPropertyValue($object, $propertyName) {
		if ($object instanceof \Doctrine\ORM\Proxy\Proxy) {
			$reflectionLoadMethod = new \ReflectionMethod($object, '__load');
			$reflectionLoadMethod->setAccessible(TRUE);
			$reflectionLoadMethod->invoke($object);
		}

		if (\F3\FLOW3\Reflection\ObjectAccess::isPropertyGettable($object, $propertyName)) {
			return \F3\FLOW3\Reflection\ObjectAccess::getProperty($object, $propertyName);
		} else {
			return \F3\FLOW3\Reflection\ObjectAccess::getProperty($object, $propertyName, TRUE);
		}

	}

	/**
	 * Checks if the specified property of the given object is valid.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param object $object The object containing the property to validate
	 * @param string $propertyName Name of the property to validate
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function checkProperty($value, $validators, $messages) {
		foreach ($validators as $validator) {
			$messages->merge($validator->validate($value));
		}
	}


	/**
	 * Checks the given object can be validated by the validator implementation
	 *
	 * @param object $object The object to be checked
	 * @return boolean TRUE if the given value is an object
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function canValidate($object) {
		return is_object($object);
	}

	/**
	 * Adds the given validator for validation of the specified property.
	 *
	 * @param string $propertyName Name of the property to validate
	 * @param \F3\FLOW3\Validation\Validator\ValidatorInterface $validator The property validator
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function addPropertyValidator($propertyName, \F3\FLOW3\Validation\Validator\ValidatorInterface $validator) {
		if (!isset($this->propertyValidators[$propertyName])) {
			$this->propertyValidators[$propertyName] = new \SplObjectStorage();
		}
		$this->propertyValidators[$propertyName]->attach($validator);
	}

	/**
	 * Returns all property validators - or only validators of the specified property
	 *
	 * @param string $propertyName (optional) Name of the property to return validators for
	 * @return array An array of validators
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyValidators($propertyName = NULL) {
		if ($propertyName !== NULL) {
			return (isset($this->propertyValidators[$propertyName])) ? $this->propertyValidators[$propertyName] : array();
		} else {
			return $this->propertyValidators;
		}
	}
}

?>