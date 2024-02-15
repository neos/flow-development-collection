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
 * Injection property as used in a Object Configuration
 *
 * @Flow\Proxy(false)
 */
class ConfigurationProperty
{
    public const PROPERTY_TYPES_STRAIGHTVALUE = 0;
    public const PROPERTY_TYPES_OBJECT = 1;
    public const PROPERTY_TYPES_CONFIGURATION = 2;
    public const PROPERTY_TYPES_CACHE = 3;

    /**
     * @var string Name of the property
     */
    protected string $name;

    /**
     * @var mixed Value of the property
     */
    protected mixed $value;

    /**
     * @var integer Type of the property - one of the PROPERTY_TYPE_* constants
     */
    protected int $type = self::PROPERTY_TYPES_STRAIGHTVALUE;

    /**
     * If specified, this configuration is used for instantiating / retrieving an property of type object
     *
     * @var Configuration|null
     */
    protected ?Configuration $objectConfiguration = null;

    /**
     * @var integer
     */
    protected int $autowiring = Configuration::AUTOWIRING_MODE_ON;

    /**
     * Should this property be lazy loaded
     *
     * @var boolean
     */
    protected bool $lazyLoading = true;

    /**
     * Constructor - sets the name, type and value of the property
     *
     * @param string $name Name of the property
     * @param mixed $value Value of the property
     * @param integer $type Type of the property - one of the PROPERTY_TYPE_* constants
     * @param Configuration|null $objectConfiguration If $type is OBJECT, a custom object configuration may be specified
     * @param boolean $lazyLoading
     */
    public function __construct(string $name, mixed $value, int $type = self::PROPERTY_TYPES_STRAIGHTVALUE, ?Configuration $objectConfiguration = null, bool $lazyLoading = true)
    {
        $this->set($name, $value, $type, $objectConfiguration, $lazyLoading);
    }

    /**
     * Sets the name, type and value of the property
     *
     * @param string $name Name of the property
     * @param mixed $value Value of the property
     * @param integer $type Type of the property - one of the PROPERTY_TYPE_* constants
     * @param Configuration|null $objectConfiguration If $type is OBJECT, a custom object configuration may be specified
     * @param boolean $lazyLoading
     * @return void
     */
    public function set(string $name, mixed $value, int $type = self::PROPERTY_TYPES_STRAIGHTVALUE, ?Configuration $objectConfiguration = null, bool $lazyLoading = true)
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
        $this->objectConfiguration = $objectConfiguration;
        $this->lazyLoading = $lazyLoading;
    }

    /**
     * Returns the name of the property
     *
     * @return string Name of the property
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the value of the property
     *
     * @return mixed Value of the property
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Returns the type of the property
     *
     * @return integer Type of the property
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Returns the (optional) object configuration which may be defined for properties of type OBJECT
     *
     * @return Configuration|null The object configuration or NULL
     */
    public function getObjectConfiguration(): ?Configuration
    {
        return $this->objectConfiguration;
    }

    /**
     * Sets autowiring for this property
     *
     * @param integer $autowiring One of the Configuration::AUTOWIRING_MODE_* constants
     * @return void
     */
    public function setAutowiring(int $autowiring): void
    {
        $this->autowiring = $autowiring;
    }

    /**
     * Returns the autowiring mode for this property
     *
     * @return integer Value of one of the Configuration::AUTOWIRING_MODE_* constants
     */
    public function getAutowiring(): int
    {
        return $this->autowiring;
    }

    /**
     * If this property can be lazy loaded if the dependency injection mechanism offers that.
     *
     * @return boolean
     */
    public function isLazyLoading(): bool
    {
        return $this->lazyLoading;
    }
}
