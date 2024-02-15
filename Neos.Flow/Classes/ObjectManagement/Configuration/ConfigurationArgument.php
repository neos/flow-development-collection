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

use Neos\Flow\Annotations as Flow;

/**
 * Injection (constructor-) argument as used in a Object Configuration
 *
 * @Flow\Proxy(false)
 */
class ConfigurationArgument
{
    public const ARGUMENT_TYPES_STRAIGHTVALUE = 0;

    public const ARGUMENT_TYPES_OBJECT = 1;

    public const ARGUMENT_TYPES_SETTING = 2;

    /**
     * The position of the constructor argument. Counting starts at "1".
     * @var integer
     */
    protected int $index;

    /**
     * @var mixed The argument's value
     */
    protected mixed $value;

    /**
     * Argument type, one of the ARGUMENT_TYPES_* constants
     * @var integer
     */
    protected int $type;

    /**
     * @var integer
     */
    protected int $autowiring = Configuration::AUTOWIRING_MODE_ON;

    /**
     * Constructor - sets the index, value and type of the argument
     *
     * @param int $index Index of the argument
     * @param mixed $value Value of the argument
     * @param integer $type Type of the argument - one of the argument_TYPE_* constants
     */
    public function __construct(int $index, mixed $value, int $type = self::ARGUMENT_TYPES_STRAIGHTVALUE)
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
    public function set(int $index, mixed $value, int $type = self::ARGUMENT_TYPES_STRAIGHTVALUE): void
    {
        $this->index = $index;
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * Returns the index (position) of the argument
     *
     * @return int Index of the argument
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * Returns the value of the argument
     *
     * @return mixed Value of the argument
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Returns the type of the argument
     *
     * @return integer Type of the argument - one of the ARGUMENT_TYPES_* constants
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Sets autowiring for this argument
     *
     * @param integer $autowiring One of the Configuration::AUTOWIRING_MODE_* constants
     * @return void
     */
    public function setAutowiring(int $autowiring): void
    {
        $this->autowiring = $autowiring;
    }

    /**
     * Returns the autowiring mode for this argument
     *
     * @return integer Value of one of the Configuration::AUTOWIRING_MODE_* constants
     */
    public function getAutowiring(): int
    {
        return $this->autowiring;
    }
}
