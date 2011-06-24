<?php
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
 * This converter transforms arrays or strings to persistent objects. It does the following:
 *
 * - If the input is string, it is assumed to be a UUID. Then, the object is fetched from persistence.
 * - If the input is array, we check if it has an identity property.
 *
 * - If the input has an identity property and NO additional properties, we fetch the object from persistence.
 * - If the input has an identity property AND additional properties, we fetch the object from persistence,
 *   create a clone on it, and set the sub-properties. We only do this if the configuration option "CONFIGURATION_MODIFICATION_ALLOWED" is TRUE.
 * - If the input has NO identity property, but additional properties, we create a new object and return it.
 *   However, we only do this if the configuration option "CONFIGURATION_CREATION_ALLOWED" is TRUE.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope singleton
 */
class PersistentObjectConverter extends \F3\FLOW3\Property\TypeConverter\AbstractTypeConverter {

	const PATTERN_MATCH_UUID = '/([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}/';
	const CONFIGURATION_MODIFICATION_ALLOWED = 1;
	const CONFIGURATION_CREATION_ALLOWED = 2;
	const CONFIGURATION_TARGET_TYPE = 3;

	/**
	 * @var array
	 */
	protected $sourceTypes = array('string', 'array');

	/**
	 * @var strng
	 */
	protected $targetType = 'object';

	/**
	 * @var integer
	 */
	protected $priority = 1;

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
	 * We can only convert if the $targetType is either tagged with entity or value object.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @return boolean
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function canConvertFrom($source, $targetType) {
		$isValueObject = $this->reflectionService->isClassTaggedWith($targetType, 'valueobject');
		$isEntity = $this->reflectionService->isClassTaggedWith($targetType, 'entity');
		return ($isEntity || $isValueObject);
	}

