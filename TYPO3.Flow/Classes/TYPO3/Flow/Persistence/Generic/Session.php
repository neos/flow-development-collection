<?php
namespace TYPO3\Flow\Persistence\Generic;

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
 * The persistence session - acts as a UoW and Identity Map for Flow's
 * persistence framework.
 *
 * @Flow\Scope("singleton")
 */
class Session {

	/**
	 * Reconstituted objects
	 *
	 * @var \SplObjectStorage
	 */
	protected $reconstitutedEntities;

	/**
	 * Reconstituted entity data (effectively their clean state)
	 *
	 * @var array
	 */
	protected $reconstitutedEntitiesData = array();

	/**
	 * @var \SplObjectStorage
	 */
	protected $objectMap;

	/**
	 * @var array
	 */
	protected $identifierMap = array();

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * Constructs a new Session
	 *
	 */
	public function __construct() {
		$this->reconstitutedEntities = new \SplObjectStorage();
		$this->objectMap = new \SplObjectStorage();
	}

	/**
	 * Injects a Reflection Service instance
	 *
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Registers data for a reconstituted object.
	 *
	 * $entityData format is described in
	 * "Documentation/PersistenceFramework object data format.txt"
	 *
	 * @param object $entity
	 * @param array $entityData
	 * @return void
	 */
	public function registerReconstitutedEntity($entity, array $entityData) {
		$this->reconstitutedEntities->attach($entity);
		$this->reconstitutedEntitiesData[$entityData['identifier']] = $entityData;
	}

	/**
	 * Replace a reconstituted object, leaves the clean data unchanged.
	 *
	 * @param object $oldEntity
	 * @param object $newEntity
	 * @return void
	 */
	public function replaceReconstitutedEntity($oldEntity, $newEntity) {
		$this->reconstitutedEntities->detach($oldEntity);
		$this->reconstitutedEntities->attach($newEntity);
	}

	/**
	 * Unregisters data for a reconstituted object
	 *
	 * @param object $entity
	 * @return void
	 */
	public function unregisterReconstitutedEntity($entity) {
		if ($this->reconstitutedEntities->contains($entity)) {
			$this->reconstitutedEntities->detach($entity);
			unset($this->reconstitutedEntitiesData[$this->getIdentifierByObject($entity)]);
		}
	}

	/**
	 * Returns all objects which have been registered as reconstituted
	 *
	 * @return \SplObjectStorage All reconstituted objects
	 */
	public function getReconstitutedEntities() {
		return $this->reconstitutedEntities;
	}

	/**
	 * Tells whether the given object is a reconstituted entity.
	 *
	 * @param object $entity
	 * @return boolean
	 */
	public function isReconstitutedEntity($entity) {
		return $this->reconstitutedEntities->contains($entity);
	}

	/**
	 * Checks whether the given property was changed in the object since it was
	 * reconstituted. Returns TRUE for unknown objects in all cases!
	 *
	 * @param object $object
	 * @param string $propertyName
	 * @return boolean
	 * @api
	 */
	public function isDirty($object, $propertyName) {
		if ($this->isReconstitutedEntity($object) === FALSE) {
			return TRUE;
		}

		if (property_exists($object, 'Flow_Persistence_LazyLoadingObject_thawProperties')) {
			return FALSE;
		}

		$currentValue = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($object, $propertyName, TRUE);
		$cleanData =& $this->reconstitutedEntitiesData[$this->getIdentifierByObject($object)]['properties'][$propertyName];

		if ($currentValue instanceof \TYPO3\Flow\Persistence\Generic\LazySplObjectStorage && !$currentValue->isInitialized()
				|| ($currentValue === NULL && $cleanData['value'] === NULL)) {
			return FALSE;
		}

		if ($cleanData['multivalue']) {
			return $this->isMultiValuedPropertyDirty($cleanData, $currentValue);
		} else {
			return $this->isSingleValuedPropertyDirty($cleanData['type'], $cleanData['value'], $currentValue);
		}
	}

