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
 * A privilege parameter definition
 */
class PrivilegeParameterDefinition
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $parameterClassName;

    /**
     * @param string $name
     * @param string $parameterClassName
     */
    public function __construct($name, $parameterClassName)
    {
        $this->name = $name;
        $this->parameterClassName = $parameterClassName;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getParameterClassName()
    {
        return $this->parameterClassName;
    }
}
