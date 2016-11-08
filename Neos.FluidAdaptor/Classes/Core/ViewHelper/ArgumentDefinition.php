<?php
namespace Neos\FluidAdaptor\Core\ViewHelper;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition as FluidArgumentDefinition;

/**
 * Argument definition of each view helper argument
 *
 * @deprecated use \TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition
 */
class ArgumentDefinition extends FluidArgumentDefinition
{
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
     * TRUE if it is a method parameter
     *
     * @return boolean TRUE if it's a method parameter
     */
    public function isMethodParameter()
    {
        return $this->isMethodParameter;
    }
}
