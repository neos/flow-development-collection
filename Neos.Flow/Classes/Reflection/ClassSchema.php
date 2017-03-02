<?php
namespace Neos\Flow\Reflection;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;

/**
 * A class schema
 *
 */
class ClassSchema
{
    /**
     * Available model types
     */
    const MODELTYPE_ENTITY = 1;
    const MODELTYPE_VALUEOBJECT = 2;

    /**
     * Name of the class this schema is referring to
     *
     * @var string
     */
    protected $className;

    /**
     * Model type of the class this schema is referring to
     *
     * @var integer
     */
    protected $modelType = self::MODELTYPE_ENTITY;

    /**
     * Whether instances of the class can be lazy-loadable
     * @var boolean
     */
    protected $lazyLoadable = false;

    /**
     * @var string
     */
    protected $repositoryClassName;

    /**
     * Properties of the class which need to be persisted
     *
     * @var array
     */
    protected $properties = [];

    /**
     * The properties forming the identity of an object
     *
     * @var array
     */
    protected $identityProperties = [];

    /**
     * Constructs this class schema
     *
     * @param string $className Name of the class this schema is referring to
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * Returns the class name this schema is referring to
     *
     * @return string The class name
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Marks the class as being lazy-loadable.
     *
     * @param boolean $lazyLoadable
     * @return void
     */
    public function setLazyLoadableObject($lazyLoadable)
    {
        $this->lazyLoadable = $lazyLoadable;
    }

    /**
     * Marks the class as being lazy-loadable.
     *
     * @return boolean
     */
    public function isLazyLoadableObject()
    {
        return $this->lazyLoadable;
    }

    /**
     * Adds (defines) a specific property and its type.
     *
     * @param string $name Name of the property
     * @param string $type Type of the property
     * @param boolean $lazy Whether the property should be lazy-loaded when reconstituting
     * @param boolean $transient Whether the property should not be considered for persistence
     * @return void
     * @throws \InvalidArgumentException
     */
    public function addProperty($name, $type, $lazy = false, $transient = false)
    {
        try {
            $type = TypeHandling::parseType($type);
        } catch (InvalidTypeException $exception) {
            throw new \InvalidArgumentException(sprintf($exception->getMessage(), 'class "' . $name . '"'), 1315564474);
        }
        $this->properties[$name] = [
            'type' => $type['type'],
            'elementType' => $type['elementType'],
            'lazy' => $lazy,
            'transient' => $transient
        ];
    }

    /**
     * Returns the given property defined in this schema. Check with
     * hasProperty($propertyName) before!
     *
     * @param string $propertyName
     * @return array
     */
    public function getProperty($propertyName)
    {
        return $this->properties[$propertyName];
    }

    /**
     * Returns all properties defined in this schema
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Checks if the given property defined in this schema is multi-valued (i.e.
     * array or SplObjectStorage).
     *
     * @param string $propertyName
     * @return boolean
     */
    public function isMultiValuedProperty($propertyName)
    {
        return ($this->properties[$propertyName]['type'] === 'array' || $this->properties[$propertyName]['type'] === 'SplObjectStorage' || $this->properties[$propertyName]['type'] === 'Doctrine\Common\Collections\Collection' || $this->properties[$propertyName]['type'] === 'Doctrine\Common\Collections\ArrayCollection');
    }

    /**
     * Sets the model type of the class this schema is referring to.
     *
     * @param integer $modelType The model type, one of the MODELTYPE_* constants.
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setModelType($modelType)
    {
        if ($modelType !== self::MODELTYPE_ENTITY && $modelType !== self::MODELTYPE_VALUEOBJECT) {
            throw new \InvalidArgumentException('"' . $modelType . '" is an invalid model type.', 1212519195);
        }
        $this->modelType = $modelType;
        if ($modelType === self::MODELTYPE_VALUEOBJECT) {
            $this->identityProperties = [];
            $this->repositoryClassName = null;
        }
    }

    /**
     * Returns the model type of the class this schema is referring to.
     *
     * @return integer The model type, one of the MODELTYPE_* constants.
     */
    public function getModelType()
    {
        return $this->modelType;
    }

    /**
     * Set the class name of the repository managing an entity.
     *
     * @param string $repositoryClassName
     * @return void
     * @throws Exception\ClassSchemaConstraintViolationException
     */
    public function setRepositoryClassName($repositoryClassName)
    {
        if ($this->modelType === self::MODELTYPE_VALUEOBJECT && $repositoryClassName !== null) {
            throw new Exception\ClassSchemaConstraintViolationException('Value objects must not be aggregate roots (have a repository)', 1268739172);
        }
        $this->repositoryClassName = $repositoryClassName;
    }

    /**
     * @return string
     */
    public function getRepositoryClassName()
    {
        return $this->repositoryClassName;
    }

    /**
     * Whether the class is accessible through a repository and therefore an aggregate root.
     *
     * @return boolean TRUE
     */
    public function isAggregateRoot()
    {
        return $this->repositoryClassName !== null;
    }

    /**
     * If the class schema has a certain property.
     *
     * @param string $propertyName Name of the property
     * @return boolean
     */
    public function hasProperty($propertyName)
    {
        return array_key_exists($propertyName, $this->properties);
    }

    /**
     * If a certain class schema property is to be lazy loaded
     *
     * @param string $propertyName Name of the property
     * @return boolean
     */
    public function isPropertyLazy($propertyName)
    {
        return $this->properties[$propertyName]['lazy'];
    }

    /**
     * If a certain class schema property is to disregarded for persistence
     *
     * @param string $propertyName Name of the property
     * @return boolean
     */
    public function isPropertyTransient($propertyName)
    {
        return $this->properties[$propertyName]['transient'];
    }

    /**
     * Marks the given property as one of properties forming the identity
     * of an object. The property must already be registered in the class
     * schema.
     *
     * @param string $propertyName
     * @return void
     * @throws \InvalidArgumentException
     * @throws Exception\ClassSchemaConstraintViolationException
     */
    public function markAsIdentityProperty($propertyName)
    {
        if ($this->modelType === self::MODELTYPE_VALUEOBJECT) {
            throw new Exception\ClassSchemaConstraintViolationException('Value objects must not have identity properties', 1264102084);
        }
        if (!array_key_exists($propertyName, $this->properties)) {
            throw new \InvalidArgumentException('Property "' . $propertyName . '" must be added to the class schema before it can be marked as identity property.', 1233775407);
        }
        if ($this->properties[$propertyName]['lazy'] === true) {
            throw new \InvalidArgumentException('Property "' . $propertyName . '" must not be marked for lazy loading to be marked as identity property.', 1239896904);
        }

        $this->identityProperties[$propertyName] = $this->properties[$propertyName]['type'];
    }

    /**
     * Gets the properties (names and types) forming the identity of an object.
     *
     * @return array
     * @see markAsIdentityProperty()
     */
    public function getIdentityProperties()
    {
        return $this->identityProperties;
    }
}
