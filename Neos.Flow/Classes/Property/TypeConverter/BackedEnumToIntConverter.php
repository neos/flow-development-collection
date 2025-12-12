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

class BackedEnumToIntConverter extends AbstractTypeConverter
{
    protected $sourceTypes = [\BackedEnum::class];

    protected $targetType = 'integer';

    public function canConvertFrom($source, $targetType): bool
    {
        return is_int($source->value);
    }

    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = [],
        ?PropertyMappingConfigurationInterface $configuration = null
    ) {
        return $source->value;
    }
}
