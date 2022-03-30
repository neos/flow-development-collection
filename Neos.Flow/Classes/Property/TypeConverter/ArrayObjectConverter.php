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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\Exception\InvalidSourceException;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;

/**
 * Converter which transforms ArrayObjects to arrays.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ArrayObjectConverter extends AbstractTypeConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = [\ArrayObject::class];

    /**
     * @var string
     */
    protected $targetType = 'array';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * Convert from $source to $targetType.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return array
     * @throws InvalidSourceException
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null): array
    {
        if (!($source instanceof \ArrayObject)) {
            throw new InvalidSourceException('Source was not an instance of ArrayObject.', 1648456200);
        }

        return $source->getArrayCopy();
    }
}
