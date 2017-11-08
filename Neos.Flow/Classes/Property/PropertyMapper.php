<?php
namespace Neos\Flow\Property;

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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\Exception\DuplicateTypeConverterException;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\TypeHandling;
use Neos\Flow\Security\Exception as SecurityException;
use Neos\Flow\Property\Exception as PropertyException;

/**
 * The Property Mapper transforms simple types (arrays, strings, integers, floats, booleans) to objects or other simple types.
 * It is used most prominently to map incoming HTTP arguments to objects.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class PropertyMapper
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $cache;

    /**
     * A multi-dimensional array which stores the Type Converters available in the system.
     *
     * It has the following structure:
     *
     * 1. Dimension: Source Type
     * 2. Dimension: Target Type
     * 3. Dimension: Priority
     * Value: Type Converter instance
     *
     * @var array
     */
    protected $typeConverters = [];

    /**
     * A list of property mapping messages (errors, warnings) which have occured on last mapping.
     *
     * @var Result
     */
    protected $messages;

    /**
     * Lifecycle method, called after all dependencies have been injected.
     * Here, the typeConverter array gets initialized.
     *
     * @return void
     */
    public function initializeObject()
    {
        if ($this->cache->has('typeConverterMap')) {
            $this->typeConverters = $this->cache->get('typeConverterMap');
            return;
        }

        $this->typeConverters = $this->prepareTypeConverterMap();
        $this->cache->set('typeConverterMap', $this->typeConverters);
    }

    /**
     * Returns all class names implementing the TypeConverterInterface.
     *
     * @param ObjectManagerInterface $objectManager
     * @return array Array of type converter implementations
     * @Flow\CompileStatic
     */
    public static function getTypeConverterImplementationClassNames($objectManager)
    {
        $reflectionService = $objectManager->get(ReflectionService::class);

        return $reflectionService->getAllImplementationClassNamesForInterface(TypeConverterInterface::class);
    }

    /**
     * Map $source to $targetType, and return the result.
     *
     * If $source is an object and already is of type $targetType, we do return the unmodified object.
     *
     * @param mixed $source the source data to map. MUST be a simple type, NO object allowed!
     * @param string $targetType The type of the target; can be either a class name or a simple type.
     * @param PropertyMappingConfigurationInterface $configuration Configuration for the property mapping. If NULL, the PropertyMappingConfigurationBuilder will create a default configuration.
     * @return mixed an instance of $targetType
     * @throws Exception
     * @throws SecurityException
     * @api
     */
    public function convert($source, $targetType, PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            $configuration = $this->buildPropertyMappingConfiguration();
        }

        $currentPropertyPath = [];
        $this->messages = new Result();
        try {
            $result = $this->doMapping($source, $targetType, $configuration, $currentPropertyPath);
            if ($result instanceof Error) {
                return null;
            }

            return $result;
        } catch (SecurityException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new PropertyException('Exception while property mapping for target type "' . $targetType . '", at property path "' . implode('.', $currentPropertyPath) . '": ' . $exception->getMessage(), 1297759968, $exception);
        }
    }

    /**
     * Get the messages of the last Property Mapping
     *
     * @return Result
     * @api
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Internal function which actually does the property mapping.
     *
     * @param mixed $source the source data to map. MUST be a simple type, NO object allowed!
     * @param string $targetType The type of the target; can be either a class name or a simple type.
     * @param PropertyMappingConfigurationInterface $configuration Configuration for the property mapping.
     * @param array $currentPropertyPath The property path currently being mapped; used for knowing the context in case an exception is thrown.
     * @return mixed an instance of $targetType
     * @throws Exception\TypeConverterException
     * @throws Exception\InvalidPropertyMappingConfigurationException
     */
    protected function doMapping($source, $targetType, PropertyMappingConfigurationInterface $configuration, &$currentPropertyPath)
    {
        if (is_object($source)) {
            $targetClass = TypeHandling::truncateElementType($targetType);
            if ($source instanceof $targetClass) {
                return $source;
            }
        }

        if ($source === null) {
            $source = '';
        }

        $typeConverter = $this->findTypeConverter($source, $targetType, $configuration);
        $targetType = $typeConverter->getTargetTypeForSource($source, $targetType, $configuration);

        if (!is_object($typeConverter) || !($typeConverter instanceof TypeConverterInterface)) {
            throw new Exception\TypeConverterException('Type converter for "' . $source . '" -> "' . $targetType . '" not found.');
        }

        $convertedChildProperties = [];
        foreach ($typeConverter->getSourceChildPropertiesToBeConverted($source) as $sourcePropertyName => $sourcePropertyValue) {
            $targetPropertyName = $configuration->getTargetPropertyName($sourcePropertyName);
            if ($configuration->shouldSkip($targetPropertyName)) {
                continue;
            }

            if (!$configuration->shouldMap($targetPropertyName)) {
                if ($configuration->shouldSkipUnknownProperties()) {
                    continue;
                }
                throw new Exception\InvalidPropertyMappingConfigurationException('It is not allowed to map property "' . $targetPropertyName . '". You need to use $propertyMappingConfiguration->allowProperties(\'' . $targetPropertyName . '\') to enable mapping of this property.', 1335969887);
            }

            $targetPropertyType = $typeConverter->getTypeOfChildProperty($targetType, $targetPropertyName, $configuration);

            $subConfiguration = $configuration->getConfigurationFor($targetPropertyName);

            $currentPropertyPath[] = $targetPropertyName;
            $targetPropertyValue = $this->doMapping($sourcePropertyValue, $targetPropertyType, $subConfiguration, $currentPropertyPath);
            array_pop($currentPropertyPath);
            if (!($targetPropertyValue instanceof Error)) {
                $convertedChildProperties[$targetPropertyName] = $targetPropertyValue;
            }
        }
        $result = $typeConverter->convertFrom($source, $targetType, $convertedChildProperties, $configuration);

        if ($result instanceof Error) {
            $this->messages->forProperty(implode('.', $currentPropertyPath))->addError($result);
        }

        return $result;
    }

    /**
     * Determine the type converter to be used. If no converter has been found, an exception is raised.
     *
     * @param mixed $source
     * @param string $targetType
     * @param PropertyMappingConfigurationInterface $configuration
     * @return TypeConverterInterface Type Converter which should be used to convert between $source and $targetType.
     * @throws Exception\TypeConverterException
     * @throws Exception\InvalidTargetException
     */
    protected function findTypeConverter($source, $targetType, PropertyMappingConfigurationInterface $configuration)
    {
        if ($configuration->getTypeConverter() !== null) {
            return $configuration->getTypeConverter();
        }

        if (!is_string($targetType)) {
            throw new Exception\InvalidTargetException('The target type was no string, but of type "' . gettype($targetType) . '"', 1297941727);
        }
        $normalizedTargetType = TypeHandling::normalizeType($targetType);
        $truncatedTargetType = TypeHandling::truncateElementType($normalizedTargetType);
        $converter = null;

        $sourceTypes = $this->determineSourceTypes($source);
        foreach ($sourceTypes as $sourceType) {
            if (TypeHandling::isSimpleType($truncatedTargetType)) {
                if (isset($this->typeConverters[$sourceType][$truncatedTargetType])) {
                    $converter = $this->findEligibleConverterWithHighestPriority($this->typeConverters[$sourceType][$truncatedTargetType], $source, $normalizedTargetType);
                }
            } else {
                $converter = $this->findFirstEligibleTypeConverterInObjectHierarchy($source, $sourceType, $normalizedTargetType);
            }

            if ($converter !== null) {
                return $converter;
            }
        }

        throw new Exception\TypeConverterException('No converter found which can be used to convert from "' . implode('" or "', $sourceTypes) . '" to "' . $normalizedTargetType . '".');
    }

    /**
     * Tries to find a suitable type converter for the given source and target type.
     *
     * @param string $source The actual source value
     * @param string $sourceType Type of the source to convert from
     * @param string $targetType Name of the target type to find a type converter for
     * @return mixed Either the matching object converter or NULL
     * @throws Exception\InvalidTargetException
     */
    protected function findFirstEligibleTypeConverterInObjectHierarchy($source, $sourceType, $targetType)
    {
        $targetClass = TypeHandling::truncateElementType($targetType);
        if (!class_exists($targetClass) && !interface_exists($targetClass)) {
            throw new Exception\InvalidTargetException(sprintf('Could not find a suitable type converter for "%s" because no such the class/interface "%s" does not exist.', $targetType, $targetClass), 1297948764);
        }

        if (!isset($this->typeConverters[$sourceType])) {
            return null;
        }

        $convertersForSource = $this->typeConverters[$sourceType];
        if (isset($convertersForSource[$targetClass])) {
            $converter = $this->findEligibleConverterWithHighestPriority($convertersForSource[$targetClass], $source, $targetType);
            if ($converter !== null) {
                return $converter;
            }
        }

        foreach (class_parents($targetClass) as $parentClass) {
            if (!isset($convertersForSource[$parentClass])) {
                continue;
            }

            $converter = $this->findEligibleConverterWithHighestPriority($convertersForSource[$parentClass], $source, $targetType);
            if ($converter !== null) {
                return $converter;
            }
        }

        $converters = $this->getConvertersForInterfaces($convertersForSource, class_implements($targetClass));
        $converter = $this->findEligibleConverterWithHighestPriority($converters, $source, $targetType);

        if ($converter !== null) {
            return $converter;
        }
        if (isset($convertersForSource['object'])) {
            return $this->findEligibleConverterWithHighestPriority($convertersForSource['object'], $source, $targetType);
        } else {
            return null;
        }
    }

    /**
     * @param mixed $converters
     * @param mixed $source
     * @param string $targetType
     * @return mixed Either the matching object converter or NULL
     */
    protected function findEligibleConverterWithHighestPriority($converters, $source, $targetType)
    {
        if (!is_array($converters)) {
            return null;
        }
        krsort($converters);
        reset($converters);
        foreach ($converters as $converter) {
            if (is_string($converter)) {
                $converter = $this->objectManager->get($converter);
            }

            /** @var TypeConverterInterface $converter */
            if ($converter->getPriority() < 0) {
                continue;
            }

            if ($converter->canConvertFrom($source, $targetType)) {
                return $converter;
            }
        }

        return null;
    }

    /**
     * @param array $convertersForSource
     * @param array $interfaceNames
     * @return array
     * @throws DuplicateTypeConverterException
     */
    protected function getConvertersForInterfaces(array $convertersForSource, array $interfaceNames)
    {
        $convertersForInterface = [];
        foreach ($interfaceNames as $implementedInterface) {
            if (isset($convertersForSource[$implementedInterface])) {
                foreach ($convertersForSource[$implementedInterface] as $priority => $converter) {
                    if (isset($convertersForInterface[$priority])) {
                        throw new DuplicateTypeConverterException('There exist at least two converters which handle the conversion to an interface with priority "' . $priority . '". ' . get_class($convertersForInterface[$priority]) . ' and ' . get_class($converter), 1297951338);
                    }
                    $convertersForInterface[$priority] = $converter;
                }
            }
        }

        return $convertersForInterface;
    }

    /**
     * Determine the type of the source data, or throw an exception if source was an unsupported format.
     *
     * @param mixed $source
     * @return array Possible source types (single value for simple typed source, multiple values for object source)
     * @throws Exception\InvalidSourceException
     */
    protected function determineSourceTypes($source)
    {
        if (is_string($source)) {
            return ['string'];
        } elseif (is_array($source)) {
            return ['array'];
        } elseif (is_float($source)) {
            return ['float'];
        } elseif (is_integer($source)) {
            return ['integer'];
        } elseif (is_bool($source)) {
            return ['boolean'];
        } elseif (is_object($source)) {
            $class = get_class($source);
            $parentClasses = class_parents($class);
            $interfaces = class_implements($class);

            return array_merge([$class], $parentClasses, $interfaces, ['object']);
        } else {
            throw new Exception\InvalidSourceException('The source is not of type string, array, float, integer, boolean or object, but of type "' . gettype($source) . '"', 1297773150);
        }
    }

    /**
     * Collects all TypeConverter implementations in a multi-dimensional array with source and target types.
     *
     * @return array
     * @throws Exception\DuplicateTypeConverterException
     * @see getTypeConverters
     */
    protected function prepareTypeConverterMap()
    {
        $typeConverterMap = [];
        $typeConverterClassNames = static::getTypeConverterImplementationClassNames($this->objectManager);
        foreach ($typeConverterClassNames as $typeConverterClassName) {
            $typeConverter = $this->objectManager->get($typeConverterClassName);
            foreach ($typeConverter->getSupportedSourceTypes() as $supportedSourceType) {
                if (isset($typeConverterMap[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()])) {
                    throw new Exception\DuplicateTypeConverterException('There exist at least two converters which handle the conversion from "' . $supportedSourceType . '" to "' . $typeConverter->getSupportedTargetType() . '" with priority "' . $typeConverter->getPriority() . '": ' . $typeConverterMap[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()] . ' and ' . get_class($typeConverter), 1297951378);
                }
                $typeConverterMap[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()] = $typeConverterClassName;
            }
        }

        return $typeConverterMap;
    }

    /**
     * Returns a multi-dimensional array with the Type Converters available in the system.
     *
     * It has the following structure:
     *
     * 1. Dimension: Source Type
     * 2. Dimension: Target Type
     * 3. Dimension: Priority
     * Value: Type Converter instance
     *
     * @return array
     */
    public function getTypeConverters()
    {
        return $this->typeConverters;
    }

    /**
     * Builds the default property mapping configuration.
     *
     * @param string $type the implementation class name of the PropertyMappingConfiguration to instantiate; must be a subclass of Neos\Flow\Property\PropertyMappingConfiguration
     * @return PropertyMappingConfiguration
     */
    public function buildPropertyMappingConfiguration($type = PropertyMappingConfiguration::class)
    {
        /** @var PropertyMappingConfiguration $configuration */
        $configuration = new $type();

        $configuration->setTypeConverterOptions(TypeConverter\PersistentObjectConverter::class, [
            TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
            TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true
        ]);
        $configuration->allowAllProperties();

        return $configuration;
    }
}
