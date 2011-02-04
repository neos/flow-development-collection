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
 * The Property Mapper maps properties from a source onto a given target object, often a
 * (domain-) model. Which properties are required and how they should be filtered can
 * be customized.
 *
 * During the mapping process, the property values are validated and the result of this
 * validation can be queried.
 *
 * The following code would map the property of the source array to the target:
 *
 * $target = new ArrayObject();
 * $source = new ArrayObject(
 *    array(
 *       'someProperty' => 'SomeValue'
 *    )
 * );
 * $mapper->mapAndValidate(array('someProperty'), $source, $target);
 *
 * Now the target object equals the source object.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 * @api
 */
class PropertyMapper {

	/**
	 * A preg pattern to match against UUIDs
	 * @var string
	 */
	const PATTERN_MATCH_UUID = '/([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}/';

	/**
	 * Results of the last mapping operation
	 * @var \F3\FLOW3\Property\MappingResults
	 */
	protected $mappingResults;

	/**
	 * A list of property editor instances, indexed by the object type they are designed for
	 * @var array
	 */
	protected $objectConverters = array();

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Validation\ValidatorResolver
	 */
	protected $validatorResolver;

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the validator resolver
	 *
	 * @param \F3\FLOW3\Validation\ValidatorResolver $validatorResolver
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectValidatorResolver(\F3\FLOW3\Validation\ValidatorResolver $validatorResolver) {
		$this->validatorResolver = $validatorResolver;
	}

