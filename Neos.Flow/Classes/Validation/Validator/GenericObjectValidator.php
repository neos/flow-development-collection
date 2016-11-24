<?php
namespace Neos\Flow\Validation\Validator;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\ObjectAccess;
use Neos\Error\Messages\Result as ErrorResult;

/**
 * A generic object validator which allows for specifying property validators.
 *
 * @api
 */
class GenericObjectValidator extends AbstractValidator implements ObjectValidatorInterface
{
    /**
     * @var array
     */
    protected $propertyValidators = [];

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
    public function setValidatedInstancesContainer(\SplObjectStorage $validatedInstancesContainer)
    {
        $this->validatedInstancesContainer = $validatedInstancesContainer;
    }

    /**
     * Checks if the given value is valid according to the validator, and returns
     * the Error Messages object which occurred.
     *
     * @param mixed $value The value that should be validated
     * @return ErrorResult
     * @api
     */
    public function validate($value)
    {
        $this->result = new ErrorResult();
        if ($this->acceptsEmptyValues === false || $this->isEmpty($value) === false) {
            if (!is_object($value)) {
                $this->addError('Object expected, %1$s given.', 1241099149, [gettype($value)]);
            } elseif ($this->isValidatedAlready($value) === false) {
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
    protected function isValid($object)
    {
        $messages = new ErrorResult();
        foreach ($this->propertyValidators as $propertyName => $validators) {
            $propertyValue = $this->getPropertyValue($object, $propertyName);
            $result = $this->checkProperty($propertyValue, $validators);
            if ($result !== null) {
                $messages->forProperty($propertyName)->merge($result);
            }
        }
        $this->result = $messages;
    }

    /**
     * @param object $object
     * @return boolean
     */
    protected function isValidatedAlready($object)
    {
        if ($this->validatedInstancesContainer === null) {
            $this->validatedInstancesContainer = new \SplObjectStorage();
        }
        if ($this->validatedInstancesContainer->contains($object)) {
            return true;
        } else {
            $this->validatedInstancesContainer->attach($object);

            return false;
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
    protected function getPropertyValue($object, $propertyName)
    {
        if ($object instanceof \Doctrine\ORM\Proxy\Proxy) {
            $object->__load();
        }

        if (ObjectAccess::isPropertyGettable($object, $propertyName)) {
            return ObjectAccess::getProperty($object, $propertyName);
        } else {
            return ObjectAccess::getProperty($object, $propertyName, true);
        }
    }

    /**
     * Checks if the specified property of the given object is valid, and adds
     * found errors to the $messages object.
     *
     * @param mixed $value The value to be validated
     * @param array $validators The validators to be called on the value
     * @return NULL|ErrorResult
     */
    protected function checkProperty($value, $validators)
    {
        $result = null;
        foreach ($validators as $validator) {
            if ($validator instanceof ObjectValidatorInterface) {
                $validator->setValidatedInstancesContainer($this->validatedInstancesContainer);
            }
            $currentResult = $validator->validate($value);
            if ($currentResult->hasMessages()) {
                if ($result === null) {
                    $result = $currentResult;
                } else {
                    $result->merge($currentResult);
                }
            }
        }
        return $result;
    }

    /**
     * Adds the given validator for validation of the specified property.
     *
     * @param string $propertyName Name of the property to validate
     * @param ValidatorInterface $validator The property validator
     * @return void
     * @api
     */
    public function addPropertyValidator($propertyName, ValidatorInterface $validator)
    {
        if (!isset($this->propertyValidators[$propertyName])) {
            $this->propertyValidators[$propertyName] = new \SplObjectStorage();
        }
        $this->propertyValidators[$propertyName]->attach($validator);
    }

    /**
     * Returns all property validators - or only validators of the specified property
     *
     * @param string $propertyName Name of the property to return validators for
     * @return array An array of validators
     */
    public function getPropertyValidators($propertyName = null)
    {
        if ($propertyName !== null) {
            return (isset($this->propertyValidators[$propertyName])) ? $this->propertyValidators[$propertyName] : [];
        } else {
            return $this->propertyValidators;
        }
    }
}
