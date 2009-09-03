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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Mapper {

	/**
	 * A preg pattern to match against UUIDs
	 * @var string
	 */
	const PATTERN_MATCH_UUID = '/([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}/';

	/**
	 * Results of the last mapping operation
	 * @var \F3\FLOW3\Propert\MappingResults
	 */
	protected $mappingResults;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Validation\ValidatorResolver
	 */
	protected $validatorResolver;

	/**
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Persistence\ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \F3\FLOW3\Persistence\QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
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
	 * @param \F3\FLOW3\Reflection\Service
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the persistence manager
	 *
	 * @param \F3\FLOW3\Persistence\ManagerInterface $persistenceManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\ManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Injects a QueryFactory instance
	 *
	 * @param \F3\FLOW3\Persistence\QueryFactoryInterface $queryFactory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectQueryFactory(\F3\FLOW3\Persistence\QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
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
	 * @param object $target The target object
	 * @param \F3\FLOW3\Validation\Validator\ObjectValidatorInterface $targetObjectValidator A validator used for validating the target object
	 * @param array $optionalPropertyNames Names of optional properties. If a property is specified here and it doesn't exist in the source, no error is issued.
	 * @return boolean TRUE if the mapped properties are valid, otherwise FALSE
	 * @see getMappingResults()
	 * @see map()
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function mapAndValidate(array $propertyNames, $source, $target, $optionalPropertyNames = array(), \F3\FLOW3\Validation\Validator\ObjectValidatorInterface $targetObjectValidator) {
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
	 * Add errors to the mapping result from an object validator (property errors).
	 *
	 * @param array Array of \F3\FLOW3\Validation\PropertyError
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @return void
	 */
	protected function addErrorsFromObjectValidator($errors) {
		foreach ($errors as $error) {
			if ($error instanceof \F3\FLOW3\Validation\PropertyError) {
				$propertyName = $error->getPropertyName();
				$this->mappingResults->addError($error, $propertyName);
			}
		}
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
	 * @param object $target The target object
	 * @param array $optionalPropertyNames Names of optional properties. If a property is specified here and it doesn't exist in the source, no error is issued.
	 * @return boolean TRUE if the properties could be mapped, otherwise FALSE
	 * @see mapAndValidate()
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function map(array $propertyNames, $source, &$target, $optionalPropertyNames = array()) {
		if (!is_object($source) && !is_array($source)) throw new \F3\FLOW3\Property\Exception\InvalidSource('The source object must be a valid object or array, ' . gettype($target) . ' given.', 1187807099);

		if (is_string($target) && strpos($target, '\\') !== FALSE) {
			return $this->transformToObject($source, $target, '--none--');
		}

		if (!is_object($target) && !is_array($target)) throw new \F3\FLOW3\Property\Exception\InvalidTarget('The target object must be a valid object or array, ' . gettype($target) . ' given.', 1187807099);

		$this->mappingResults = $this->objectFactory->create('F3\FLOW3\Property\MappingResults');
		if (is_object($target)) {
			if ($target instanceof \F3\FLOW3\AOP\ProxyInterface) {
				$targetClassSchema = $this->reflectionService->getClassSchema($target->FLOW3_AOP_Proxy_getProxyTargetClassName());
			} else {
				$targetClassSchema = $this->reflectionService->getClassSchema(get_class($target));
			}
		} else {
			$targetClassSchema = NULL;
		}

		foreach ($propertyNames as $propertyName) {
			$propertyValue = NULL;
			if (is_array($source) || $source instanceof \ArrayAccess) {
				if (isset($source[$propertyName])) {
					$propertyValue = $source[$propertyName];
				}
			} else {
				$propertyValue = \F3\FLOW3\Reflection\ObjectAccess::getProperty($source, $propertyName);
			}

			if ($propertyValue === NULL && !in_array($propertyName, $optionalPropertyNames)) {
				$this->mappingResults->addError($this->objectFactory->create('F3\FLOW3\Error\Error', "Required property '$propertyName' does not exist." , 1236785359), $propertyName);
			} else {
				if ($targetClassSchema !== NULL && $targetClassSchema->hasProperty($propertyName)) {
					$propertyMetaData = $targetClassSchema->getProperty($propertyName);

					if (in_array($propertyMetaData['type'], array('array', 'ArrayObject', 'SplObjectStorage')) && strpos($propertyMetaData['elementType'], '\\') !== FALSE) {
						$objects = array();
						foreach ($propertyValue as $value) {
							$objects[] = $this->transformToObject($value, $propertyMetaData['elementType'], $propertyName);
						}

							// make sure we hand out what is expected
						if ($propertyMetaData['type'] === 'ArrayObject') {
							$propertyValue = new \ArrayObject($objects);
						} elseif ($propertyMetaData['type']=== 'SplObjectStorage') {
							$propertyValue = new \SplObjectStorage();
							foreach ($objects as $object) {
								$propertyValue->attach($object);
							}
						} else {
							$propertyValue = $objects;
						}
					} elseif (strpos($propertyMetaData['type'], '\\') !== FALSE) {
						$propertyValue = $this->transformToObject($propertyValue, $propertyMetaData['type'], $propertyName);
					}
				} elseif ($targetClassSchema !== NULL) {
					$this->mappingResults->addError($this->objectFactory->create('F3\FLOW3\Error\Error', "Property '$propertyName' does not exist in target class schema." , 1251813614), $propertyName);
				}

				if (is_array($target)) {
					$target[$propertyName] = $propertyValue;
				} elseif (\F3\FLOW3\Reflection\ObjectAccess::setProperty($target, $propertyName, $propertyValue) === FALSE) {
					$this->mappingResults->addError($this->objectFactory->create('F3\FLOW3\Error\Error', "Property '$propertyName' could not be set." , 1236783102), $propertyName);
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
	 * Transforms strings with UUIDs or arrays with UUIDs/identity properties
	 * into the requested type, if possible.
	 *
	 * @param mixed $propertyValue The value to transform, string or array
	 * @param string $targetType The type to transform to
	 * @param string $propertyName In case of an error we add this to the error message
	 * @return object
	 */
	protected function transformToObject($propertyValue, $targetType, $propertyName) {
		if (is_string($propertyValue) && preg_match(self::PATTERN_MATCH_UUID, $propertyValue) === 1) {
			$propertyValue = $this->persistenceManager->getBackend()->getObjectByIdentifier($propertyValue);
			if ($propertyValue === FALSE) {
				$this->mappingResults->addError($this->objectFactory->create('F3\FLOW3\Error\Error', 'Querying the repository for the specified object with UUID ' . $propertyValue . ' was not successful.' , 1249379517), $propertyName);
			}
		} elseif (is_array($propertyValue)) {
			if (isset($propertyValue['__identity'])) {
				$existingObject = (is_array($propertyValue['__identity'])) ? $this->findObjectByIdentityProperties($propertyValue['__identity'], $targetType) : $this->persistenceManager->getBackend()->getObjectByIdentifier($propertyValue['__identity']);
				if ($existingObject === FALSE) throw new \F3\FLOW3\Property\Exception\TargetNotFound('Querying the repository for the specified object was not successful.', 1237305720);
				unset($propertyValue['__identity']);
				if (count($propertyValue) === 0) {
					$propertyValue = $existingObject;
				} elseif ($existingObject !== NULL) {
					$newObject = clone $existingObject;
					if ($this->map(array_keys($propertyValue), $propertyValue, $newObject)) {
						$propertyValue = $newObject;
					}
				}
			} else {
				$newObject = $this->objectFactory->create($targetType);
				if ($this->map(array_keys($propertyValue), $propertyValue, $newObject)) {
					$propertyValue = $newObject;
				}
			}
		} else {
			throw new \InvalidArgumentException('transformToObject() accepts only strings and arrays.', 1251814355);
		}

		return $propertyValue;
	}

	/**
	 * Finds an object from the repository by searching for its identity properties.
	 *
	 * @param array $identityProperties Property names and values to search for
	 * @param string $type The object type to look for
	 * @return mixed Either the object matching the identity or, if none or more than one object was found, FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function findObjectByIdentityProperties(array $identityProperties, $type) {
		$query = $this->queryFactory->create($type);
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
		if (count($objects) === 1 ) {
			return current($objects);
		} else {
			$this->mappingResults->addError($this->objectFactory->create('F3\FLOW3\Error\Error', 'Querying the repository for object by properties (' . implode(', ', array_keys($identityProperties)) . ') resulted in ' . count($objects) . ' objects instead of one.' , 1237305719), '--none--');
			return FALSE;
		}
	}

}

?>