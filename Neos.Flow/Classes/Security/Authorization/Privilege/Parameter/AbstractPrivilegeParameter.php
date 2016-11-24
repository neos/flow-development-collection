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
