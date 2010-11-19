<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Backend;

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
 * An abstract storage backend for the FLOW3 persistence
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
abstract class AbstractBackend implements \F3\FLOW3\Persistence\Backend\BackendInterface {

	/**
	 * An object that was reconstituted
	 * @var integer
	 */
	const OBJECTSTATE_RECONSTITUTED = 1;

	/**
	 * An object that is new
	 * @var integer
	 */
	const OBJECTSTATE_NEW = 2;

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Persistence\Session
	 */
	protected $persistenceSession;

	/**
	 * @var \F3\FLOW3\Persistence\PersistenceManager
	 */
	protected $persistenceManager;

	/**
	 * @var \F3\FLOW3\Validation\ValidatorResolver
	 */
	protected $validatorResolver;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var \SplObjectStorage
	 */
	protected $visitedDuringPersistence;

	/**
	 * @var \SplObjectStorage
	 */
	protected $aggregateRootObjects;

	/**
	 * @var \SplObjectStorage
	 */
	protected $deletedEntities;

	/**
	 * @var array
	 */
	protected $classSchemata = array();

	/**
	 * Constructs the backend
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct() {
		$this->aggregateRootObjects = new \SplObjectStorage();
		$this->deletedEntities = new \SplObjectStorage();
	}

	/**
	 * Injects a Reflection Service instance used for processing objects
	 *
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the persistence session
	 *
	 * @param \F3\FLOW3\Persistence\Session $persistenceSession
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectPersistenceSession(\F3\FLOW3\Persistence\Session $persistenceSession) {
		$this->persistenceSession = $persistenceSession;
	}

	/**
	 * Set a PersistenceManager instance.
	 *
	 * @param \F3\FLOW3\Persistence\PersistenceManager $persistenceManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setPersistenceManager($persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Injects the ValidatorResolver
	 *
	 * @param \F3\FLOW3\Validation\ValidatorResolver $validatorResolver
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectValidatorResolver(\F3\FLOW3\Validation\ValidatorResolver $validatorResolver) {
		$this->validatorResolver = $validatorResolver;
	}

	/**
	 * Injects the system logger
	 *
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectSystemLogger(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Signalizes that the given object has been removed
	 *
	 * @param object $object The object that will be removed
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @signal
	 * @api
	 */
	protected function emitRemovedObject($object) {}

	/**
	 * Signalizes that the given object has been persisted
	 *
	 * @param object $object The object that will be persisted
	 * @param integer $objectState The state, see self::OBJECTSTATE_*
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @signal
	 * @api
	 */
	protected function emitPersistedObject($object, $objectState) {}

	/**
	 * Initializes the backend
	 *
	 * @param array $options
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initialize(array $options) {
		foreach ($options as $optionName => $optionValue) {
			$methodName = 'set' . ucfirst($optionName);
			if (method_exists($this, $methodName)) {
				$this->$methodName($optionValue);
			} elseif ($optionValue ===  NULL) {
				$this->systemLogger->log('Invalid backend option "' . $optionName . '" for backend of type "' . get_class($this) . '" ignored (NULL value).', LOG_DEBUG);
			} else {
				throw new \InvalidArgumentException('Invalid backend option "' . $optionName . '" for backend of type "' . get_class($this) . '"', 1259701878);
			}
		}
		$this->classSchemata = $this->reflectionService->getClassSchemata();
	}

	/**
	 * Sets the aggregate root objects
	 *
	 * @param \SplObjectStorage $objects
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setAggregateRootObjects(\SplObjectStorage $objects) {
		$this->aggregateRootObjects = $objects;
	}

	/**
	 * Sets the deleted objects
	 *
	 * @param \SplObjectStorage $entities
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setDeletedEntities(\SplObjectStorage $entities) {
		$this->deletedEntities = $entities;
	}

	/**
	 * Returns the (internal) UUID for the object, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * Note: this returns an UUID even if the object has not been persisted
	 * in case of AOP-managed entities. Use isNewObject() if you need
	 * to distinguish those cases.
	 *
	 * @param \F3\FLOW3\AOP\ProxyInterface $object
	 * @return string The identifier for the object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	protected function getIdentifierByObject(\F3\FLOW3\AOP\ProxyInterface $object) {
		if ($this->persistenceSession->hasObject($object)) {
			return $this->persistenceSession->getIdentifierByObject($object);
		}

		if (isset($this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()])
				&& $this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()]->getUuidPropertyName() !== NULL) {
			return $object->FLOW3_AOP_Proxy_getProperty($this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()]->getUuidPropertyName());
		} elseif (property_exists($object, 'FLOW3_Persistence_Entity_UUID')) {
				// entities created get an UUID set through AOP
			return $object->FLOW3_Persistence_Entity_UUID;
		} elseif (property_exists($object, 'FLOW3_Persistence_ValueObject_Hash')) {
				// valueobjects created get a hash set through AOP
			return $object->FLOW3_Persistence_ValueObject_Hash;
		}

		return NULL;
	}

	/**
	 * Fetchs the identifier for the given object, either from the declared UUID
	 * property, the injected UUID or injected hash.
	 *
	 * @param object $object
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @deprecated 1.0.0 alpha 13
	 */
	protected function getIdentifierFromObject($object) {
		return $this->getIdentifierByObject($object);
	}

