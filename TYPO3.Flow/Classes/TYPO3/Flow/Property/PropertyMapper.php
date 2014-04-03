<?php
namespace TYPO3\Flow\Property;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\TypeHandling;

/**
 * The Property Mapper transforms simple types (arrays, strings, integers, floats, booleans) to objects or other simple types.
 * It is used most prominently to map incoming HTTP arguments to objects.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class PropertyMapper {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Property\PropertyMappingConfigurationBuilder
	 */
	protected $configurationBuilder;

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
	protected $typeConverters = array();

	/**
	 * A list of property mapping messages (errors, warnings) which have occured on last mapping.
	 * @var \TYPO3\Flow\Error\Result
	 */
	protected $messages;

	/**
	 * Lifecycle method, called after all dependencies have been injected.
	 * Here, the typeConverter array gets initialized.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Property\Exception\DuplicateTypeConverterException
	 */
	public function initializeObject() {
		$typeConverterClassNames = static::getTypeConverterImplementationClassNames($this->objectManager);
		foreach ($typeConverterClassNames as $typeConverterClassName) {
			$typeConverter = $this->objectManager->get($typeConverterClassName);
			foreach ($typeConverter->getSupportedSourceTypes() as $supportedSourceType) {
				if (isset($this->typeConverters[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()])) {
					throw new \TYPO3\Flow\Property\Exception\DuplicateTypeConverterException('There exist at least two converters which handle the conversion from "' . $supportedSourceType . '" to "' . $typeConverter->getSupportedTargetType() . '" with priority "' . $typeConverter->getPriority() . '": ' . get_class($this->typeConverters[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()]) . ' and ' . get_class($typeConverter), 1297951378);
				}
				$this->typeConverters[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()] = $typeConverter;
			}
		}
	}

	/**
	 * Returns all class names implementing the TypeConverterInterface.
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return array Array of type converter implementations
	 * @Flow\CompileStatic
	 */
	static public function getTypeConverterImplementationClassNames($objectManager) {
		$reflectionService = $objectManager->get('TYPO3\Flow\Reflection\ReflectionService');
		return $reflectionService->getAllImplementationClassNamesForInterface('TYPO3\Flow\Property\TypeConverterInterface');
	}

	/**
	 * Map $source to $targetType, and return the result.
	 *
	 * If $source is an object and already is of type $targetType, we do return the unmodified object.
	 *
	 * @param mixed $source the source data to map. MUST be a simple type, NO object allowed!
	 * @param string $targetType The type of the target; can be either a class name or a simple type.
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration Configuration for the property mapping. If NULL, the PropertyMappingConfigurationBuilder will create a default configuration.
	 * @return mixed an instance of $targetType
	 * @throws \TYPO3\Flow\Property\Exception
	 * @api
	 */
	public function convert($source, $targetType, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($configuration === NULL) {
			$configuration = $this->configurationBuilder->build();
		}

		$currentPropertyPath = array();
		$this->messages = new \TYPO3\Flow\Error\Result();
		try {
			$result = $this->doMapping($source, $targetType, $configuration, $currentPropertyPath);
			if ($result instanceof \TYPO3\Flow\Error\Error) {
				return NULL;
			}

			return $result;
		} catch (\TYPO3\Flow\Security\Exception $exception) {
			throw $exception;
		} catch (\Exception $exception) {
			throw new \TYPO3\Flow\Property\Exception('Exception while property mapping for target type "' . $targetType . '", at property path "' . implode('.', $currentPropertyPath) . '": ' . $exception->getMessage(), 1297759968, $exception);
		}
	}

	/**
	 * Get the messages of the last Property Mapping
	 *
	 * @return \TYPO3\Flow\Error\Result
	 * @api
	 */
	public function getMessages() {
		return $this->messages;
	}

	/**
	 * Internal function which actually does the property mapping.
	 *
	 * @param mixed $source the source data to map. MUST be a simple type, NO object allowed!
	 * @param string $targetType The type of the target; can be either a class name or a simple type.
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration Configuration for the property mapping.
	 * @param array $currentPropertyPath The property path currently being mapped; used for knowing the context in case an exception is thrown.
	 * @return mixed an instance of $targetType
	 * @throws \TYPO3\Flow\Property\Exception\TypeConverterException
	 * @throws \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	protected function doMapping($source, $targetType, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration, &$currentPropertyPath) {
		if (is_object($source)) {
			$targetType = $this->parseCompositeType($targetType);
			if ($source instanceof $targetType) {
				return $source;
			}
		}

		if ($source === NULL) {
			$source = '';
		}

		$typeConverter = $this->findTypeConverter($source, $targetType, $configuration);
		$targetType = $typeConverter->getTargetTypeForSource($source, $targetType, $configuration);

		if (!is_object($typeConverter) || !($typeConverter instanceof \TYPO3\Flow\Property\TypeConverterInterface)) {
			throw new Exception\TypeConverterException('Type converter for "' . $source . '" -> "' . $targetType . '" not found.');
		}

		$convertedChildProperties = array();
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
			if (!($targetPropertyValue instanceof \TYPO3\Flow\Error\Error)) {
				$convertedChildProperties[$targetPropertyName] = $targetPropertyValue;
			}
		}
		$result = $typeConverter->convertFrom($source, $targetType, $convertedChildProperties, $configuration);

		if ($result instanceof \TYPO3\Flow\Error\Error) {
			$this->messages->forProperty(implode('.', $currentPropertyPath))->addError($result);
		}

		return $result;
	}

	/**
	 * Determine the type converter to be used. If no converter has been found, an exception is raised.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \TYPO3\Flow\Property\TypeConverterInterface Type Converter which should be used to convert between $source and $targetType.
	 * @throws \TYPO3\Flow\Property\Exception\TypeConverterException
	 * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException
	 */
	protected function findTypeConverter($source, $targetType, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration) {
		if ($configuration->getTypeConverter() !== NULL) {
			return $configuration->getTypeConverter();
		}

		$sourceType = $this->determineSourceType($source);

		if (!is_string($targetType)) {
			throw new \TYPO3\Flow\Property\Exception\InvalidTargetException('The target type was no string, but of type "' . gettype($targetType) . '"', 1297941727);
		}
		$targetType = $this->parseCompositeType($targetType);
		$normalizedTargetType = TypeHandling::normalizeType($targetType);
		$converter = NULL;

		if (TypeHandling::isSimpleType($normalizedTargetType)) {
			if (isset($this->typeConverters[$sourceType][$normalizedTargetType])) {
				$converter = $this->findEligibleConverterWithHighestPriority($this->typeConverters[$sourceType][$normalizedTargetType], $source, $normalizedTargetType);
			}
		} else {
			$converter = $this->findFirstEligibleTypeConverterInObjectHierarchy($source, $sourceType, $normalizedTargetType);
		}

		if ($converter === NULL) {
			throw new \TYPO3\Flow\Property\Exception\TypeConverterException('No converter found which can be used to convert from "' . $sourceType . '" to "' . $normalizedTargetType . '".');
		}

		return $converter;
	}

	/**
	 * Tries to find a suitable type converter for the given source and target type.
	 *
	 * @param string $source The actual source value
	 * @param string $sourceType Type of the source to convert from
	 * @param string $targetClass Name of the target class to find a type converter for
	 * @return mixed Either the matching object converter or NULL
	 * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException
	 */
	protected function findFirstEligibleTypeConverterInObjectHierarchy($source, $sourceType, $targetClass) {
		if (!class_exists($targetClass) && !interface_exists($targetClass)) {
			throw new \TYPO3\Flow\Property\Exception\InvalidTargetException('Could not find a suitable type converter for "' . $targetClass . '" because no such class or interface exists.', 1297948764);
		}

		if (!isset($this->typeConverters[$sourceType])) {
			return NULL;
		}

		$convertersForSource = $this->typeConverters[$sourceType];
		if (isset($convertersForSource[$targetClass])) {
			$converter = $this->findEligibleConverterWithHighestPriority($convertersForSource[$targetClass], $source, $targetClass);
			if ($converter !== NULL) {
				return $converter;
			}
		}

		foreach (class_parents($targetClass) as $parentClass) {
			if (!isset($convertersForSource[$parentClass])) {
				continue;
			}

			$converter = $this->findEligibleConverterWithHighestPriority($convertersForSource[$parentClass], $source, $targetClass);
			if ($converter !== NULL) {
				return $converter;
			}
		}

		$converters = $this->getConvertersForInterfaces($convertersForSource, class_implements($targetClass));
		$converter = $this->findEligibleConverterWithHighestPriority($converters, $source, $targetClass);

		if ($converter !== NULL) {
			return $converter;
		}
		if (isset($convertersForSource['object'])) {
			return $this->findEligibleConverterWithHighestPriority($convertersForSource['object'], $source, $targetClass);
		} else {
			return NULL;
		}
	}

	/**
	 * @param mixed $converters
	 * @param mixed $source
	 * @param string $targetType
	 * @return mixed Either the matching object converter or NULL
	 */
	protected function findEligibleConverterWithHighestPriority($converters, $source, $targetType) {
		if (!is_array($converters)) {
			return NULL;
		}
		krsort($converters);
		reset($converters);
		foreach ($converters as $converter) {
			if ($converter->canConvertFrom($source, $targetType)) {
				return $converter;
			}
		}
		return NULL;
	}

	/**
	 * @param array $convertersForSource
	 * @param array $interfaceNames
	 * @return array
	 * @throws \TYPO3\Flow\Property\Exception\DuplicateTypeConverterException
	 */
	protected function getConvertersForInterfaces(array $convertersForSource, array $interfaceNames) {
		$convertersForInterface = array();
		foreach ($interfaceNames as $implementedInterface) {
			if (isset($convertersForSource[$implementedInterface])) {
				foreach ($convertersForSource[$implementedInterface] as $priority => $converter) {
					if (isset($convertersForInterface[$priority])) {
						throw new \TYPO3\Flow\Property\Exception\DuplicateTypeConverterException('There exist at least two converters which handle the conversion to an interface with priority "' . $priority . '". ' . get_class($convertersForInterface[$priority]) . ' and ' . get_class($converter), 1297951338);
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
	 * @return string the type of $source
	 * @throws \TYPO3\Flow\Property\Exception\InvalidSourceException
	 */
	protected function determineSourceType($source) {
		if (is_string($source)) {
			return 'string';
		} elseif (is_array($source)) {
			return 'array';
		} elseif (is_float($source)) {
			return 'float';
		} elseif (is_integer($source)) {
			return 'integer';
		} elseif (is_bool($source)) {
			return 'boolean';
		} else {
			throw new \TYPO3\Flow\Property\Exception\InvalidSourceException('The source is not of type string, array, float, integer or boolean, but of type "' . gettype($source) . '"', 1297773150);
		}
	}

	/**
	 * Parse a composite type like \Foo\Collection<\Bar\Entity> into
	 * \Foo\Collection
	 *
	 * @param string $compositeType
	 * @return string
	 */
	public function parseCompositeType($compositeType) {
		if (strpos($compositeType, '<') !== FALSE) {
			$compositeType = substr($compositeType, 0, strpos($compositeType, '<'));
		}
		return $compositeType;
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
	public function getTypeConverters() {
		return $this->typeConverters;
	}
}
