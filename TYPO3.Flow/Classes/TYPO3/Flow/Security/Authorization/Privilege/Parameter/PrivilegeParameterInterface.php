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
 * Contract for a privilege parameter
 */
interface PrivilegeParameterInterface
{
    /**
     * Note: We can't define constructors in interfaces, but this is assumed to exist in the concrete implementation!
     *
     * @param string $name
     * @param mixed $value
     */
    //public function __construct($name, $value);

    /**
     * Name of this parameter
     *
     * @return string
     */
    public function getName();

    /**
     * The value of this parameter
     *
     * @return mixed
     */
    public function getValue();

    /**
     * @return array
     */
    public function getPossibleValues();

    /**
     * @param mixed $value
     * @return boolean
     */
    public function validate($value);

    /**
     * @return string
     */
    public function getType();

    /**
     * Returns the string representation of this parameter
     * @return string
     */
    public function __toString();
}