	/**
	 * Commits the current persistence session.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commit() {
		$this->persistObjects();
		$this->processDeletedObjects();
	}

	/**
	 * First persist new objects, then check reconstituted entites.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function persistObjects() {
		$this->visitedDuringPersistence = new \SplObjectStorage();
		foreach ($this->aggregateRootObjects as $object) {
			$this->persistObject($object, NULL);
		}
		foreach ($this->persistenceSession->getReconstitutedEntities() as $entity) {
			$this->persistObject($entity, NULL);
		}
	}

	/**
	 * Stores or updates an object in the underlying storage.
	 *
	 * @param object $object The object to persist
	 * @param string $parentIdentifier
	 * @return string
	 * @api
	 */
	protected function persistObject($object, $parentIdentifier) {
		if (isset($this->visitedDuringPersistence[$object])) {
			return $this->visitedDuringPersistence[$object];
		}

		if (!$this->persistenceSession->hasObject($object) && property_exists($object, 'FLOW3_Persistence_clone') && $object->FLOW3_Persistence_clone === TRUE) {
			$this->persistenceManager->replaceObject($this->persistenceSession->getObjectByIdentifier($this->getIdentifierByObject($object)), $object);
		}

		$identifier = $this->getIdentifierByObject($object);
		$this->visitedDuringPersistence[$object] = $identifier;

		$objectData = array();
		$objectState = $this->storeObject($object, $identifier, $parentIdentifier, $objectData);

		if ($this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()]->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY) {
			$this->persistenceSession->registerReconstitutedEntity($object, $objectData);
		}
		$this->emitPersistedObject($object, $objectState);

