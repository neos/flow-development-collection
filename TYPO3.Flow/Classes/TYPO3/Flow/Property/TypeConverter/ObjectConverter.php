<?php
namespace TYPO3\Flow\Property\TypeConverter;

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
use TYPO3\Flow\Property\Exception\InvalidDataTypeException;
use TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException;
use TYPO3\Flow\Property\Exception\InvalidTargetException;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * This converter transforms arrays to simple objects (POPO) by setting properties.
 *
 * This converter will only be used on target types that are not entities or value objects (for those the
 * PersistentObjectConverter is used).
 *
 * The target type can be overridden in the source by setting the __type key to the desired value.
 *
 * The converter will return an instance of the target type with all properties given in the source array set to
 * the respective values. For the mechanics used to set the values see ObjectAccess::setProperty().
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ObjectConverter extends AbstractTypeConverter {

	/**
	 * @var integer
	 */
	const CONFIGURATION_TARGET_TYPE = 3;

	/**
	 * @var integer
	 */
	const CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED = 4;

	/**
	 * @var array
	 */
	protected $sourceTypes = array('array');

	/**
	 * @var string
	 */
	protected $targetType = 'object';

	/**
	 * @var integer
	 */
	protected $priority = 0;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * As it is very likely that the constructor arguments are needed twice we should cache them for the request.
	 *
	 * @var array
	 */
	protected $constructorReflectionFirstLevelCache = array();

	/**
	 * Only convert non-persistent types
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @return boolean
	 */
	public function canConvertFrom($source, $targetType) {
		return !(
			$this->reflectionService->isClassAnnotatedWith($targetType, 'TYPO3\Flow\Annotations\Entity') ||
			$this->reflectionService->isClassAnnotatedWith($targetType, 'TYPO3\Flow\Annotations\ValueObject') ||
			$this->reflectionService->isClassAnnotatedWith($targetType, 'Doctrine\ORM\Mapping\Entity')
		);
	}

	/**
	 * Convert all properties in the source array
	 *
	 * @param mixed $source
	 * @return array
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
		if (isset($source['__type'])) {
			unset($source['__type']);
		}
		return $source;
	}

	/**
	 * The type of a property is determined by the reflection service.
	 *
	 * @param string $targetType
	 * @param string $propertyName
	 * @param PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @throws InvalidTargetException
	 */
	public function getTypeOfChildProperty($targetType, $propertyName, PropertyMappingConfigurationInterface $configuration) {
		$configuredTargetType = $configuration->getConfigurationFor($propertyName)->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\ObjectConverter', self::CONFIGURATION_TARGET_TYPE);
		if ($configuredTargetType !== NULL) {
			return $configuredTargetType;
		}

		$methodParameters = $this->reflectionService->getMethodParameters($targetType, '__construct');
		if (isset($methodParameters[$propertyName]) && isset($methodParameters[$propertyName]['type'])) {
			return $methodParameters[$propertyName]['type'];
		} elseif ($this->reflectionService->hasMethod($targetType, ObjectAccess::buildSetterMethodName($propertyName))) {
			$methodParameters = $this->reflectionService->getMethodParameters($targetType, ObjectAccess::buildSetterMethodName($propertyName));
			$methodParameter = current($methodParameters);
			if (!isset($methodParameter['type'])) {
				throw new InvalidTargetException('Setter for property "' . $propertyName . '" had no type hint or documentation in target object of type "' . $targetType . '".', 1303379158);
			} else {
				return $methodParameter['type'];
			}
		} else {
			$targetPropertyNames = $this->reflectionService->getClassPropertyNames($targetType);
			if (in_array($propertyName, $targetPropertyNames)) {
				$values = $this->reflectionService->getPropertyTagValues($targetType, $propertyName, 'var');
				if (count($values) > 0) {
					return current($values);
				} else {
					throw new InvalidTargetException(sprintf('Public property "%s" had no proper type annotation (i.e. "@var") in target object of type "%s".', $propertyName, $targetType), 1406821818);
				}
			}
		}
		throw new InvalidTargetException(sprintf('Property "%s" has neither a setter or constructor argument, nor is public, in target object of type "%s".', $propertyName, $targetType), 1303379126);
	}

	/**
	 * Convert an object from $source to an object.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param PropertyMappingConfigurationInterface $configuration
	 * @return object the target type
	 * @throws InvalidTargetException
	 * @throws InvalidDataTypeException
	 * @throws InvalidPropertyMappingConfigurationException
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), PropertyMappingConfigurationInterface $configuration = NULL) {
		$object = $this->buildObject($convertedChildProperties, $targetType);
		foreach ($convertedChildProperties as $propertyName => $propertyValue) {
			$result = ObjectAccess::setProperty($object, $propertyName, $propertyValue);
			if ($result === FALSE) {
				$exceptionMessage = sprintf(
					'Property "%s" having a value of type "%s" could not be set in target object of type "%s". Make sure that the property is accessible properly, for example via an appropriate setter method.',
					$propertyName,
					(is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue)),
					$targetType
				);
				throw new InvalidTargetException($exceptionMessage, 1304538165);
			}
		}

		return $object;
	}

	/**
	 * Determines the target type based on the source's (optional) __type key.
	 *
	 * @param mixed $source
	 * @param string $originalTargetType
	 * @param PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @throws InvalidDataTypeException
	 * @throws InvalidPropertyMappingConfigurationException
	 * @throws \InvalidArgumentException
	 */
	public function getTargetTypeForSource($source, $originalTargetType, PropertyMappingConfigurationInterface $configuration = NULL) {
		$targetType = $originalTargetType;

		if (is_array($source) && array_key_exists('__type', $source)) {
			$targetType = $source['__type'];

			if ($configuration === NULL) {
				throw new \InvalidArgumentException('A property mapping configuration must be given, not NULL.', 1326277369);
			}
			if ($configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\ObjectConverter', self::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED) !== TRUE) {
				throw new InvalidPropertyMappingConfigurationException('Override of target type not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED" to TRUE.', 1317050430);
			}

			if ($targetType !== $originalTargetType && is_a($targetType, $originalTargetType, TRUE) === FALSE) {
				throw new InvalidDataTypeException('The given type "' . $targetType . '" is not a subtype of "' . $originalTargetType . '".', 1317048056);
			}
		}

		return $targetType;
	}

	/**
	 * Builds a new instance of $objectType with the given $possibleConstructorArgumentValues.
	 * If constructor argument values are missing from the given array the method looks for a
	 * default value in the constructor signature.
	 *
	 * Furthermore, the constructor arguments are removed from $possibleConstructorArgumentValues
	 *
	 * @param array &$possibleConstructorArgumentValues
	 * @param string $objectType
	 * @return object The created instance
	 * @throws InvalidTargetException if a required constructor argument is missing
	 */
	protected function buildObject(array &$possibleConstructorArgumentValues, $objectType) {
		$constructorArguments = array();
		$className = $this->objectManager->getClassNameByObjectName($objectType);
		$constructorSignature = $this->getConstructorArgumentsForClass($className);
		if (count($constructorSignature)) {
			foreach ($constructorSignature as $constructorArgumentName => $constructorArgumentReflection) {
				if (array_key_exists($constructorArgumentName, $possibleConstructorArgumentValues)) {
					$constructorArguments[] = $possibleConstructorArgumentValues[$constructorArgumentName];
					unset($possibleConstructorArgumentValues[$constructorArgumentName]);
				} elseif ($constructorArgumentReflection['optional'] === TRUE) {
					$constructorArguments[] = $constructorArgumentReflection['defaultValue'];
				} else {
					throw new InvalidTargetException('Missing constructor argument "' . $constructorArgumentName . '" for object of type "' . $objectType . '".', 1268734872);
				}
			}
			$classReflection = new \ReflectionClass($className);
			return $classReflection->newInstanceArgs($constructorArguments);
		} else {
			return new $className();
		}
	}

	/**
	 * Get the constructor argument reflection for the given object type.
	 *
	 * @param string $className
	 * @return array<array>
	 */
	protected function getConstructorArgumentsForClass($className) {
		if (!isset($this->constructorReflectionFirstLevelCache[$className])) {
			$constructorSignature = array();

			// TODO: Check if we can get rid of this reflection service usage, directly reflecting doesn't work as the proxy class __construct has no arguments.
			if ($this->reflectionService->hasMethod($className, '__construct')) {
				$constructorSignature = $this->reflectionService->getMethodParameters($className, '__construct');
			}

			$this->constructorReflectionFirstLevelCache[$className] = $constructorSignature;
		}

		return $this->constructorReflectionFirstLevelCache[$className];
	}

}