	/**
	 * Checks the $currentValue against the $cleanData.
	 *
	 * @param array $cleanData
	 * @param \Traversable $currentValue
	 * @return boolean
	 */
	protected function isMultiValuedPropertyDirty(array $cleanData, $currentValue) {
		if (count($cleanData['value']) > 0 && count($cleanData['value']) === count($currentValue)) {
			if ($currentValue instanceof \SplObjectStorage) {
				$cleanIdentifiers = array();
				foreach ($cleanData['value'] as &$cleanObjectData) {
					$cleanIdentifiers[] = $cleanObjectData['value']['identifier'];
				}
				sort($cleanIdentifiers);
				$currentIdentifiers = array();
				foreach ($currentValue as $currentObject) {
					$currentIdentifier = $this->getIdentifierByObject($currentObject);
					if ($currentIdentifier !== NULL) {
						$currentIdentifiers[] = $currentIdentifier;
					}
				}
				sort($currentIdentifiers);
				if ($cleanIdentifiers !== $currentIdentifiers) {
					return TRUE;
				}
			} else {
				foreach ($cleanData['value'] as &$cleanObjectData) {
					if (!isset($currentValue[$cleanObjectData['index']])) {
						return TRUE;
					}
					if (($cleanObjectData['type'] === 'array' && $this->isMultiValuedPropertyDirty($cleanObjectData, $currentValue[$cleanObjectData['index']]) === TRUE)
						|| ($cleanObjectData['type'] !== 'array' && $this->isSingleValuedPropertyDirty($cleanObjectData['type'], $cleanObjectData['value'], $currentValue[$cleanObjectData['index']]) === TRUE)) {
						return TRUE;
					}
				}
			}
		} elseif (count($cleanData['value']) > 0 || count($currentValue) > 0) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Checks the $previousValue against the $currentValue.
	 *
	 * @param string $type
	 * @param mixed $previousValue
	 * @param mixed &$currentValue
	 * @return boolean
	 */
	protected function isSingleValuedPropertyDirty($type, $previousValue, $currentValue) {
		switch ($type) {
			case 'integer':
				if ($currentValue === (int) $previousValue) {
					return FALSE;
				}
			break;
			case 'float':
				if ($currentValue === (float) $previousValue) {
					return FALSE;
				}
			break;
			case 'boolean':
				if ($currentValue === (boolean) $previousValue) {
					return FALSE;
				}
			break;
			case 'string':
				if ($currentValue === (string) $previousValue) {
					return FALSE;
				}
			break;
			case 'DateTime':
				if ($currentValue instanceof \DateTime && $currentValue->getTimestamp() === (int) $previousValue) {
					return FALSE;
				}
			break;
			default:
				if (is_object($currentValue) && $this->getIdentifierByObject($currentValue) === $previousValue['identifier']) {
					return FALSE;
				}
			break;
		}
		return TRUE;
	}

	/**
	 * Returns the previous (last persisted) state of the property.
	 * If nothing is found, NULL is returned.
	 *
	 * @param object $object
	 * @param string $propertyName
	 * @return mixed
	 */
	public function getCleanStateOfProperty($object, $propertyName) {
		if ($this->isReconstitutedEntity($object) === FALSE) {
			return NULL;
		}
		$identifier = $this->getIdentifierByObject($object);
		if (!isset($this->reconstitutedEntitiesData[$identifier]['properties'][$propertyName])) {
			return NULL;
		}
		return $this->reconstitutedEntitiesData[$identifier]['properties'][$propertyName];
	}

	/**
	 * Checks whether the given object is known to the identity map
	 *
	 * @param object $object
	 * @return boolean
	 * @api
	 */
	public function hasObject($object) {
		return $this->objectMap->contains($object);
	}

	/**
	 * Checks whether the given identifier is known to the identity map
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public function hasIdentifier($identifier) {
		return array_key_exists($identifier, $this->identifierMap);
	}

	/**
	 * Returns the object for the given identifier
	 *
	 * @param string $identifier
	 * @return object
	 * @api
	 */
	public function getObjectByIdentifier($identifier) {
		return $this->identifierMap[$identifier];
	}

	/**
	 * Returns the identifier for the given object either from
	 * the session, if the object was registered, or from the object
	 * itself using a special uuid property or the internal
	 * properties set by AOP.
	 *
	 * Note: this returns an UUID even if the object has not been persisted
	 * in case of AOP-managed entities. Use isNewObject() if you need
	 * to distinguish those cases.
	 *
	 * @param object $object
	 * @return string
	 * @api
	 */
	public function getIdentifierByObject($object) {
		if ($this->hasObject($object)) {
			return $this->objectMap[$object];
		}

		$idPropertyNames = $this->reflectionService->getPropertyNamesByTag(get_class($object), 'id');
		if (count($idPropertyNames) === 1) {
			$idPropertyName = $idPropertyNames[0];
			return \TYPO3\Flow\Reflection\ObjectAccess::getProperty($object, $idPropertyName, TRUE);
		} elseif (property_exists($object, 'Persistence_Object_Identifier')) {
			return \TYPO3\Flow\Reflection\ObjectAccess::getProperty($object, 'Persistence_Object_Identifier', TRUE);
		}

		return NULL;
	}

	/**
	 * Register an identifier for an object
	 *
	 * @param object $object
	 * @param string $identifier
	 * @api
	 */
	public function registerObject($object, $identifier) {
		$this->objectMap[$object] = $identifier;
		$this->identifierMap[$identifier] = $object;
	}

	/**
	 * Unregister an object
	 *
	 * @param string $object
	 * @return void
	 */
	public function unregisterObject($object) {
		unset($this->identifierMap[$this->objectMap[$object]]);
		$this->objectMap->detach($object);
	}

	/**
	 * Destroy the state of the persistence session and reset
	 * all internal data.
	 *
	 * @return void
	 */
	public function destroy() {
		$this->identifierMap = array();
		$this->objectMap = new \SplObjectStorage();
		$this->reconstitutedEntities = new \SplObjectStorage();
		$this->reconstitutedEntitiesData = array();
	}
}
?>