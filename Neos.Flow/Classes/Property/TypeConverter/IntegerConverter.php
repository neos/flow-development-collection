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
use Neos\Error\Messages\Error;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;

/**
 * Converter which transforms to an integer.
 *
 * * If the source is an integer, it is returned unchanged.
 * * If the source a numeric string, it is cast to integer
 * * If the source is a DateTime instance, the UNIX timestamp is returned
 *
 * @api
 * @Flow\Scope("singleton")
 */
class IntegerConverter extends AbstractTypeConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = ['integer', 'string', 'DateTime'];

    /**
     * @var string
     */
    protected $targetType = 'integer';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * Actually convert from $source to $targetType, in fact a noop here.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return integer|Error
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($source instanceof \DateTimeInterface) {
            return $source->format('U');
        }

        if ($source === null || strlen($source) === 0) {
            return null;
        }

        if (!is_numeric($source)) {
            return new Error('"%s" is not numeric.', 1332933658, [$source]);
        }
        return (integer)$source;
    }
}
