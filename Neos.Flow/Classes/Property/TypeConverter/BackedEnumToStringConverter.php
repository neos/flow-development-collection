<?php
declare(strict_types=1);

namespace Neos\Flow\Property\TypeConverter;

use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverterInterface;

class BackedEnumToStringConverter implements TypeConverterInterface
{
    public function getSupportedSourceTypes()
    {
        return ['object'];
    }

    public function getSupportedTargetType()
    {
        return 'string';
    }

    public function getTargetTypeForSource($source, $originalTargetType, PropertyMappingConfigurationInterface $configuration = null)
    {
        return gettype($source->value);
    }

    public function getPriority()
    {
        return 200;
    }

    public function canConvertFrom($source, $targetType)
    {
        /* @todo once the min php version is raised to 8.1 use BackedEnum::class */
        if (interface_exists("BackedEnum") && is_subclass_of($source, 'BackedEnum')) {
            return (gettype($source->value) === $targetType);
        }
        return false;
    }

    public function getSourceChildPropertiesToBeConverted($source)
    {
        return [];
    }

    public function getTypeOfChildProperty($targetType, $propertyName, PropertyMappingConfigurationInterface $configuration)
    {
        return '';
    }

    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        /* @todo once the min php version is raised to 8.1 use BackedEnum::class */
        if (interface_exists("BackedEnum") && is_subclass_of($source, 'BackedEnum')) {
            return $source->value;
        }
    }
}
