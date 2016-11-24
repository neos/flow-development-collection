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

/**
 * Converter which transforms simple types to a boolean.
 *
 * For boolean this is a no-op, integer and float are simply typecast to boolean.
 *
 * Strings are converted to TRUE unless they are empry or match one of 'off', 'n', 'no', 'false' (case-insensitive).
 *
 * @api
 * @Flow\Scope("singleton")
 */
class BooleanConverter extends AbstractTypeConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = ['boolean', 'string', 'integer', 'float'];

    /**
     * @var string
     */
    protected $targetType = 'boolean';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * Actually convert from $source to $targetType
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return boolean
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        if (is_bool($source)) {
            return $source;
        }

        if (is_int($source) || is_float(($source))) {
            return (boolean)$source;
        }

        return (!empty($source) && !in_array(strtolower($source), ['off', 'n', 'no', 'false']));
    }
}
