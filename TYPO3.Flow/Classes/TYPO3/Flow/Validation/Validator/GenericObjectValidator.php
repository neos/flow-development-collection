<?php
namespace TYPO3\Flow\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A generic object validator which allows for specifying property validators.
 *
 * @api
 */
class GenericObjectValidator extends AbstractValidator implements ObjectValidatorInterface {

	/**
	 * @var array
	 */
	protected $propertyValidators = array();

	/**
	 * @var \SplObjectStorage
	 */
	protected $validatedInstancesContainer;

	/**
	 * Allows to set a container to keep track of validated instances.
	 *
	 * @param \SplObjectStorage $validatedInstancesContainer A container to keep track of validated instances
	 * @return void
	 * @api
	 */
	public function setValidatedInstancesContainer(\SplObjectStorage $validatedInstancesContainer) {
		$this->validatedInstancesContainer = $validatedInstancesContainer;
	}

	/**
	 * Checks if the given value is valid according to the validator, and returns
	 * the Error Messages object which occurred.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\Flow\Error\Result
	 * @api
	 */
	public function validate($value) {
		$this->result = new \TYPO3\Flow\Error\Result();

		if ($this->acceptsEmptyValues === FALSE || $this->isEmpty($value) === FALSE) {
			if (!is_object($value)) {
				$this->addError('Object expected, %1$s given.', 1241099149, array(gettype($value)));
			} elseif ($this->isValidatedAlready($value) === FALSE) {
				$this->isValid($value);
			}
		}
		return $this->result;
	}

	/**
	 * Checks if the given value is valid according to the property validators.
	 *
	 * @param mixed $object The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($object) {
		$messages = new \TYPO3\Flow\Error\Result();
		foreach ($this->propertyValidators as $propertyName => $validators) {
			$propertyValue = $this->getPropertyValue($object, $propertyName);
			$this->checkProperty($propertyValue, $validators, $messages->forProperty($propertyName));
		}
		$this->result = $messages;

		return;
	}

	/**
	 * @param object $object
	 * @return boolean
	 */
	protected function isValidatedAlready($object) {
		if ($this->validatedInstancesContainer === NULL) {
			$this->validatedInstancesContainer = new \SplObjectStorage();
		}
		if ($this->validatedInstancesContainer->contains($object)) {
			return TRUE;
		} else {
			$this->validatedInstancesContainer->attach($object);
			return FALSE;
		}
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

		if (\TYPO3\Flow\Reflection\ObjectAccess::isPropertyGettable($object, $propertyName)) {
			return \TYPO3\Flow\Reflection\ObjectAccess::getProperty($object, $propertyName);
		} else {
			return \TYPO3\Flow\Reflection\ObjectAccess::getProperty($object, $propertyName, TRUE);
		}
	}

	/**
	 * Checks if the specified property of the given object is valid, and adds
	 * found errors to the $messages object.
	 *
	 * @param mixed $value The value to be validated
	 * @param array $validators The validators to be called on the value
	 * @param \TYPO3\Flow\Error\Result $messages the result object to which the validation errors should be added
	 * @return void
	 */
	protected function checkProperty($value, $validators, \TYPO3\Flow\Error\Result $messages) {
		foreach ($validators as $validator) {
			if ($validator instanceof ObjectValidatorInterface) {
				$validator->setValidatedInstancesContainer($this->validatedInstancesContainer);
			}
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
	 * @param \TYPO3\Flow\Validation\Validator\ValidatorInterface $validator The property validator
	 * @return void
	 * @api
	 */
	public function addPropertyValidator($propertyName, \TYPO3\Flow\Validation\Validator\ValidatorInterface $validator) {
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
}

?>