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
		foreach ($options as $key => $value) {
			$methodName = 'set' . ucfirst($key);
			if (method_exists($this, $methodName)) {
				$this->$methodName($value);
			} else {
				throw new \InvalidArgumentException('Invalid backend option "' . $key . '" for backend of type "' . get_class($this) . '"', 1259701878);
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
	 * @param object $object
	 * @return string The identifier for the object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getIdentifierByObject($object) {
		if ($this->persistenceSession->hasObject($object)) {
			return $this->persistenceSession->getIdentifierByObject($object);
		} elseif ($object instanceof \F3\FLOW3\AOP\ProxyInterface && $object->FLOW3_AOP_Proxy_hasProperty('FLOW3_Persistence_Entity_UUID')) {
				// entities created get an UUID set through AOP
			return $object->FLOW3_AOP_Proxy_getProperty('FLOW3_Persistence_Entity_UUID');
		} elseif ($object instanceof \F3\FLOW3\AOP\ProxyInterface && $object->FLOW3_AOP_Proxy_hasProperty('FLOW3_Persistence_ValueObject_Hash')) {
				// valueobjects created get a hash set through AOP
			return $object->FLOW3_AOP_Proxy_getProperty('FLOW3_Persistence_ValueObject_Hash');
		} else {
			return NULL;
		}
	}

	/**
	 * Fetchs the identifier for the given object, either from the declared UUID
	 * property, the injected UUID or injected hash.
	 *
	 * @param object $object
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getIdentifierFromObject($object) {
		$classSchema = $this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()];

		if ($classSchema->getUuidPropertyName() !== NULL) {
			return $object->FLOW3_AOP_Proxy_getProperty($classSchema->getUuidPropertyName());
		} elseif (property_exists($object, 'FLOW3_Persistence_Entity_UUID')) {
			return $object->FLOW3_Persistence_Entity_UUID;
		} elseif (property_exists($object, 'FLOW3_Persistence_ValueObject_Hash')) {
			return $object->FLOW3_Persistence_ValueObject_Hash;
		}
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
			$this->persistObject($object);
		}
		foreach ($this->persistenceSession->getReconstitutedEntities() as $entity) {
			$this->persistObject($entity);
		}
	}

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
	 * Validates the given object and throws an exception if validation fails.
	 *
	 * @param object $object
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
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

}

?>