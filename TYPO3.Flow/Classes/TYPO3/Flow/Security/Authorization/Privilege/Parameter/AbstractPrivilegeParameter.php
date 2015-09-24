<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Parameter;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A privilege parameter
 */
abstract class AbstractPrivilegeParameter implements PrivilegeParameterInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Name of this parameter
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * The value of this parameter
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the string representation of this parameter
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getValue();
    }
}
