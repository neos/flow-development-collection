<?php
namespace Neos\Flow\ObjectManagement\Configuration;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * Injection (constructor-) argument as used in a Object Configuration
 *
 * @Flow\Proxy(false)
 */
class ConfigurationArgument
{
    const ARGUMENT_TYPES_STRAIGHTVALUE = 0;
    const ARGUMENT_TYPES_OBJECT = 1;
    const ARGUMENT_TYPES_SETTING = 2;

    /**
     * The position of the constructor argument. Counting starts at "1".
     * @var integer
     */
    protected $index;

    /**
     * @var mixed The argument's value
     */
    protected $value;

    /**
     * Argument type, one of the ARGUMENT_TYPES_* constants
     * @var integer
     */
    protected $type;

    /**
     * @var integer
     */
    protected $autowiring = Configuration::AUTOWIRING_MODE_ON;

    /**
     * Constructor - sets the index, value and type of the argument
     *
     * @param string $index Index of the argument
     * @param mixed $value Value of the argument
     * @param integer $type Type of the argument - one of the argument_TYPE_* constants
     */
    public function __construct($index, $value, $type = self::ARGUMENT_TYPES_STRAIGHTVALUE)
    {
        $this->set($index, $value, $type);
    }

    /**
     * Sets the index, value, type of the argument and object configuration
     *
     * @param integer $index Index of the argument (counting starts at "1")
     * @param mixed $value Value of the argument
     * @param integer $type Type of the argument - one of the ARGUMENT_TYPE_* constants
     * @return void
     */
    public function set($index, $value, $type = self::ARGUMENT_TYPES_STRAIGHTVALUE)
    {
        $this->index = $index;
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * Returns the index (position) of the argument
     *
     * @return string Index of the argument
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Returns the value of the argument
     *
     * @return mixed Value of the argument
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the type of the argument
     *
     * @return integer Type of the argument - one of the ARGUMENT_TYPES_* constants
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets autowiring for this argument
     *
     * @param integer $autowiring One of the Configuration::AUTOWIRING_MODE_* constants
     * @return void
     */
    public function setAutowiring($autowiring)
    {
        $this->autowiring = $autowiring;
    }

    /**
     * Returns the autowiring mode for this argument
     *
     * @return integer Value of one of the Configuration::AUTOWIRING_MODE_* constants
     */
    public function getAutowiring()
    {
        return $this->autowiring;
    }
}
