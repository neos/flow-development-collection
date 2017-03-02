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
use Neos\Flow\Http\Uri;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;

/**
 * A type converter for converting URI strings to Http Uri objects.
 *
 * This converter simply creates a Neos\Flow\Http\Uri instance from the source string.
 *
 * @Flow\Scope("singleton")
 */
class UriTypeConverter extends AbstractTypeConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = ['string'];

    /**
     * @var string
     */
    protected $targetType = Uri::class;

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * Converts the given string to a Uri object.
     *
     * @param string $source The URI to be converted
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return Uri|Error if the input format is not supported or could not be converted for other reasons
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        try {
            return new Uri($source);
        } catch (\InvalidArgumentException $exception) {
            return new Error('The given URI "%s" could not be converted', 1351594881, [$source]);
        }
    }
}