	/**
	 * All properties in the source array except __identity are sub-properties.
	 *
	 * @param mixed $source
	 * @return array
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
		if (is_string($source)) {
			return array();
		}
		if (isset($source['__identity'])) {
			unset($source['__identity']);
		}
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
	public function getTypeOfChildProperty($targetType, $propertyName, \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration) {
		$configuredTargetType = $configuration->getConfigurationFor($propertyName)->getConfigurationValue('F3\FLOW3\Property\TypeConverter\PersistentObjectConverter', self::CONFIGURATION_TARGET_TYPE);
		if ($configuredTargetType !== NULL) {
			return $configuredTargetType;
		}

		$schema = $this->reflectionService->getClassSchema($targetType);
		if (!$schema->hasProperty($propertyName)) {
			throw new \F3\FLOW3\Property\Exception\InvalidTargetException('Property "' . $propertyName . '" was not found in target object of type "' . $targetType . '".', 1297978366);
		}
		$propertyInformation = $schema->getProperty($propertyName);
		return $propertyInformation['type'] . ($propertyInformation['elementType']!==NULL ? '<' . $propertyInformation['elementType'] . '>' : '');
	}

	/**
	 * Convert an object from $source to an entity or a value object.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return object the target type
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if (is_array($source)) {
			$object = $this->handleArrayData($source, $targetType, $convertedChildProperties, $configuration);
		} elseif (is_string($source)) {
			$object = $this->fetchObjectFromPersistence($source, $targetType);
		} else {
			throw new \InvalidArgumentException('Only strings and arrays are accepted.', 1305630314);
		}
		foreach ($convertedChildProperties as $propertyName => $propertyValue) {
			$result = \F3\FLOW3\Reflection\ObjectAccess::setProperty($object, $propertyName, $propertyValue);
			if ($result === FALSE) {
				throw new \F3\FLOW3\Property\Exception\InvalidTargetException('Property "' . $propertyName . '" could not be set in target object of type "' . $targetType . '".', 1297935345);
			}
		}

		return $object;
	}

	/**
	 * Handle the case if $source is an array.
	 *
	 * @param array $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return object
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function handleArrayData(array $source, $targetType, array &$convertedChildProperties, \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if (isset($source['__identity'])) {
			$object = $this->fetchObjectFromPersistence($source['__identity'], $targetType);

			if (count($source) > 1) {
				if ($configuration === NULL || $configuration->getConfigurationValue('F3\FLOW3\Property\TypeConverter\PersistentObjectConverter', self::CONFIGURATION_MODIFICATION_ALLOWED) !== TRUE) {
					throw new \F3\FLOW3\Property\Exception\InvalidPropertyMappingConfigurationException('Modification of persistent objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_MODIFICATION_ALLOWED" to TRUE.', 1297932028);
				}
				$object = clone $object;
			}

			return $object;
		} else {
			if ($configuration === NULL || $configuration->getConfigurationValue('F3\FLOW3\Property\TypeConverter\PersistentObjectConverter', self::CONFIGURATION_CREATION_ALLOWED) !== TRUE) {
				throw new \F3\FLOW3\Property\Exception\InvalidPropertyMappingConfigurationException('Creation of objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_CREATION_ALLOWED" to TRUE');
			}
			return $this->buildObject($convertedChildProperties, $targetType);
		}
	}

	/**
	 * Fetch an object from persistence layer.
	 *
	 * @param mixed $identity
	 * @param string $targetType
	 * @return object
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function fetchObjectFromPersistence($identity, $targetType) {
		if (is_string($identity)) {
			$object = $this->persistenceManager->getObjectByIdentifier($identity, $targetType);
		} elseif (is_array($identity)) {
			$object = $this->findObjectByIdentityProperties($identity, $targetType);
		} else {
			throw new \F3\FLOW3\Property\Exception\InvalidSourceException('The identity property "' . $identity . '" is neither a string nor an array.', 1297931020);
		}

		if ($object === NULL) {
			throw new \F3\FLOW3\Property\Exception\TargetNotFoundException('Object with identity "' . print_r($identity, TRUE) . '" not found.', 1297933823);
		}

		return $object;
	}

	/**
	 * Builds a new instance of $objectType with the given $possibleConstructorArgumentValues. If
	 * constructor argument values are missing from the given array the method
	 * looks for a default value in the constructor signature. Furthermore, the constructor arguments are removed from $possibleConstructorArgumentValues
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
				throw new \F3\FLOW3\Property\Exception\InvalidTargetException('Missing constructor argument "' . $constructorArgumentName . '" for object of type "' . $objectType . '".' , 1268734872);
			}
		}
		return call_user_func_array(array($this->objectManager, 'create'), array_merge(array($objectType), $constructorArguments));
	}

	/**
	 * Finds an object from the repository by searching for its identity properties.
	 *
	 * @param array $identityProperties Property names and values to search for
	 * @param string $type The object type to look for
	 * @return object Either the object matching the identity or NULL if no object was found
	 * @throws \F3\FLOW3\Property\Exception\DuplicateObjectException if more than one object was found
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function findObjectByIdentityProperties(array $identityProperties, $type) {
		$query = $this->persistenceManager->createQueryForType($type);
		$classSchema = $this->reflectionService->getClassSchema($type);

		$equals = array();
		foreach ($classSchema->getIdentityProperties() as $propertyName => $propertyType) {
			if (isset($identityProperties[$propertyName])) {
				if ($propertyType === 'string') {
					$equals[] = $query->equals($propertyName, $identityProperties[$propertyName], FALSE);
				} else {
					$equals[] = $query->equals($propertyName, $identityProperties[$propertyName]);
				}
			}
		}

		if (count($equals) === 1) {
			$constraint = current($equals);
		} else {
			$constraint = $query->logicalAnd(current($equals), next($equals));
			while (($equal = next($equals)) !== FALSE) {
				$constraint = $query->logicalAnd($constraint, $equal);
			}
		}

		$objects = $query->matching($constraint)->execute();
		$numberOfResults = $objects->count();
		if ($numberOfResults === 1) {
			return $objects->getFirst();
		} elseif ($numberOfResults === 0) {
			return NULL;
		} else {
			throw new \F3\FLOW3\Property\Exception\DuplicateObjectException('More than one object was returned for the given identity, this is a constraint violation.', 1259612399);
		}
	}
}
?>