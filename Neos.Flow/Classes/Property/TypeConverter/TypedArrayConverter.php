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
use Neos\Utility\TypeHandling;

/**
 * Converter which recursively transforms typed arrays (array<T>).
 *
 * This is a meta converter that will take an array and try to transform all elements in that array to
 * the element type <T> of the target array using an available type converter.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class TypedArrayConverter extends AbstractTypeConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = ['array'];

    /**
     * @var string
     */
    protected $targetType = 'array';

    /**
     * @var integer
     */
    protected $priority = 2;

    /**
     * @param mixed $source
     * @param string $targetType
     * @return boolean
     */
    public function canConvertFrom($source, $targetType)
    {
        $targetTypeInformation = TypeHandling::parseType($targetType);
        if ($targetTypeInformation['type'] !== 'array') {
            return false;
        }
        return $targetTypeInformation['elementType'] !== null;
    }

    /**
     * @param array $source An array of objects/simple types
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return array
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        return $convertedChildProperties;
    }

    /**
     * Returns the source, if it is an array, otherwise an empty array.
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        return is_array($source) ? $source : [];
    }

    /**
     * Return the type of a given sub-property inside the $targetType
     *
     * @param string $targetType
     * @param string $propertyName
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     */
    public function getTypeOfChildProperty($targetType, $propertyName, PropertyMappingConfigurationInterface $configuration)
    {
        $parsedTargetType = TypeHandling::parseType($targetType);
        return $parsedTargetType['elementType'];
    }
}
