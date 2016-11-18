<?php
namespace TYPO3\Flow\Mvc\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Result;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Utility\TypeHandling;
use TYPO3\Flow\Validation\Validator\ValidatorInterface;

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
     * Short name of this argument
     * @var string
     */
    protected $shortName = null;

    /**
     * Short help message for this argument
     * @var string
     */
    protected $shortHelpMessage = null;

    /**
     * Data type of this argument's value
     * @var string
     */
    protected $dataType = null;

    /**
     * TRUE if this argument is required
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
     * @var ValidatorInterface
     */
    protected $validator = null;

    /**
     * The validation results. This can be asked if the argument has errors.
     * @var Result
     */
    protected $validationResults = null;

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
    public function __construct($name, $dataType)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('$name must be of type string, ' . gettype($name) . ' given.', 1187951688);
        }
        if (strlen($name) === 0) {
            throw new \InvalidArgumentException('$name must be a non-empty string, ' . strlen($name) . ' characters given.', 1232551853);
        }
        $this->name = $name;
        $this->setDataType($dataType);
    }

    /**
     * Returns the name of this argument
     *
     * @return string This argument's name
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the short name of this argument.
     *
     * @param string $shortName A "short name" - a single character
     * @return Argument $this
     * @throws \InvalidArgumentException if $shortName is not a character
     * @api
     */
    public function setShortName($shortName)
    {
        if ($shortName !== null && (!is_string($shortName) || strlen($shortName) !== 1)) {
            throw new \InvalidArgumentException('$shortName must be a single character or NULL', 1195824959);
        }
        $this->shortName = $shortName;
        return $this;
    }

    /**
     * Returns the short name of this argument
     *
     * @return string This argument's short name
     * @api
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * Sets the data type of this argument that is also used for property mapping.
     * @param string $dataType
     * @return Argument $this
     */
    public function setDataType($dataType)
    {
        $this->dataType = TypeHandling::normalizeType($dataType);
        return $this;
    }

    /**
     * Returns the data type of this argument's value
     *
     * @return string The data type
     * @api
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * Marks this argument to be required
     *
     * @param boolean $required TRUE if this argument should be required
     * @return Argument $this
     * @api
     */
    public function setRequired($required)
    {
        $this->isRequired = (boolean)$required;
        return $this;
    }

    /**
     * Returns TRUE if this argument is required
     *
     * @return boolean TRUE if this argument is required
     * @api
     */
    public function isRequired()
    {
        return $this->isRequired;
    }

    /**
     * Sets a short help message for this argument. Mainly used at the command line, but maybe
     * used elsewhere, too.
     *
     * @param string $message A short help message
     * @return Argument $this
     * @throws \InvalidArgumentException
     * @api
     */
    public function setShortHelpMessage($message)
    {
        if (!is_string($message)) {
            throw new \InvalidArgumentException('The help message must be of type string, ' . gettype($message) . 'given.', 1187958170);
        }
        $this->shortHelpMessage = $message;
        return $this;
    }

    /**
     * Returns the short help message
     *
     * @return string The short help message
     * @api
     */
    public function getShortHelpMessage()
    {
        return $this->shortHelpMessage;
    }

    /**
     * Sets the default value of the argument
     *
     * @param mixed $defaultValue Default value
     * @return Argument $this
     * @api
     */
    public function setDefaultValue($defaultValue)
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
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Returns the set validator
     *
     * @return ValidatorInterface The set validator, NULL if none was set
     * @api
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Sets the value of this argument.
     *
     * @param mixed $rawValue The value of this argument
     * @return Argument $this
     */
    public function setValue($rawValue)
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
        return ($this->value === null) ? $this->defaultValue : $this->value;
    }

    /**
     * Return the Property Mapping Configuration used for this argument; can be used by the initialize*action to modify the Property Mapping.
     *
     * @return MvcPropertyMappingConfiguration
     * @api
     */
    public function getPropertyMappingConfiguration()
    {
        if ($this->propertyMappingConfiguration === null) {
            $this->propertyMappingConfiguration = new MvcPropertyMappingConfiguration();
        }
        return $this->propertyMappingConfiguration;
    }

    /**
     * @return boolean TRUE if the argument is valid, FALSE otherwise
     * @api
     */
    public function isValid()
    {
        return !$this->validationResults->hasErrors();
    }

    /**
     * @return array<TYPO3\Flow\Error\Result> Validation errors which have occured.
     * @api
     */
    public function getValidationResults()
    {
        return $this->validationResults;
    }
}
