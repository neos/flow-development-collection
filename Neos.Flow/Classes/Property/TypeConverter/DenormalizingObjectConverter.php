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

use Neos\Flow\Property\Exception\TypeConverterException;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverterInterface;

final class DenormalizingObjectConverter implements TypeConverterInterface
{
    /**
     * @return array<string>
     */
    public function getSupportedSourceTypes()
    {
        return ['array', 'string', 'bool', 'int', 'float'];
    }

    /**
     * @return string
     */
    public function getSupportedTargetType()
    {
        return 'object';
    }

    /**
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
        return 100;
    }

    /**
     * @param mixed $source the source data
     * @param string $targetType the type to convert to.
     * @return boolean true if this TypeConverter can convert from $source to $targetType, false otherwise.
     * @api
     */
    public function canConvertFrom($source, $targetType)
    {
        return self::canConvertFromSourceType(gettype($source), $targetType);
    }

    /**
     * @param string $sourceType
     * @param string $targetType
     * @return boolean
     */
    public static function canConvertFromSourceType(string $sourceType, string $targetType): bool
    {
        if (class_exists($targetType)) {
            switch ($sourceType) {
                case 'array':
                    return method_exists($targetType, 'fromArray');
                case 'string':
                    return method_exists($targetType, 'fromString');
                case 'bool':
                case 'boolean':
                    return method_exists($targetType, 'fromBool') || method_exists($targetType, 'fromBoolean');
                case 'int':
                case 'integer':
                    return method_exists($targetType, 'fromInt') || method_exists($targetType, 'fromInteger');
                case 'double':
                case 'float':
                    return method_exists($targetType, 'fromFloat');
                default:
                    break;
            }
        }

        return false;
    }

    /**
     * @param string $targetType
     * @return boolean
     */
    public static function isDenormalizable(string $targetType): bool
    {
        return self::canConvertFromSourceType('array', $targetType)
            || self::canConvertFromSourceType('string', $targetType)
            || self::canConvertFromSourceType('boolean', $targetType)
            || self::canConvertFromSourceType('integer', $targetType)
            || self::canConvertFromSourceType('double', $targetType)
        ;
    }

    /**
     * @param mixed $source
     * @return array<mixed>
     * @api
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        return [];
    }

    /**
     * @param string $targetType
     * @param string $propertyName
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string the type of $propertyName in $targetType
     * @api
     */
    public function getTypeOfChildProperty($targetType, $propertyName, PropertyMappingConfigurationInterface $configuration)
    {
        throw new \LogicException(self::class . '::getTypeOfChildProperty should never be called.');
    }

    /**
     * @param mixed $source
     * @param string $targetType
     * @param array<mixed> $convertedChildProperties
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return mixed
     * @throws TypeConverterException thrown in case a developer error occurred
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        return self::convertFromSource($source, $targetType);
    }

    /**
     * @param mixed $source
     * @param string $targetType
     * @return mixed
     * @throws TypeConverterException thrown in case a developer error occurred
     */
    public static function convertFromSource($source, string $targetType)
    {
        if (class_exists($targetType)) {
            switch (gettype($source)) {
                case 'array':
                    return $targetType::fromArray($source);
                case 'string':
                    return $targetType::fromString($source);
                case 'boolean':
                    return method_exists($targetType, 'fromBool') ? $targetType::fromBool($source) : $targetType::fromBoolean($source);
                case 'integer':
                    return method_exists($targetType, 'fromInt') ? $targetType::fromInt($source) : $targetType::fromInteger($source);
                case 'double':
                    return $targetType::fromFloat($source);
                default:
                    break;
            }
        }

        throw new TypeConverterException(
            sprintf(
                'Unable to convert "%s" to "%s"',
                gettype($source),
                $targetType
            ),
            1621322742
        );
    }
}
