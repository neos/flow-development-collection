<?php
namespace Neos\Flow\Mvc\Routing;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * abstract Route Part
 *
 */
abstract class AbstractRoutePart implements RoutePartInterface
{
    /**
     * Name of the Route Part
     *
     * @var string
     */
    protected $name = null;

    /**
     * Value of the Route Part after decoding.
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * Default value of the Route Part.
     *
     * @var mixed
     */
    protected $defaultValue = null;

    /**
     * Specifies whether this Route Part is optional. Which means it's put in parentheses in the routes URI pattern.
     *
     * @var boolean
     */
    protected $isOptional = false;

    /**
     * Specifies whether this Route Part should be converted to lower case when resolved.
     *
     * @var boolean
     */
    protected $lowerCase = true;

    /**
     * Contains options for this Route Part.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Sets name of the Route Part.
     *
     * @param string $partName
     * @return void
     */
    public function setName($partName)
    {
        $this->name = $partName;
    }

    /**
     * Returns name of the Route Part.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns TRUE if a value is set for this Route Part, otherwise FALSE.
     *
     * @return boolean
     */
    public function hasValue()
    {
        return isset($this->value);
    }

    /**
     * Returns value of the Route Part. Before match() is called this returns NULL.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns TRUE if a default value is set for this Route Part, otherwise FALSE.
     *
     * @return boolean
     */
    public function hasDefaultValue()
    {
        return isset($this->defaultValue);
    }

    /**
     * Sets default value of the Route Part.
     *
     * @param mixed $defaultValue
     * @return void
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Gets default value of the Route Part.
     *
     * @return mixed $defaultValue
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }


    /**
     * Specifies whether this Route part is optional.
     *
     * @param boolean $isOptional TRUE: this Route part is optional. FALSE: this Route part is required.
     * @return void
     */
    public function setOptional($isOptional)
    {
        $this->isOptional = $isOptional;
    }

    /**
     * Getter for $this->isOptional.
     *
     * @return boolean TRUE if this Route part is optional, otherwise FALSE.
     * @see setOptional()
     */
    public function isOptional()
    {
        return $this->isOptional;
    }

    /**
     * Specifies whether this Route part should be converted to lower case when resolved.
     *
     * @param boolean $lowerCase TRUE: this Route part is converted to lower case. FALSE: this Route part is not altered.
     * @return void
     */
    public function setLowerCase($lowerCase)
    {
        $this->lowerCase = $lowerCase;
    }

    /**
     * Getter for $this->lowerCase.
     *
     * @return boolean TRUE if this Route part will be converted to lower case, otherwise FALSE.
     * @see setLowerCase()
     */
    public function isLowerCase()
    {
        return $this->lowerCase;
    }

    /**
     * Defines options for this Route Part.
     * Options can be used to enrich a route part with parameters or settings like case sensivity.
     *
     * @param array $options
     * @return void
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return array options of this Route Part.
     */
    public function getOptions()
    {
        return $this->options;
    }
}
