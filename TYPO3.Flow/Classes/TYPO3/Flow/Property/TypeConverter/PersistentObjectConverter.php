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

/**
 * This converter transforms arrays or strings to persistent objects. It does the following:
 *
 * - If the input is string, it is assumed to be a UUID. Then, the object is fetched from persistence.
 * - If the input is array, we check if it has an identity property.
 *
 * - If the input has an identity property and NO additional properties, we fetch the object from persistence.
 * - If the input has an identity property AND additional properties, we fetch the object from persistence,
 *   and set the sub-properties. We only do this if the configuration option "CONFIGURATION_MODIFICATION_ALLOWED" is TRUE.
 * - If the input has NO identity property, but additional properties, we create a new object and return it.
 *   However, we only do this if the configuration option "CONFIGURATION_CREATION_ALLOWED" is TRUE.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class PersistentObjectConverter extends ObjectConverter {

	/**
	 * @var string
	 */
	const PATTERN_MATCH_UUID = '/([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}/';

	/**
	 * @var integer
	 */
	const CONFIGURATION_MODIFICATION_ALLOWED = 1;

	/**
	 * @var integer
	 */
	const CONFIGURATION_CREATION_ALLOWED = 2;

	/**
	 * @var array
	 */
	protected $sourceTypes = array('string', 'array');

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * We can only convert if the $targetType is either tagged with entity or value object.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @return boolean
	 */
	public function canConvertFrom($source, $targetType) {
		return (
			$this->reflectionService->isClassAnnotatedWith($targetType, 'TYPO3\Flow\Annotations\Entity') ||
			$this->reflectionService->isClassAnnotatedWith($targetType, 'TYPO3\Flow\Annotations\ValueObject') ||
			$this->reflectionService->isClassAnnotatedWith($targetType, 'Doctrine\ORM\Mapping\Entity')
		);
	}

	/**
	 * All properties in the source array except __identity are sub-properties.
	 *
	 * @param mixed $source
	 * @return array
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
		if (is_string($source)) {
			return array();
		}
		if (isset($source['__identity'])) {
			unset($source['__identity']);
		}
		return parent::getSourceChildPropertiesToBeConverted($source);
	}

	/**
	 * The type of a property is determined by the reflection service.
	 *
	 * @param string $targetType
	 * @param string $propertyName
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException
	 */
	public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration) {
		$configuredTargetType = $configuration->getConfigurationFor($propertyName)->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', self::CONFIGURATION_TARGET_TYPE);
		if ($configuredTargetType !== NULL) {
			return $configuredTargetType;
		}

		$schema = $this->reflectionService->getClassSchema($targetType);
		$setterMethodName = \TYPO3\Flow\Reflection\ObjectAccess::buildSetterMethodName($propertyName);
		if ($schema->hasProperty($propertyName)) {
			$propertyInformation = $schema->getProperty($propertyName);
			return $propertyInformation['type'] . ($propertyInformation['elementType'] !== NULL ? '<' . $propertyInformation['elementType'] . '>' : '');
		} elseif ($this->reflectionService->hasMethod($targetType, $setterMethodName)) {
			$methodParameters = $this->reflectionService->getMethodParameters($targetType, $setterMethodName);
			$methodParameter = current($methodParameters);
			if (!isset($methodParameter['type'])) {
				throw new \TYPO3\Flow\Property\Exception\InvalidTargetException('Setter for property "' . $propertyName . '" had no type hint or documentation in target object of type "' . $targetType . '".', 1303379158);
			} else {
				return $methodParameter['type'];
			}
		} else {
			throw new \TYPO3\Flow\Property\Exception\InvalidTargetException('Property "' . $propertyName . '" was not found in target object of type "' . $targetType . '".', 1297978366);
		}
	}

	/**
	 * Convert an object from $source to an entity or a value object.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return object the target type
	 * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException
	 * @throws \InvalidArgumentException
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if (is_array($source)) {
			if ($this->reflectionService->isClassAnnotatedWith($targetType, 'TYPO3\Flow\Annotations\ValueObject')) {
				// Unset identity for valueobject to use constructor mapping, since the identity is determined from
				// constructor arguments
				unset($source['__identity']);
			}
			$object = $this->handleArrayData($source, $targetType, $convertedChildProperties, $configuration);
		} elseif (is_string($source)) {
			if ($source === '') {
				return NULL;
			}
			$object = $this->fetchObjectFromPersistence($source, $targetType);
		} else {
			throw new \InvalidArgumentException('Only strings and arrays are accepted.', 1305630314);
		}
		foreach ($convertedChildProperties as $propertyName => $propertyValue) {
			$result = \TYPO3\Flow\Reflection\ObjectAccess::setProperty($object, $propertyName, $propertyValue);
			if ($result === FALSE) {
				$exceptionMessage = sprintf(
					'Property "%s" having a value of type "%s" could not be set in target object of type "%s". Make sure that the property is accessible properly, for example via an appropriate setter method.',
					$propertyName,
					(is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue)),
					$targetType
				);
				throw new \TYPO3\Flow\Property\Exception\InvalidTargetException($exceptionMessage, 1297935345);
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
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return object
	 * @throws \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	protected function handleArrayData(array $source, $targetType, array &$convertedChildProperties, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if (isset($source['__identity'])) {
			$object = $this->fetchObjectFromPersistence($source['__identity'], $targetType);

			if (count($source) > 1 && ($configuration === NULL || $configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', self::CONFIGURATION_MODIFICATION_ALLOWED) !== TRUE)) {
				throw new \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException('Modification of persistent objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_MODIFICATION_ALLOWED" to TRUE.', 1297932028);
			}
		} else {
			if ($configuration === NULL || $configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', self::CONFIGURATION_CREATION_ALLOWED) !== TRUE) {
				throw new \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException('Creation of objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_CREATION_ALLOWED" to TRUE');
			}
			$object = $this->buildObject($convertedChildProperties, $targetType);
		}
		return $object;
	}

	/**
	 * Fetch an object from persistence layer.
	 *
	 * @param mixed $identity
	 * @param string $targetType
	 * @return object
	 * @throws \TYPO3\Flow\Property\Exception\TargetNotFoundException
	 * @throws \TYPO3\Flow\Property\Exception\InvalidSourceException
	 */
	protected function fetchObjectFromPersistence($identity, $targetType) {
		if (is_string($identity)) {
			$object = $this->persistenceManager->getObjectByIdentifier($identity, $targetType);
		} elseif (is_array($identity)) {
			$object = $this->findObjectByIdentityProperties($identity, $targetType);
		} else {
			throw new \TYPO3\Flow\Property\Exception\InvalidSourceException('The identity property "' . $identity . '" is neither a string nor an array.', 1297931020);
		}

		if ($object === NULL) {
			throw new \TYPO3\Flow\Property\Exception\TargetNotFoundException('Object with identity "' . print_r($identity, TRUE) . '" not found.', 1297933823);
		}

		return $object;
	}

	/**
	 * Finds an object from the repository by searching for its identity properties.
	 *
	 * @param array $identityProperties Property names and values to search for
	 * @param string $type The object type to look for
	 * @return object Either the object matching the identity or NULL if no object was found
	 * @throws \TYPO3\Flow\Property\Exception\DuplicateObjectException if more than one object was found
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
			throw new \TYPO3\Flow\Property\Exception\DuplicateObjectException('More than one object was returned for the given identity, this is a constraint violation.', 1259612399);
		}
	}
}
?>