		return $identifier;
	}

	/**
	 * Actually store an object, backend-specific
	 *
	 * @param object $object
	 * @param string $identifier
	 * @param string $parentIdentifier
	 * @param array $objectData
	 * @return integer one of self::OBJECTSTATE_*
	 */
	abstract protected function storeObject($object, $identifier, $parentIdentifier, array &$objectData);

	/**
	 * Iterate over deleted entities and process them
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processDeletedObjects() {
		foreach ($this->deletedEntities as $entity) {
			if ($this->persistenceSession->hasObject($entity)) {
				$this->removeEntity($entity);
				$this->persistenceSession->unregisterReconstitutedEntity($entity);
				$this->persistenceSession->unregisterObject($entity);
			}
		}
		$this->deletedEntities = new \SplObjectStorage();
	}

	/**
	 * Remove an entity
	 *
	 * @param object $object
	 * @return void
	 */
	abstract protected function removeEntity($object);

	/**
	 * Remove a value object
	 *
	 * @param object $object
	 * @return void
	 */
	abstract protected function removeValueObject($object);

	/**
	 * Validates the given object and throws an exception if validation fails.
	 *
	 * @param object $object
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	protected function validateObject($object) {
		$classSchema = $this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()];
		$validator = $this->validatorResolver->getBaseValidatorConjunction($classSchema->getClassName());
		if (!$validator->isValid($object)) {
			$errorMessages = '';
			foreach ($validator->getErrors() as $error) {
				$errorMessages .= (string)$error . PHP_EOL;
			}
			throw new \F3\FLOW3\Persistence\Exception\ObjectValidationFailedException('An instance of "' . $object->FLOW3_AOP_Proxy_getProxyTargetClassName() . '" failed to pass validation with ' . count($validator->getErrors()) . ' error(s): ' . PHP_EOL . $errorMessages);
		}
	}

	/**
	 * Returns the type of $value, i.e. the class name or primitive type.
	 *
	 * @param mixed $value
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getType($value) {
		if (is_object($value)) {
			if ($value instanceof \F3\FLOW3\AOP\ProxyInterface) {
				return $value->FLOW3_AOP_Proxy_getProxyTargetClassName();
			} else {
				return get_class($value);
			}
		} else {
			return gettype($value) === 'double' ? 'float' : gettype($value);
		}
	}

	/**
	 * Returns the type name as used in the database table names.
	 *
	 * @param string $type
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getTypeName($type) {
		if (strstr($type, '\\')) {
			return 'object';
		} else {
			return strtolower($type);
		}
	}

	/**
	 *
	 * @param string $identifier The object's identifier
	 * @param object $object The object to work on
	 * @param array $properties The properties to collect (as per class schema)
	 * @param boolean $dirty A dirty flag that is passed by reference and set to TRUE if a dirty property was found
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function collectProperties($identifier, $object, array $properties, &$dirty) {
		$propertyData = array();
		foreach ($properties as $propertyName => $propertyMetaData) {
			$this->checkPropertyValue($object, $propertyName, $propertyMetaData);
			$propertyValue = $object->FLOW3_AOP_Proxy_getProperty($propertyName);

				// handle all objects now, because even clean ones need to be traversed
				// as dirty checking is not recursive
			if ($propertyValue instanceof \F3\FLOW3\AOP\ProxyInterface) {
				if ($this->persistenceSession->isDirty($object, $propertyName)) {
					$dirty = TRUE;
					$this->flattenValue($identifier, $object, $propertyName, $propertyMetaData, $propertyData);
				} else {
					$this->persistObject($propertyValue, $identifier);
				}
			} elseif ($this->persistenceSession->isDirty($object, $propertyName)) {
				$dirty = TRUE;
				$this->flattenValue($identifier, $object, $propertyName, $propertyMetaData, $propertyData);
			}
		}

		return $propertyData;
	}

	/**
	 * Convert a value to the internal object data format
	 *
	 * @param string $identifier The object's identifier
	 * @param object $object The object with the property to flatten
	 * @param string $propertyName The name of the property
	 * @param array $propertyMetaData The property metadata
	 * @param array $propertyData Reference to the property data array
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	protected function flattenValue($identifier, \F3\FLOW3\AOP\ProxyInterface $object, $propertyName, array $propertyMetaData, array &$propertyData) {
		$propertyValue = $object->FLOW3_AOP_Proxy_getProperty($propertyName);

		if ($propertyValue instanceof \F3\FLOW3\AOP\ProxyInterface) {
			$propertyData[$propertyName] = array(
				'type' => $propertyValue->FLOW3_AOP_Proxy_getProxyTargetClassName(),
				'multivalue' => FALSE,
				'value' => $this->processObject($propertyValue, $identifier)
			);
		} else {
			switch ($propertyMetaData['type']) {
				case 'DateTime':
					$propertyData[$propertyName] = array(
						'type' => 'DateTime',
						'multivalue' => FALSE,
						'value' => $this->processDateTime($propertyValue)
					);
				break;
				case 'array':
					$propertyData[$propertyName] = array(
						'type' => 'array',
						'multivalue' => TRUE,
						'value' => $this->processArray($propertyValue, $identifier, $this->persistenceSession->getCleanStateOfProperty($object, $propertyName))
					);
				break;
				case 'SplObjectStorage':
					$propertyData[$propertyName] = array(
						'type' => 'SplObjectStorage',
						'multivalue' => TRUE,
						'value' => $this->processSplObjectStorage($propertyValue, $identifier, $this->persistenceSession->getCleanStateOfProperty($object, $propertyName))
					);
				break;
				default:
					$propertyData[$propertyName] = array(
						'type' => $propertyMetaData['type'],
						'multivalue' => FALSE,
						'value' => $propertyValue
					);
				break;
			}
		}
	}

	/**
	 * @param \F3\FLOW3\AOP\ProxyInterface $object
	 * @param string $identifier
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processObject(\F3\FLOW3\AOP\ProxyInterface $object, $identifier) {
		return array(
			'identifier' => $this->persistObject($object, $identifier)
		);
	}

	/**
	 * Check the property value for allowed types and throw exceptions for
	 * unsupported types.
	 *
	 * @param object $object The object with the property to check
	 * @param string $propertyName The name of the property to check
	 * @param array $propertyMetaData Property metadata
	 * @return void
	 * @throws \F3\FLOW3\Persistence\Exception
	 * @api
	 */
	protected function checkPropertyValue($object, $propertyName, array $propertyMetaData) {
		$propertyValue = $object->FLOW3_AOP_Proxy_getProperty($propertyName);
		$propertyType = $propertyMetaData['type'];
		if ($propertyType === 'ArrayObject') {
			throw new \F3\FLOW3\Persistence\Exception('ArrayObject properties are not supported - missing feature?!?', 1283524355);
		}

		if (is_object($propertyValue)) {
			if ($propertyType === 'object') {
				if (!($propertyValue instanceof \F3\FLOW3\AOP\ProxyInterface)) {
					throw new \F3\FLOW3\Persistence\Exception\IllegalObjectTypeException('Property of generic type object holds "' . get_class($propertyValue) . '", which is not persistable (no @entity or @valueobject), in ' . $object->FLOW3_AOP_Proxy_getProxyTargetClassName() . '::' . $propertyName, 1283531761);
				}
			} elseif(!($propertyValue instanceof $propertyType)) {
				throw new \F3\FLOW3\Persistence\Exception\UnexpectedTypeException('Expected property of type ' . $propertyType . ', but got ' . get_class($propertyValue) . ' for ' . $object->FLOW3_AOP_Proxy_getProxyTargetClassName() . '::' . $propertyName, 1244465558);
			}
		} elseif ($propertyValue !== NULL && $propertyType !== $this->getType($propertyValue)) {
			throw new \F3\FLOW3\Persistence\Exception\UnexpectedTypeException('Expected property of type ' . $propertyType . ', but got ' . gettype($propertyValue) . ' for ' . $object->FLOW3_AOP_Proxy_getProxyTargetClassName() . '::' . $propertyName, 1244465559);
		}
	}

	/**
	 * Store an array as a set of records, with each array element becoming a
	 * property named like the key and the value.
	 *
	 * Note: Objects contained in the array will have a matching entry created,
	 * the objects must be persisted elsewhere!
	 *
	 * @param array $array The array to persist
	 * @param string $parentIdentifier
	 * @param array $previousArray the previously persisted state of the array
	 * @return array An array with "flat" values representing the array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processArray(array $array = NULL, $parentIdentifier, array $previousArray = NULL) {
		if ($previousArray !== NULL && is_array($previousArray['value'])) {
			$this->removeDeletedArrayEntries($array, $previousArray['value']);
		}

		if ($array === NULL) {
			return NULL;
		}

		$values = array();
		foreach ($array as $key => $value) {
			if ($value instanceof \DateTime) {
				$values[] = array(
					'type' => 'DateTime',
					'index' => $key,
					'value' => $this->processDateTime($value)
				);
			} elseif ($value instanceof \SplObjectStorage) {
				throw new \F3\FLOW3\Persistence\Exception('SplObjectStorage instances in arrays are not supported - missing feature?!?', 1261048721);
			} elseif ($value instanceof \ArrayObject) {
				throw new \F3\FLOW3\Persistence\Exception('ArrayObject instances in arrays are not supported - missing feature?!?', 1283524345);
			} elseif (is_object($value)) {
				$values[] = array(
					'type' => $this->getType($value),
					'index' => $key,
					'value' => $this->processObject($value, $parentIdentifier)
				);
			} elseif (is_array($value)) {
				$values[] = array(
					'type' => 'array',
					'index' => $key,
					'value' => $this->processNestedArray($parentIdentifier, $value)
				);
			} else {
				$values[] = array(
					'type' => $this->getType($value),
					'index' => $key,
					'value' => $value
				);
			}
		}

		return $values;
	}

	/**
	 * "Serializes" a nested array for storage.
	 *
	 * @param string $parentIdentifier
	 * @param array $nestedArray
	 * @param \Closure $handler
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processNestedArray($parentIdentifier, array $nestedArray, \Closure $handler = NULL) {
		$identifier = uniqid('a', TRUE);
		$data = array(
			'multivalue' => TRUE,
			'value' => $this->processArray($nestedArray, $parentIdentifier)
		);
		if ($handler instanceof \Closure) {
			$handler($parentIdentifier, $identifier, $data);
		}
		return $identifier;
	}

	/**
	 * Remove objects removed from array compared to $previousArray.
	 *
	 * @param array $array
	 * @param array $previousArray
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeDeletedArrayEntries(array $array = NULL, array $previousArray) {
		foreach ($previousArray as $item) {
			if ($item['type'] === 'array') {
				$this->removeDeletedArrayEntries($array[$item['index']], $item['value']);
			} elseif ($this->getTypeName($item['type']) === 'object' && !($item['type'] === 'DateTime' || $item['type'] === 'SplObjectStorage')) {
				if (!$this->persistenceSession->hasIdentifier($item['value']['identifier'])) {
						// ingore this identifier, assume it was blocked by security query rewriting
					continue;
				}

				$object = $this->persistenceSession->getObjectByIdentifier($item['value']['identifier']);
				if ($array === NULL || !$this->arrayContainsObject($array, $object)) {
					if ($this->classSchemata[$item['type']]->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY
							&& $this->classSchemata[$item['type']]->isAggregateRoot() === FALSE) {
						$this->removeEntity($this->persistenceSession->getObjectByIdentifier($item['value']['identifier']));
					} elseif ($this->classSchemata[$item['type']]->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT) {
						$this->removeValueObject($this->persistenceSession->getObjectByIdentifier($item['value']['identifier']));
					}
				}
			}
		}
	}

	/**
	 * Checks whether the given object is contained in the array. This checks
	 * for object identity in terms of the persistence layer, i.e. the UUID,
	 * when comparing entities.
	 *
	 * @param array $array
	 * @param object $object
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function arrayContainsObject(array $array, $object) {
		if (in_array($object, $array, TRUE) === TRUE) {
			return TRUE;
		}

		foreach ($array as $value) {
			if ($value instanceof $object
					&& property_exists($value, 'FLOW3_Persistence_Entity_UUID')
					&& property_exists($object, 'FLOW3_Persistence_Entity_UUID')
					&& $value->FLOW3_Persistence_Entity_UUID === $object->FLOW3_Persistence_Entity_UUID) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Store an SplObjectStorage as a set of records.
	 *
	 * Note: Objects contained in the SplObjectStorage will have a matching
	 * entry created, the objects must be persisted elsewhere!
	 *
	 * @param \SplObjectStorage $splObjectStorage The SplObjectStorage to persist
	 * @param string $parentIdentifier
	 * @param array $previousObjectStorage the previously persisted state of the SplObjectStorage
	 * @return array An array with "flat" values representing the SplObjectStorage
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processSplObjectStorage(\SplObjectStorage $splObjectStorage = NULL, $parentIdentifier, array $previousObjectStorage = NULL) {
		if ($previousObjectStorage !== NULL && is_array($previousObjectStorage['value'])) {
			$this->removeDeletedSplObjectStorageEntries($splObjectStorage, $previousObjectStorage['value']);
		}

		if ($splObjectStorage === NULL) {
			return NULL;
		}

		$values = array();
		foreach ($splObjectStorage as $object) {
			if ($object instanceof \DateTime) {
				$values[] = array(
					'type' => 'DateTime',
					'index' => NULL,
					'value' => $this->processDateTime($object)
				);
			} elseif ($object instanceof \SplObjectStorage) {
				throw new \F3\FLOW3\Persistence\Exception('SplObjectStorage instances in SplObjectStorage are not supported - missing feature?!?', 1283524360);
			} elseif ($object instanceof \ArrayObject) {
				throw new \F3\FLOW3\Persistence\Exception('ArrayObject instances in SplObjectStorage are not supported - missing feature?!?', 1283524350);
			} else {
				$values[] = array(
					'type' => $this->getType($object),
					'index' => NULL,
					'value' => $this->processObject($object, $parentIdentifier)
				);
			}
		}

		return $values;
	}

	/**
	 * Remove objects removed from SplObjectStorage compared to
	 * $previousSplObjectStorage.
	 *
	 * @param \SplObjectStorage $splObjectStorage
	 * @param array $previousObjectStorage
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeDeletedSplObjectStorageEntries(\SplObjectStorage $splObjectStorage = NULL, array $previousObjectStorage) {
			// remove objects detached since reconstitution
		foreach ($previousObjectStorage as $item) {
			if ($splObjectStorage instanceof \F3\FLOW3\Persistence\LazySplObjectStorage && !$this->persistenceSession->hasIdentifier($item['value']['identifier'])) {
					// ingore this identifier, assume it was blocked by security query rewriting upon activation
				continue;
			}

			$object = $this->persistenceSession->getObjectByIdentifier($item['value']['identifier']);
			if ($splObjectStorage === NULL || !$splObjectStorage->contains($object)) {
				if ($this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()]->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY
						&& $this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()]->isAggregateRoot() === FALSE) {
					$this->removeEntity($object);
				} elseif ($this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()]->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT) {
					$this->removeValueObject($object);
				}
			}
		}
	}

	/**
	 * Creates a unix timestamp from the given DateTime object. If NULL is given
	 * NULL will be returned.
	 *
	 * @param \DateTime $dateTime
	 * @return integer
	 */
	protected function processDateTime(\DateTime $dateTime = NULL) {
		if ($dateTime instanceof \DateTime) {
			return $dateTime->getTimestamp();
		} else {
			return NULL;
		}
	}

}

?>