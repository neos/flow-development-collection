<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

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
 * The persistence session - acts as a UoW and Identity Map for FLOW3's
 * persistence framework.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
	 * Constructs a new Session
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct() {
		$this->reconstitutedEntities = new \SplObjectStorage();
		$this->objectMap = new \SplObjectStorage();
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getReconstitutedEntities() {
		return $this->reconstitutedEntities;
	}

	/**
	 * Tells whether the given object is a reconstituted entity.
	 *
	 * @param object $entity
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirty($object, $propertyName) {
		if ($this->isReconstitutedEntity($object) === FALSE) {
			return TRUE;
		}

		if (property_exists($object, 'FLOW3_Persistence_LazyLoadingObject_thawProperties')) {
			return FALSE;
		}

		$currentValue = $object->FLOW3_AOP_Proxy_getProperty($propertyName);
		$cleanData =& $this->reconstitutedEntitiesData[$this->getIdentifierByObject($object)]['properties'][$propertyName];

		if ($currentValue instanceof \F3\FLOW3\Persistence\LazySplObjectStorage && !$currentValue->isInitialized()
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
					if (property_exists($currentObject, 'FLOW3_Persistence_Entity_UUID')) {
						$currentIdentifiers[] = $currentObject->FLOW3_Persistence_Entity_UUID;
					} elseif (property_exists($currentObject, 'FLOW3_Persistence_ValueObject_Hash')) {
						$currentIdentifiers[] = $currentObject->FLOW3_Persistence_ValueObject_Hash;
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
	 * @return boolan
	 */
	protected function isSingleValuedPropertyDirty($type, $previousValue, $currentValue) {
		switch ($type) {
			case 'integer':
				if ($currentValue === (int) $previousValue) return FALSE;
			break;
			case 'float':
				if ($currentValue === (float) $previousValue) return FALSE;
			break;
			case 'boolean':
				if ($currentValue === (boolean) $previousValue) return FALSE;
			break;
			case 'string':
				if ($currentValue === (string) $previousValue) return FALSE;
			break;
			case 'DateTime':
				if ($currentValue instanceof \DateTime && $currentValue->getTimestamp() === (int) $previousValue) return FALSE;
			break;
			default:
				if (is_object($currentValue) && property_exists($currentValue, 'FLOW3_Persistence_Entity_UUID') && $currentValue->FLOW3_Persistence_Entity_UUID === $previousValue['identifier']) return FALSE;
				if (is_object($currentValue) && property_exists($currentValue, 'FLOW3_Persistence_ValueObject_Hash') && $currentValue->FLOW3_Persistence_ValueObject_Hash === $previousValue['identifier']) return FALSE;
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getCleanStateOfProperty($object, $propertyName) {
		if ($this->isReconstitutedEntity($object) === FALSE) {
			return NULL;
		}
		return $this->reconstitutedEntitiesData[$this->getIdentifierByObject($object)]['properties'][$propertyName];
	}

	/**
	 * Checks whether the given object is known to the identity map
	 *
	 * @param object $object
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasObject($object) {
		return $this->objectMap->contains($object);
	}

	/**
	 * Checks whether the given identifier is known to the identity map
	 *
	 * @param string $identifier
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasIdentifier($identifier) {
		return array_key_exists($identifier, $this->identifierMap);
	}

	/**
	 * Returns the object for the given identifier
	 *
	 * @param string $identifier
	 * @return object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectByIdentifier($identifier) {
		return $this->identifierMap[$identifier];
	}

	/**
	 * Returns the identifier for the given object
	 *
	 * @param object $object
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierByObject($object) {
		return $this->objectMap[$object];
	}

	/**
	 * Register an identifier for an object
	 *
	 * @param object $object
	 * @param string $identifier
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unregisterObject($object) {
		unset($this->identifierMap[$this->objectMap[$object]]);
		$this->objectMap->detach($object);
	}

}
?>
