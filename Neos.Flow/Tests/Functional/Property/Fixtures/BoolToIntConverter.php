<?php
namespace Neos\Flow\Tests\Functional\Property\Fixtures;

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
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;

class BoolToIntConverter extends AbstractTypeConverter
{
    /**
     * NOTE: Using the short version "bool" instead of "boolean" on purpose!
     *
     * @var array
     */
    protected $sourceTypes = ['bool'];

    /**
     * NOTE: Using the short version "int" instead of "integer" on purpose!
     *
     * @var string
     */
    protected $targetType = 'int';

    /**
     * @var int
     */
    protected $priority = 10;

    /**
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return bool|Error
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        return $source ? 42 : -42;
    }
}
