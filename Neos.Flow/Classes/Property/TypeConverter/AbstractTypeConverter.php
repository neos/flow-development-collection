<?php
namespace Neos\Flow\Property\TypeConverter;

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
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverterInterface;

/**
 * Type converter which provides sensible default implementations for most methods. If you extend this class
 * you only need to do the following:
 *
 * - set $sourceTypes
 * - set $targetType
 * - set $priority
 * - implement convertFrom()
 *
 * @api
 * @Flow\Scope("singleton")
 */
abstract class AbstractTypeConverter implements TypeConverterInterface
{
    /**
     * The source types this converter can convert.
     *
     * @var array<string>
     * @api
     */
    protected $sourceTypes = [];

    /**
     * The target type this converter can convert to.
     *
     * @var string
     * @api
     */
    protected $targetType = '';

    /**
     * The priority for this converter.
     *
     * @var integer
     * @api
     */
    protected $priority;

    /**
     * Returns the list of source types the TypeConverter can handle.
     * Must be PHP simple types, classes or object is not allowed.
     *
     * @return array<string>
     * @api
     */
    public function getSupportedSourceTypes()
    {
        return $this->sourceTypes;
    }

    /**
     * Return the target type this TypeConverter converts to.
     * Can be a simple type or a class name.
     *
     * @return string
     * @api
     */
    public function getSupportedTargetType()
    {
        return $this->targetType;
    }

    /**
     * Returns the $originalTargetType unchanged in this implementation.
     *
     * @param mixed $source the source data
     * @param string $originalTargetType the type we originally want to convert to
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @api
     */
    public function getTargetTypeForSource($source, $originalTargetType, PropertyMappingConfigurationInterface $configuration = null)
    {
        return $originalTargetType;
    }

    /**
     * Return the priority of this TypeConverter. TypeConverters with a high priority are chosen before low priority.
     *
     * @return integer
     * @api
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * This implementation always returns TRUE for this method.
     *
     * @param mixed $source the source data
     * @param string $targetType the type to convert to.
     * @return boolean TRUE if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
     * @api
     */
    public function canConvertFrom($source, $targetType)
    {
        return true;
    }

    /**
     * Returns an empty list of sub property names
     *
     * @param mixed $source
     * @return array<string>
     * @api
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        return [];
    }

    /**
     * This method is never called, as getSourceChildPropertiesToBeConverted() returns an empty array.
     *
     * @param string $targetType
     * @param string $propertyName
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @api
     */
    public function getTypeOfChildProperty($targetType, $propertyName, PropertyMappingConfigurationInterface $configuration)
    {
    }
}
