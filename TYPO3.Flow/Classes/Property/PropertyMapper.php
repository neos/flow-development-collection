<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The Property Mapper transforms simple types (arrays, strings, integers, floats, booleans) to objects or other simple types.
 * It is used most prominently to map incoming HTTP arguments to objects.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 * @api
 */
class PropertyMapper {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Property\PropertyMappingConfigurationBuilder
	 */
	protected $configurationBuilder;

	/**
	 * A multi-dimensional array which stores the Type Converters available in the system.
	 * It has the following structure:
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
	 * @var \F3\FLOW3\Error\Result
	 */
	protected $messages;

	/**
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \F3\FLOW3\Property\PropertyMappingConfigurationBuilder $propertyMappingConfigurationBuilder
	 * @return void
	 */
	public function injectPropertyMappingConfigurationBuilder(\F3\FLOW3\Property\PropertyMappingConfigurationBuilder $propertyMappingConfigurationBuilder) {
		$this->configurationBuilder = $propertyMappingConfigurationBuilder;
	}

	/**
	 * Lifecycle method, called after all dependencies have been injected.
	 * Here, the typeConverter array gets initialized.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeObject() {
		foreach($this->reflectionService->getAllImplementationClassNamesForInterface('F3\FLOW3\Property\TypeConverterInterface') as $typeConverterClassName) {
			$typeConverter = $this->objectManager->get($typeConverterClassName);
			foreach ($typeConverter->getSupportedSourceTypes() as $supportedSourceType) {
				if (isset($this->typeConverters[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()])) {
					throw new \F3\FLOW3\Property\Exception\DuplicateTypeConverterException('There exist at least two converters which handle the conversion from "' . $supportedSourceType . '" to "' . $typeConverter->getSupportedTargetType() . '" with priority "' . $typeConverter->getPriority() . '": ' . get_class($this->typeConverters[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()]) . ' and ' . get_class($typeConverter), 1297951378);
				}
				$this->typeConverters[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()] = $typeConverter;
			}
		}
	}

	/**
	 * Map $source to $targetType, and return the result
	 *
	 * @param mixed $source the source data to map. MUST be a simple type, NO object allowed!
	 * @param string $targetType The type of the target; can be either a class name or a simple type.
	 * @param \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration Configuration for the property mapping. If NULL, the PropertyMappingConfigurationBuilder will create a default configuration.
	 * @return mixed an instance of $targetType
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function convert($source, $targetType, \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($configuration === NULL) {
			$configuration = $this->configurationBuilder->build();
		}

		$currentPropertyPath = array();
		$this->messages = new \F3\FLOW3\Error\Result();
		try {
			return $this->doMapping($source, $targetType, $configuration, $currentPropertyPath);
		} catch (\Exception $e) {
			throw new \F3\FLOW3\Property\Exception('Exception while property mapping at property path "' . implode('.', $currentPropertyPath) . '":' . $e->getMessage(), 1297759968, $e);
		}
	}

	/**
	 * Get the messages of the last Property Mapping
	 *
	 * @return \F3\FLOW3\Error\Result
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
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
	 * @param \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration Configuration for the property mapping.
	 * @param array $currentPropertyPath The property path currently being mapped; used for knowing the context in case an exception is thrown.
	 * @return mixed an instance of $targetType
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function doMapping($source, $targetType, \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration, &$currentPropertyPath) {
		$typeConverter = $this->findTypeConverter($source, $targetType, $configuration);

		if (!is_object($typeConverter) || !($typeConverter instanceof \F3\FLOW3\Property\TypeConverterInterface)) {
			throw new \F3\FLOW3\Property\Exception\TypeConverterException('Type converter for "' . $source . '" -> "' . $targetType . '" not found.');
		}

		$subProperties = array();
		foreach ($typeConverter->getProperties($source) as $sourcePropertyName => $sourcePropertyValue) {
			$targetPropertyName = $configuration->getTargetPropertyName($sourcePropertyName);
			if (!$configuration->shouldMap($targetPropertyName)) continue;

			$targetPropertyType = $typeConverter->getTypeOfProperty($targetType, $targetPropertyName);

			$subConfiguration = $configuration->getConfigurationFor($targetPropertyName);

			$currentPropertyPath[] = $targetPropertyName;
			$targetPropertyValue = $this->doMapping($sourcePropertyValue, $targetPropertyType, $subConfiguration, $currentPropertyPath);
			array_pop($currentPropertyPath);
			if ($targetPropertyValue !== NULL) {
				$subProperties[$targetPropertyName] = $targetPropertyValue;
			}
		}
		$result = $typeConverter->convertFrom($source, $targetType, $subProperties, $configuration);

		if ($result instanceof \F3\FLOW3\Error\Error) {
			$this->messages->forProperty(implode('.', $currentPropertyPath))->addError($result);
			$result = NULL;
		}

		return $result;
	}

	/**
	 * Determine the type converter to be used. If no converter has been found, an exception is raised.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \F3\FLOW3\Property\TypeConverterInterface Type Converter which should be used to convert between $source and $targetType.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function findTypeConverter($source, $targetType, \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration) {
		if ($configuration->getTypeConverter() !== NULL) return $configuration->getTypeConverter();

		$sourceType = $this->determineSourceType($source);

		if (!is_string($targetType)) {
			throw new \F3\FLOW3\Property\Exception\InvalidTargetException('The target type was no string, but of type "' . gettype($targetType) . '"', 1297941727);
		}
		$converter = NULL;

		if (\F3\FLOW3\Utility\TypeHandling::isSimpleType($targetType)) {
			if (isset($this->typeConverters[$sourceType][$targetType])) {
				$converter = $this->findEligibleConverterWithHighestPriority($this->typeConverters[$sourceType][$targetType], $source, $targetType);
			}
		} else {
			$converter = $this->findFirstEligibleTypeConverterInObjectHierarchy($source, $sourceType, $targetType);
		}

		if ($converter === NULL) {
			throw new \F3\FLOW3\Property\Exception\TypeConverterException('No converter found which can be used to convert from "' . $sourceType . '" to "' . $targetType . '".');
		}

		return $converter;
	}

	protected function findFirstEligibleTypeConverterInObjectHierarchy($source, $sourceType, $targetClass) {
		if (!class_exists($targetClass)) {
			throw new \F3\FLOW3\Property\Exception\InvalidTargetException('The target class "' . $targetClass . '" does not exist.', 1297948764);
		}

		$convertersForSource = $this->typeConverters[$sourceType];
		if (isset($convertersForSource[$targetClass])) {
			$converter = $this->findEligibleConverterWithHighestPriority($convertersForSource[$targetClass], $source, $targetClass);
			if ($converter !== NULL) {
				return $converter;
			}
		}

		foreach (class_parents($targetClass) as $parentClass) {
			if (!isset($convertersForSource[$parentClass])) continue;

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

	protected function findEligibleConverterWithHighestPriority($converters, $source, $targetType) {
		if (!is_array($converters)) return NULL;
		krsort($converters);
		reset($converters);
		foreach ($converters as $converter) {
			if ($converter->canConvert($source, $targetType)) {
				return $converter;
			}
		}
		return NULL;
	}

	protected function getConvertersForInterfaces($convertersForSource, $interfaceNames) {
		$convertersForInterface = array();
		foreach ($interfaceNames as $implementedInterface) {
			if (isset($convertersForSource[$implementedInterface])) {
				foreach ($convertersForSource[$implementedInterface] as $priority => $converter) {
					if (isset($convertersForInterface[$priority])) {
						throw new \F3\FLOW3\Property\Exception\DuplicateTypeConverterException('There exist at least two converters which handle the conversion to an interface with priority "' . $priority . '". ' . get_class($convertersForInterface[$priority]) . ' and ' . get_class($converter), 1297951338);
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
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
			throw new \F3\FLOW3\Property\Exception\InvalidSourceException('The source is not of type string, array, float, integer or boolean, but of type "' . gettype($source) . '"', 1297773150);
		}
	}
}
?>