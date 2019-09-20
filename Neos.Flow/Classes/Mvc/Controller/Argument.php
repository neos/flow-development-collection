<?php
namespace Neos\Flow\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Result;
use Neos\Flow\Property\PropertyMapper;
use Neos\Utility\TypeHandling;
use Neos\Flow\Validation\Validator\ValidatorInterface;

/**
 * A controller argument
 *
 * @api
 */
class Argument
{
    /**
     * Name of this argument
     * @var string
     */
    protected $name = '';

    /**
     * Data type of this argument's value
     * @var string
     */
    protected $dataType = null;

    /**
     * true if this argument is required
     * @var boolean
     */
    protected $isRequired = false;

    /**
     * Actual value of this argument
     * @var object
     */
    protected $value = null;

    /**
     * Default value. Used if argument is optional.
     * @var mixed
     */
    protected $defaultValue = null;

    /**
     * A custom validator, used supplementary to the base validation
     * @var ValidatorInterface|null
     */
    protected $validator = null;

    /**
     * The validation results. This can be asked if the argument has errors.
     * @var Result
     */
    protected $validationResults = null;

    /**
     * If the request body should be mapped into this argument.
     * @var bool
     */
    protected $mapRequestBody = false;

    /**
     * @var MvcPropertyMappingConfiguration
     */
    protected $propertyMappingConfiguration;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * Constructs this controller argument
     *
     * @param string $name Name of this argument
     * @param string $dataType The data type of this argument
     * @throws \InvalidArgumentException if $name is not a string or empty
     * @api
     */
    public function __construct(string $name, string $dataType)
    {
        if ($name === '') {
            throw new \InvalidArgumentException('$name must be a non-empty string, ' . strlen($name) . ' characters given.', 1232551853);
        }
        $this->name = $name;
        $this->dataType = TypeHandling::normalizeType($dataType);
    }

    /**
     * Returns the name of this argument
     *
     * @return string This argument's name
     * @api
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the data type of this argument's value
     *
     * @return string The data type
     * @api
     */
    public function getDataType(): string
    {
        return $this->dataType;
    }

    /**
     * Marks this argument to be required
     *
     * @param boolean $required true if this argument should be required
     * @return Argument $this
     * @api
     */
    public function setRequired(bool $required): Argument
    {
        $this->isRequired = $required;
        return $this;
    }

    /**
     * Returns true if this argument is required
     *
     * @return boolean true if this argument is required
     * @api
     */
    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    /**
     * Sets the default value of the argument
     *
     * @param mixed $defaultValue Default value
     * @return Argument $this
     * @api
     */
    public function setDefaultValue($defaultValue): Argument
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    /**
     * Returns the default value of this argument
     *
     * @return mixed The default value
     * @api
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Sets a custom validator which is used supplementary to the base validation
     *
     * @param ValidatorInterface $validator The actual validator object
     * @return Argument Returns $this (used for fluent interface)
     * @api
     */
    public function setValidator(ValidatorInterface $validator): Argument
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Returns the set validator
     *
     * @return ValidatorInterface|null The set validator, NULL if none was set
     * @api
     */
    public function getValidator(): ?ValidatorInterface
    {
        return $this->validator;
    }

    /**
     * Sets the value of this argument.
     *
     * @param mixed $rawValue The value of this argument
     * @return Argument $this
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function setValue($rawValue): Argument
    {
        if ($rawValue === null) {
            $this->value = null;
            return $this;
        }
        if (is_object($rawValue) && $rawValue instanceof $this->dataType) {
            $this->value = $rawValue;
            return $this;
        }
        $this->value = $this->propertyMapper->convert($rawValue, $this->dataType, $this->getPropertyMappingConfiguration());
        $this->validationResults = $this->propertyMapper->getMessages();
        if ($this->validator !== null) {
            $validationMessages = $this->validator->validate($this->value);
            $this->validationResults->merge($validationMessages);
        }

        return $this;
    }

    /**
     * Returns the value of this argument. If the value is NULL, we use the defaultValue.
     *
     * @return object The value of this argument - if none was set, the default value is returned
     * @api
     */
    public function getValue()
    {
        return $this->value ?? $this->defaultValue;
    }

    /**
     * Return the Property Mapping Configuration used for this argument; can be used by the initialize*action to modify the Property Mapping.
     *
     * @return MvcPropertyMappingConfiguration
     * @api
     */
    public function getPropertyMappingConfiguration(): MvcPropertyMappingConfiguration
    {
        if ($this->propertyMappingConfiguration === null) {
            $this->propertyMappingConfiguration = new MvcPropertyMappingConfiguration();
        }
        return $this->propertyMappingConfiguration;
    }

    /**
     * @return Result|null Validation errors which have occurred.
     * @api
     */
    public function getValidationResults(): ?Result
    {
        return $this->validationResults;
    }

    /**
     * @return bool
     */
    public function getMapRequestBody(): bool
    {
        return $this->mapRequestBody;
    }

    /**
     * Set if the request body should be mapped into this argument.
     *
     * @param bool $mapRequestBody
     * @return Argument $this
     */
    public function setMapRequestBody(bool $mapRequestBody): Argument
    {
        $this->mapRequestBody = $mapRequestBody;
        return $this;
    }
}
