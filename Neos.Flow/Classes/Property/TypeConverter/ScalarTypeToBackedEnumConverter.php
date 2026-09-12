<?php

declare(strict_types=1);

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

use Neos\Flow\Property\PropertyMappingConfigurationInterface;

class ScalarTypeToBackedEnumConverter extends AbstractTypeConverter
{
    protected $sourceTypes = ['string', 'integer'];

    protected $targetType = \BackedEnum::class;

    public function canConvertFrom($source, $targetType): bool
    {
        if (is_a($targetType, \BackedEnum::class, true)) {
            $backingType = (new \ReflectionEnum($targetType))->getBackingType()?->getName();
            return (
                is_int($source) && $backingType === 'int'
                || is_string($source) && $backingType === 'string'
            ) && $targetType::tryFrom($source) instanceof $targetType;
        }
        return false;
    }

    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = [],
        ?PropertyMappingConfigurationInterface $configuration = null
    ) {
        return $targetType::from($source);
    }
}
