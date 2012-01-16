<?php
namespace TYPO3\FLOW3\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * A generic object validator which allows for specifying property validators
 *
 * @api
 */
class GenericObjectValidator implements \TYPO3\FLOW3\Validation\Validator\ValidatorInterface {

	/**
	 * @var array
	 */
	protected $options = array();

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
	 * Constructs the object validator and sets validation options
	 *
	 * @param array $options The validation options
	 */
	public function __construct(array $options = array()) {
		$this->options = $options;
	}

	/**
	 * Checks if the given value is valid according to the property validators
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $object The value that should be validated
	 * @return \TYPO3\FLOW3\Error\Result
	 * @api
	 */
	public function validate($object) {
		$messages = new \TYPO3\FLOW3\Error\Result();

		if (self::$instancesCurrentlyUnderValidation === NULL) {
			self::$instancesCurrentlyUnderValidation = new \SplObjectStorage();
		}

		if ($object === NULL) {
			return $messages;
		}

		if (!is_object($object)) {
			$messages->addError(new \TYPO3\FLOW3\Validation\Error('Object expected, %1$s given.', 1241099149, array(gettype($object))));
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
	 */
	protected function getPropertyValue($object, $propertyName) {
		if ($object instanceof \Doctrine\ORM\Proxy\Proxy) {
			$reflectionLoadMethod = new \ReflectionMethod($object, '__load');
			$reflectionLoadMethod->setAccessible(TRUE);
			$reflectionLoadMethod->invoke($object);
		}

		if (\TYPO3\FLOW3\Reflection\ObjectAccess::isPropertyGettable($object, $propertyName)) {
			return \TYPO3\FLOW3\Reflection\ObjectAccess::getProperty($object, $propertyName);
		} else {
			return \TYPO3\FLOW3\Reflection\ObjectAccess::getProperty($object, $propertyName, TRUE);
		}
	}

	/**
	 * Checks if the specified property of the given object is valid, and adds
	 * found errors to the $messages object.
	 *
	 * @param mixed $value The value to be validated
	 * @param array $validators The validators to be called on the value
	 * @param \TYPO3\FLOW3\Error\Result $messages the result object to which the validation errors should be added
	 * @return void
	 */
	protected function checkProperty($value, $validators, \TYPO3\FLOW3\Error\Result $messages) {
		foreach ($validators as $validator) {
			$messages->merge($validator->validate($value));
		}
	}


	/**
	 * Checks the given object can be validated by the validator implementation
	 *
	 * @param object $object The object to be checked
	 * @return boolean TRUE if the given value is an object
	 * @api
	 */
	public function canValidate($object) {
		return is_object($object);
	}

	/**
	 * Adds the given validator for validation of the specified property.
	 *
	 * @param string $propertyName Name of the property to validate
	 * @param \TYPO3\FLOW3\Validation\Validator\ValidatorInterface $validator The property validator
	 * @return void
	 * @api
	 */
	public function addPropertyValidator($propertyName, \TYPO3\FLOW3\Validation\Validator\ValidatorInterface $validator) {
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
	 */
	public function getPropertyValidators($propertyName = NULL) {
		if ($propertyName !== NULL) {
			return (isset($this->propertyValidators[$propertyName])) ? $this->propertyValidators[$propertyName] : array();
		} else {
			return $this->propertyValidators;
		}
	}

	/**
	 * Returns the options of this validator
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}
}

?>