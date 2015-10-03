<?php
namespace TYPO3\Fluid\Core\ViewHelper;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Argument definition of each view helper argument
 */
class ArgumentDefinition
{
    /**
     * Name of argument
     *
     * @var string
     */
    protected $name;

    /**
     * Type of argument
     *
     * @var string
     */
    protected $type;

    /**
     * Description of argument
     *
     * @var string
     */
    protected $description;

    /**
     * Is argument required?
     *
     * @var boolean
     */
    protected $required = false;

    /**
     * Default value for argument
     *
     * @var mixed
     */
    protected $defaultValue = null;

    /**
     * TRUE if it is a method parameter
     *
     * @var boolean
     */
    protected $isMethodParameter = false;

    /**
     * Constructor for this argument definition.
     *
     * @param string $name Name of argument
     * @param string $type Type of argument
     * @param string $description Description of argument
     * @param boolean $required TRUE if argument is required
     * @param mixed $defaultValue Default value
     * @param boolean $isMethodParameter TRUE if this argument is a method parameter
     */
    public function __construct($name, $type, $description, $required, $defaultValue = null, $isMethodParameter = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->description = $description;
        $this->required = $required;
        $this->defaultValue = $defaultValue;
        $this->isMethodParameter = $isMethodParameter;
    }

    /**
     * Get the name of the argument
     *
     * @return string Name of argument
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the type of the argument
     *
     * @return string Type of argument
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the description of the argument
     *
     * @return string Description of argument
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the optionality of the argument
     *
     * @return boolean TRUE if argument is optional
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Get the default value, if set
     *
     * @return mixed Default value
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * TRUE if it is a method parameter
     *
     * @return boolean TRUE if it's a method parameter
     */
    public function isMethodParameter()
    {
        return $this->isMethodParameter;
    }
}
