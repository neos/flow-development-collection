<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property\TypeConverter;

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
 * This converter transforms arrays to simple objects (POPO) by setting properties.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope singleton
 */
class ArrayToObjectConverter extends \F3\FLOW3\Property\TypeConverter\AbstractTypeConverter {

	/**
	 * @var integer
	 */
	const CONFIGURATION_TARGET_TYPE = 3;

	/**
	 * @var array
	 */
	protected $sourceTypes = array('array');

	/**
	 * @var strng
	 */
	protected $targetType = 'object';

	/**
	 * @var integer
	 */
	protected $priority = 0;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \F3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Only convert non-persistent types
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @return boolean
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function canConvert($source, $targetType) {
		$isValueObject = $this->reflectionService->isClassTaggedWith($targetType, 'valueobject');
		$isEntity = $this->reflectionService->isClassTaggedWith($targetType, 'entity');
		return !($isEntity || $isValueObject);
	}

	/**
	 * Convert all properties in the source array
	 *
	 * @param mixed $source
	 * @return array
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getProperties($source) {
		return $source;
	}

	/**
	 * The type of a property is determined by the reflection service.
	 *
	 * @param string $targetType
	 * @param string $propertyName
	 * @param \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTypeOfProperty($targetType, $propertyName, \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration) {
		$configuredTargetType = $configuration->getConfigurationFor($propertyName)->getConfigurationValue('F3\FLOW3\Property\TypeConverter\ArrayToObjectConverter', self::CONFIGURATION_TARGET_TYPE);
		if ($configuredTargetType !== NULL) {
			return $configuredTargetType;
		}

		if ($this->reflectionService->hasMethod($targetType, \F3\FLOW3\Reflection\ObjectAccess::buildSetterMethodName($propertyName))) {
			$methodParameters = $this->reflectionService->getMethodParameters($targetType, \F3\FLOW3\Reflection\ObjectAccess::buildSetterMethodName($propertyName));
			$methodParameter = current($methodParameters);
			if (!isset($methodParameter['type'])) {
				throw new \F3\FLOW3\Property\Exception\InvalidTargetException('Setter for property "' . $propertyName . '" had no type hint or documentation in target object of type "' . $targetType . '".', 1303379158);
			} else {
				return $methodParameter['type'];
			}
		} else {
			throw new \F3\FLOW3\Property\Exception\InvalidTargetException('Property "' . $propertyName . '" had no setter in target object of type "' . $targetType . '".', 1303379126);
		}
	}

	/**
	 * Convert an object from $source to an object.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $subProperties
	 * @param \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return object the target type
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFrom($source, $targetType, array $subProperties = array(), \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$object = $this->buildObject($subProperties, $targetType);

		foreach ($subProperties as $propertyName => $propertyValue) {
			$result = \F3\FLOW3\Reflection\ObjectAccess::setProperty($object, $propertyName, $propertyValue);
			if ($result === FALSE) {
				throw new \F3\FLOW3\Property\Exception\InvalidTargetException('Property "' . $propertyName . '" could not be set in target object of type "' . $targetType . '".', 1304538165);
			}
		}

		return $object;
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
	 * @throws \F3\FLOW3\Property\Exception\InvalidTargetException if a required constructor argument is missing
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function buildObject(array &$possibleConstructorArgumentValues, $objectType) {
		$constructorSignature = $this->reflectionService->getMethodParameters($objectType, '__construct');
		$constructorArguments = array();
		foreach ($constructorSignature as $constructorArgumentName => $constructorArgumentInformation) {
			if (array_key_exists($constructorArgumentName, $possibleConstructorArgumentValues)) {
				$constructorArguments[] = $possibleConstructorArgumentValues[$constructorArgumentName];
				unset($possibleConstructorArgumentValues[$constructorArgumentName]);
			} elseif ($constructorArgumentInformation['optional'] === TRUE) {
				$constructorArguments[] = $constructorArgumentInformation['defaultValue'];
			} else {
				throw new \F3\FLOW3\Property\Exception\InvalidTargetException('Missing constructor argument "' . $constructorArgumentName . '" for object of type "' . $objectType . '".' , 1304538168);
			}
		}
		return call_user_func_array(array($this->objectManager, 'create'), array_merge(array($objectType), $constructorArguments));
	}

}
?>