	/**
	 * Injects the Reflection Service
	 *
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the persistence manager
	 *
	 * @param \F3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Initializes this Property Mapper
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		foreach($this->reflectionService->getAllImplementationClassNamesForInterface('F3\FLOW3\Property\ObjectConverterInterface') as $objectConverterClassName) {
			$objectConverter = $this->objectManager->get($objectConverterClassName);
			foreach ($objectConverter->getSupportedTypes() as $supportedType) {
				$this->objectConverters[$supportedType] = $objectConverter;
			}
		}
	}

	/**
	 * Maps the given properties to the target object and validates the properties according to the defined
	 * validators. If the result object is not valid, the operation will be undone (the target object remains
	 * unchanged) and this method returns FALSE.
	 *
	 * If in doubt, always prefer this method over the map() method because skipping validation can easily become
	 * a security issue.
	 *
	 * @param array $propertyNames Names of the properties to map.
	 * @param mixed $source Source containing the properties to map to the target object. Must either be an array, ArrayObject or any other object.
	 * @param mixed $target The target object or array. Alternatively this may be a fully qualified object name if the target should be created or reconstituted.
	 * @param array $optionalPropertyNames Names of optional properties. If a property is specified here and it doesn't exist in the source, no error is issued.
	 * @param \F3\FLOW3\Validation\Validator\ObjectValidatorInterface $targetObjectValidator A validator used for validating the target object
	 * @return boolean TRUE if the mapped properties are valid, otherwise FALSE
	 * @see getMappingResults()
	 * @see map()
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function mapAndValidate(array $propertyNames, $source, $target, array $optionalPropertyNames = array(), \F3\FLOW3\Validation\Validator\ObjectValidatorInterface $targetObjectValidator) {
		$backupProperties = array();

		$this->map($propertyNames, $source, $backupProperties, $optionalPropertyNames);
		if ($this->mappingResults->hasErrors()) return FALSE;

		$this->map($propertyNames, $source, $target, $optionalPropertyNames);
		if ($this->mappingResults->hasErrors()) return FALSE;

		if ($targetObjectValidator->isValid($target) !== TRUE) {
			$this->addErrorsFromObjectValidator($targetObjectValidator->getErrors());
			$backupMappingResult = $this->mappingResults;
			$this->map($propertyNames, $backupProperties, $source, $optionalPropertyNames);
			$this->mappingResults = $backupMappingResult;
		}
		return (!$this->mappingResults->hasErrors());
	}

	/**
	 * Maps the given properties to the target object WITHOUT VALIDATING THE RESULT.
	 * If the properties could be set, this method returns TRUE, otherwise FALSE.
	 * Returning TRUE does not mean that the target object is valid and secure!
	 *
	 * Only use this method if you're sure that you don't need validation!
	 *
	 * @param array $propertyNames Names of the properties to map.
	 * @param mixed $source Source containing the properties to map to the target object. Must either be an array, ArrayObject or any other object.
	 * @param mixed $target The target object or array. Alternatively this may be a fully qualified object name if the target should be created or reconstituted.
	 * @param array $optionalPropertyNames Names of optional properties. If a property is specified here and it doesn't exist in the source, no error is issued.
	 * @return boolean TRUE if the properties could be mapped, otherwise FALSE
	 * @see mapAndValidate()
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function map(array $propertyNames, $source, &$target, $optionalPropertyNames = array()) {
		if (!is_object($source) && !is_array($source)) throw new \F3\FLOW3\Property\Exception\InvalidSourceException('The source object must be a valid object or array, ' . gettype($target) . ' given.', 1187807099);
		if (is_string($target) && strpos($target, '\\') !== FALSE) {
			return $this->transformToObject($source, $target, '--none--');
		}
		$this->mappingResults = $this->objectManager->create('F3\FLOW3\Property\MappingResults');

		if (!is_object($target) && !is_array($target)) throw new \F3\FLOW3\Property\Exception\InvalidTargetException('The target must be a valid object, class name or array, ' . gettype($target) . ' given.', 1187807099);

		if (is_object($target)) {
				// fixme - make sure class schema can be fetched for doctrine proxies
			$targetClassSchema = $this->reflectionService->getClassSchema($target);
		} else {
			$targetClassSchema = NULL;
		}

		foreach ($propertyNames as $propertyName) {
			$propertyValue = NULL;
			if (is_array($source) || $source instanceof \ArrayAccess) {
				if (isset($source[$propertyName])) {
					$propertyValue = $source[$propertyName];
				}
			} elseif (\F3\FLOW3\Reflection\ObjectAccess::isPropertyGettable($source, $propertyName)) {
				$propertyValue = \F3\FLOW3\Reflection\ObjectAccess::getProperty($source, $propertyName);
			}

			if ($propertyValue === NULL && !in_array($propertyName, $optionalPropertyNames)) {
				$this->mappingResults->addError($this->objectManager->create('F3\FLOW3\Error\Error', "Required property '$propertyName' does not exist in source." , 1236785359), $propertyName);
			} else {
				if (method_exists($target, \F3\FLOW3\Reflection\ObjectAccess::buildSetterMethodName($propertyName))
						&& is_callable(array($target, \F3\FLOW3\Reflection\ObjectAccess::buildSetterMethodName($propertyName)))) {
					$targetClassName = get_class($target);
					$methodParameters = $this->reflectionService->getMethodParameters($targetClassName, \F3\FLOW3\Reflection\ObjectAccess::buildSetterMethodName($propertyName));
					$methodParameter = current($methodParameters);

					if (!isset($methodParameter['type'])) {
						$this->mappingResults->addError($this->objectManager->create('F3\FLOW3\Error\Error', 'No type hint or documentation for property "' . $propertyName . '" found".' , 1296767655), $propertyName);
						continue;
					}
					try {
						$targetPropertyType = \F3\FLOW3\Utility\TypeHandling::parseType($methodParameter['type']);
					} catch (\InvalidArgumentException $exception) {
						$this->mappingResults->addError($this->objectManager->create('F3\FLOW3\Error\Error', 'The type hint or documentation of property "' . $propertyName . '" specified the unknown type "' . $methodParameter['type'] . '".' , 1268239762), $propertyName);
						continue;
					}
				} elseif ($targetClassSchema !== NULL && $targetClassSchema->hasProperty($propertyName)) {
					$targetPropertyType = $targetClassSchema->getProperty($propertyName);
				} elseif ($targetClassSchema !== NULL) {
					$this->mappingResults->addError($this->objectManager->create('F3\FLOW3\Error\Error', 'Property "' . $propertyName . '" does not exist in target class schema.' , 1251813614), $propertyName);
					continue;
				}

				if (isset($targetPropertyType)) {
					if (in_array($targetPropertyType['type'], array('array', 'ArrayObject', 'SplObjectStorage', 'Doctrine\Common\Collections\ArrayCollection')) && ($targetPropertyType['elementType'] !== NULL && !\F3\FLOW3\Utility\TypeHandling::isLiteral($targetPropertyType['elementType']))) {
						$objects = array();
						if (is_array($propertyValue) || $propertyValue instanceof \Traversable) {
							foreach ($propertyValue as $value) {
								$objects[] = $this->transformToObject($value, $targetPropertyType['elementType'], $propertyName);
							}
						}

						if ($targetPropertyType['type'] === 'ArrayObject') {
							$propertyValue = new \ArrayObject($objects);
						} elseif ($targetPropertyType['type'] === 'Doctrine\Common\Collections\ArrayCollection') {
								$propertyValue = new \Doctrine\Common\Collections\ArrayCollection($objects);
						} elseif ($targetPropertyType['type'] === 'SplObjectStorage') {
							$propertyValue = new \SplObjectStorage();
							foreach ($objects as $object) {
								$propertyValue->attach($object);
							}
						} else {
							$propertyValue = $objects;
						}
					} elseif (strpos($targetPropertyType['type'], '\\') !== FALSE) {
						$propertyValue = $this->transformToObject($propertyValue, $targetPropertyType['type'], $propertyName);
						if ($propertyValue === NULL) {
							continue;
						}
					}
				} elseif (is_array($propertyValue) && isset($propertyValue['__identity']) && is_string($propertyValue['__identity']) && preg_match(self::PATTERN_MATCH_UUID, $propertyValue['__identity']) === 1) {
					$propertyValue = $this->transformToObject($propertyValue, NULL, $propertyName);
				}

				if (is_array($target)) {
					$target[$propertyName] = $propertyValue;
				} elseif (\F3\FLOW3\Reflection\ObjectAccess::setProperty($target, $propertyName, $propertyValue) === FALSE) {
					$this->mappingResults->addError($this->objectManager->create('F3\FLOW3\Error\Error', "Property '$propertyName' could not be set." , 1236783102), $propertyName);
				}
			}
		}

		return !$this->mappingResults->hasErrors();
	}

	/**
	 * Returns the results of the last mapping operation.
	 *
	 * @return \F3\FLOW3\Propert\MappingResults The mapping results (or NULL if no mapping has been carried out yet)
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getMappingResults() {
		return $this->mappingResults;
	}

	/**
	 * Add errors to the mapping result from an object validator (property errors).
	 *
	 * @param array $errors Array of \F3\FLOW3\Validation\PropertyError
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @return void
	 */
	protected function addErrorsFromObjectValidator(array $errors) {
		foreach ($errors as $error) {
			if ($error instanceof \F3\FLOW3\Validation\PropertyError) {
				$propertyName = $error->getPropertyName();
				$this->mappingResults->addError($error, $propertyName);
			}
		}
	}

