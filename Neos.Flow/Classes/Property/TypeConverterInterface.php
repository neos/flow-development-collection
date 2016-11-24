<?php
namespace Neos\Flow\Property;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Error;

/**
 * Interface for type converters, which can convert from a simple type to an object or another simple type.
 *
 * All Type Converters should have NO INTERNAL STATE, such that they can be used as singletons and multiple times in succession (as this improves performance dramatically).
 *
 * @api
 */
interface TypeConverterInterface
{
    /**
     * Returns the list of source types the TypeConverter can handle.
     * Must be PHP simple types, classes or object is not allowed.
     *
     * @return array<string>
     * @api
     */
    public function getSupportedSourceTypes();

    /**
     * Return the target type this TypeConverter converts to.
     * Can be a simple type or a class name.
     *
     * @return string
     * @api
     */
    public function getSupportedTargetType();

    /**
     * Returns the type for a given source, depending on e.g. the __type setting or other properties.
     *
     * @param mixed $source the source data
     * @param string $originalTargetType the type we originally want to convert to
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @api
     */
    public function getTargetTypeForSource($source, $originalTargetType, PropertyMappingConfigurationInterface $configuration = null);

    /**
     * Return the priority of this TypeConverter. TypeConverters with a high priority are chosen before low priority.
     *
     * @return integer
     * @api
     */
    public function getPriority();

    /**
     * Here, the TypeConverter can do some additional runtime checks to see whether
     * it can handle the given source data and the given target type.
     *
     * @param mixed $source the source data
     * @param string $targetType the type to convert to.
     * @return boolean TRUE if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
     * @api
     */
    public function canConvertFrom($source, $targetType);

    /**
     * Return a list of sub-properties inside the source object.
     * The "key" is the sub-property name, and the "value" is the value of the sub-property.
     *
     * @param mixed $source
     * @return array<mixed>
     * @api
     */
    public function getSourceChildPropertiesToBeConverted($source);

    /**
     * Return the type of a given sub-property inside the $targetType
     *
     * @param string $targetType
     * @param string $propertyName
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string the type of $propertyName in $targetType
     * @api
     */
    public function getTypeOfChildProperty($targetType, $propertyName, PropertyMappingConfigurationInterface $configuration);

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * The return value can be one of three types:
     * - an arbitrary object, or a simple type (which has been created while mapping).
     *   This is the normal case.
     * - NULL, indicating that this object should *not* be mapped (i.e. a "File Upload" Converter could return NULL if no file has been uploaded, and a silent failure should occur.
     * - An instance of \Neos\Error\Messages\Error -- This will be a user-visible error message later on.
     * Furthermore, it should throw an Exception if an unexpected failure (like a security error) occurred or a configuration issue happened.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return mixed|Error the target type, or an error object if a user-error occurred
     * @throws Exception\TypeConverterException thrown in case a developer error occurred
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null);
}
