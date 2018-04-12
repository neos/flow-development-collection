<?php
namespace Neos\Flow\Security\Authorization\Privilege\Parameter;

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