	/**
	 * Transforms strings with UUIDs or arrays with UUIDs/identity properties
	 * into the requested type, if possible.
	 *
	 * @param mixed $propertyValue The value to transform, string or array
	 * @param string $targetType The type to transform to
	 * @param string $propertyName In case of an error we add this to the error message
	 * @return object The object, when no transformation was possible this may return NULL as well
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function transformToObject($propertyValue, $targetType, $propertyName) {
		if (isset($this->objectConverters[$targetType])) {
			$conversionResult = $this->objectConverters[$targetType]->convertFrom($propertyValue);
			if ($conversionResult instanceof \F3\FLOW3\Error\Error) {
				$this->mappingResults->addError($conversionResult, $propertyName);
				return NULL;
			} elseif (is_object($conversionResult) || $conversionResult === NULL) {
				return $conversionResult;
			}
		}
		if (is_string($propertyValue)) {
			$object = $this->persistenceManager->getObjectByIdentifier($propertyValue, $targetType);
			if ($object === FALSE) {
				$this->mappingResults->addError($this->objectManager->create('F3\FLOW3\Error\Error', 'Querying the repository for the specified object of type "' . $targetType . '" with identifier "' . $propertyValue . '" was not successful.' , 1249379517), $propertyName);
			}
		} elseif (is_array($propertyValue)) {
			if (isset($propertyValue['__identity'])) {
				$existingObject = (is_array($propertyValue['__identity'])) ? $this->findObjectByIdentityProperties($propertyValue['__identity'], $targetType) : $this->persistenceManager->getObjectByIdentifier($propertyValue['__identity'], $targetType);
				if ($existingObject === NULL) {
					throw new \F3\FLOW3\Property\Exception\TargetNotFoundException('Querying the repository for the specified object of type "' . $targetType . '"was not successful.', 1237305720);
				}
				if ($targetType === NULL) {
					$targetType = get_class($existingObject);
				}

				unset($propertyValue['__identity']);
				if (count($propertyValue) === 0) {
					$object = $existingObject;
				} elseif ($existingObject !== NULL) {
					if ($this->reflectionService->isClassTaggedWith($targetType, 'valueobject')) {
						$object = $this->buildObject($propertyValue, $targetType);
					} else {
						$newObject = clone $existingObject;
						if ($this->map(array_keys($propertyValue), $propertyValue, $newObject)) {
							$object = $newObject;
						} else {
							throw new \RuntimeException('Could not transform to object', 1296763314);
						}
					}
				}
			} else {
				$newObject = $this->buildObject($propertyValue, $targetType);
				if (count($propertyValue)) {
					if ($this->map(array_keys($propertyValue), $propertyValue, $newObject)) {
						return $newObject;
					}
					throw new \F3\FLOW3\Property\Exception\InvalidTargetException('Values could not be mapped to new object of type ' .$targetType . ' for property "' . $propertyName . '". (Map errors: ' . implode (' - ', $this->mappingResults->getErrors()) . ')' , 1259770027);
				} else {
					return $newObject;
				}
			}
		} else {
			throw new \InvalidArgumentException('transformToObject() accepts only strings and arrays.', 1251814355);
		}

		return $object;
	}

	/**
	 * Builds a new instance of $objectType with the given $constructorArgumentValues. If
	 * constructor argument values are missing from the given array the method
	 * looks for a default value in the constructor signature.
	 *
	 * @param array $constructorArgumentValues
	 * @param string $objectType
	 * @return object The created instance
	 * @throws \F3\FLOW3\Property\Exception\InvalidTargetException if a required constructor argument is missing
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function buildObject(array &$constructorArgumentValues, $objectType) {
		$constructorSignature = $this->reflectionService->getMethodParameters($objectType, '__construct');
		$constructorArguments = array();
		foreach ($constructorSignature as $constructorArgumentName => $constructorArgumentInformation) {
			if (array_key_exists($constructorArgumentName, $constructorArgumentValues)) {
				$constructorArguments[] = $constructorArgumentValues[$constructorArgumentName];
				unset($constructorArgumentValues[$constructorArgumentName]);
			} elseif ($constructorArgumentInformation['optional'] === TRUE) {
				$constructorArguments[] = $constructorArgumentInformation['defaultValue'];
			} else {
				throw new \F3\FLOW3\Property\Exception\InvalidTargetException('Missing constructor argument "' . $constructorArgumentName . '" for value object of type "' . $objectType . '".' , 1268734872);
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
