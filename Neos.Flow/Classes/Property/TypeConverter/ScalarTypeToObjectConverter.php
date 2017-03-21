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

use Doctrine\ORM\Mapping\Entity;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Reflection\ReflectionService;

/**
 * A type converter which converts a scalar type (string, boolean, float or integer) to an object by instantiating
 * the object and passing the string as the constructor argument.
 *
 * This converter will only be used if the target class has a constructor with exactly one argument whose type must
 * be the given type.
 *
 * @Flow\Scope("singleton")
 */
class ScalarTypeToObjectConverter extends AbstractTypeConverter
{
    /**
     * @var array
     */
    protected $sourceTypes = ['string', 'integer', 'float', 'bool'];

    /**
     * @var string
     */
    protected $targetType = 'object';

    /**
     * @var int
     */
    protected $priority = 10;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * Only convert if the given target class has a constructor with one argument being of type given type
     *
     * @param string $source
     * @param string $targetType
     * @return bool
     */
    public function canConvertFrom($source, $targetType)
    {
        if ((
            $this->reflectionService->isClassAnnotatedWith($targetType, Flow\Entity::class) ||
            $this->reflectionService->isClassAnnotatedWith($targetType, Flow\ValueObject::class) ||
            $this->reflectionService->isClassAnnotatedWith($targetType, Entity::class)
        ) === true) {
            return false;
        }

        $methodParameters = $this->reflectionService->getMethodParameters($targetType, '__construct');
        if (count($methodParameters) !== 1) {
            return false;
        }
        $methodParameter = array_shift($methodParameters);
        return $methodParameter['type'] === gettype($source);
    }

    /**
     * Convert the given simple type to an object
     *
     * @param string|float|integer|bool $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return object
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        return new $targetType($source);
    }
}
