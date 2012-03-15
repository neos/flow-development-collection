<?php
namespace TYPO3\FLOW3\Persistence\Generic;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A data mapper to map raw records to objects
 *
 * @FLOW3\Scope("singleton")
 */
class DataMapper {

	/**
	 * @var \TYPO3\FLOW3\Persistence\Generic\Session
	 */
	protected $persistenceSession;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Injects the persistence session
	 *
	 * @param \TYPO3\FLOW3\Persistence\Generic\Session $persistenceSession The persistence session
	 * @return void
	 */
	public function injectPersistenceSession(\TYPO3\FLOW3\Persistence\Generic\Session $persistenceSession) {
		$this->persistenceSession = $persistenceSession;
	}

	/**
	 * Injects a Reflection Service instance used for processing objects
	 *
	 * @param \TYPO3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the persistence manager
	 *
	 * @param \TYPO3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager The persistence manager
	 * @return void
	 */
	public function setPersistenceManager(\TYPO3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Maps the (aggregate root) node data and registers the objects as
	 * reconstituted with the session.
	 *
	 * Note: QueryResult relies on the fact that the first object of $objects has the numeric index "0"
	 *
	 * @param array $objectsData
	 * @return array
	 */
	public function mapToObjects(array $objectsData) {
		$objects = array();
		foreach ($objectsData as $objectData) {
			$objects[] = $this->mapToObject($objectData);
		}

		return $objects;
	}

	/**
	 * Maps a single record into the object it represents and registers it as
	 * reconstituted with the session.
	 *
	 * @param array $objectData
	 * @return object
	 * @throws \TYPO3\FLOW3\Persistence\Generic\Exception\InvalidObjectDataException
	 * @throws \TYPO3\FLOW3\Persistence\Exception
	 */
	public function mapToObject(array $objectData) {
		if ($objectData === array()) {
			throw new \TYPO3\FLOW3\Persistence\Generic\Exception\InvalidObjectDataException('The array with object data was empty, probably object not found or access denied.', 1277974338);
		}

		if ($this->persistenceSession->hasIdentifier($objectData['identifier'])) {
			return $this->persistenceSession->getObjectByIdentifier($objectData['identifier']);
		} else {
			$className = $objectData['classname'];
			$classSchema = $this->reflectionService->getClassSchema($className);

			$object = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
			$this->persistenceSession->registerObject($object, $objectData['identifier']);
			if ($classSchema->getModelType() === \TYPO3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY) {
				$this->persistenceSession->registerReconstitutedEntity($object, $objectData);
			}
			if ($objectData['properties'] === array()) {
				if (!$classSchema->isLazyLoadableObject()) {
					throw new \TYPO3\FLOW3\Persistence\Exception('The object of type "' . $className . '" is not marked as lazy loadable.', 1268309017);
				}
				$persistenceManager = $this->persistenceManager;
				$persistenceSession = $this->persistenceSession;
				$dataMapper = $this;
				$identifier = $objectData['identifier'];
				$modelType = $classSchema->getModelType();
				$object->FLOW3_Persistence_LazyLoadingObject_thawProperties = function ($object) use ($persistenceManager, $persistenceSession, $dataMapper, $identifier, $modelType) {
					$objectData = $persistenceManager->getObjectDataByIdentifier($identifier);
					$dataMapper->thawProperties($object, $identifier, $objectData);
					if ($modelType === \TYPO3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY) {
						$persistenceSession->registerReconstitutedEntity($object, $objectData);
					}
				};
			} else {
				$this->thawProperties($object, $objectData['identifier'], $objectData);
			}

			return $object;
		}
	}

	/**
	 * Sets the given properties on the object.
	 *
	 * @param object $object The object to set properties on
	 * @param string $identifier The identifier of the object
	 * @param array $objectData
	 * @return void
	 * @throws \TYPO3\FLOW3\Persistence\Exception\UnknownObjectException
	 */
	public function thawProperties($object, $identifier, array $objectData) {
		$classSchema = $this->reflectionService->getClassSchema($objectData['classname']);

		foreach ($objectData['properties'] as $propertyName => $propertyData) {
			if (!$classSchema->hasProperty($propertyName)) continue;
			$propertyValue = NULL;

			if ($propertyData['value'] !== NULL) {
				switch ($propertyData['type']) {
					case 'integer':
						$propertyValue = (int) $propertyData['value'];
					break;
					case 'float':
						$propertyValue = (float) $propertyData['value'];
					break;
					case 'boolean':
						$propertyValue = (boolean) $propertyData['value'];
					break;
					case 'string':
						$propertyValue = (string) $propertyData['value'];
					break;
					case 'array':
						$propertyValue = $this->mapArray($propertyData['value']);
					break;
					case 'Doctrine\Common\Collections\Collection':
					case 'Doctrine\Common\Collections\ArrayCollection':
						$propertyValue = new \Doctrine\Common\Collections\ArrayCollection($this->mapArray($propertyData['value']));
					break;
					case 'SplObjectStorage':
						$propertyMetaData = $classSchema->getProperty($propertyName);
						$propertyValue = $this->mapSplObjectStorage($propertyData['value'], $propertyMetaData['lazy']);
					break;
					case 'DateTime':
						$propertyValue = $this->mapDateTime($propertyData['value']);
					break;
					default:
						if ($propertyData['value'] === FALSE) {
							throw new \TYPO3\FLOW3\Persistence\Exception\UnknownObjectException('An expected object was not found by the backend. It was expected for ' . $objectData['classname'] . '::' . $propertyName, 1289509867);
						}
						$propertyValue = $this->mapToObject($propertyData['value']);
					break;
				}
			} else {
				switch ($propertyData['type']) {
					case 'NULL':
						continue;
					break;
					case 'array':
						$propertyValue = $this->mapArray(NULL);
					break;
					case 'Doctrine\Common\Collections\Collection':
					case 'Doctrine\Common\Collections\ArrayCollection':
						$propertyValue = new \Doctrine\Common\Collections\ArrayCollection();
					break;
					case 'SplObjectStorage':
						$propertyValue = $this->mapSplObjectStorage(NULL);
					break;
				}
			}

			\TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($object, $propertyName, $propertyValue, TRUE);
		}

		if (isset($objectData['metadata'])) {
			$object->FLOW3_Persistence_Metadata = $objectData['metadata'];
		}

		\TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($object, 'FLOW3_Persistence_Identifier', $identifier, TRUE);
	}

	/**
	 * Creates a \DateTime from an unix timestamp. If the input is not an integer
	 * NULL is returned.
	 *
	 * @param integer $timestamp
	 * @return \DateTime
	 */
	protected function mapDateTime($timestamp) {
		$datetime = new \DateTime();
		$datetime->setTimestamp((integer) $timestamp);
		return $datetime;
	}

	/**
	 * Maps an array proxy structure back to a native PHP array
	 *
	 * @param array $arrayValues
	 * @return array
	 */
	protected function mapArray(array $arrayValues = NULL) {
		if ($arrayValues === NULL) return array();

		$array = array();
		foreach ($arrayValues as $arrayValue) {
			if ($arrayValue['value'] === NULL) {
				$array[$arrayValue['index']] = NULL;
			} else {
				switch ($arrayValue['type']) {
					case 'integer':
						$array[$arrayValue['index']] = (int) $arrayValue['value'];
					break;
					case 'float':
						$array[$arrayValue['index']] = (float) $arrayValue['value'];
					break;
					case 'boolean':
						$array[$arrayValue['index']] = (boolean) $arrayValue['value'];
					break;
					case 'string':
						$array[$arrayValue['index']] = (string) $arrayValue['value'];
					break;
					case 'DateTime':
						$array[$arrayValue['index']] = $this->mapDateTime($arrayValue['value']);
					break;
					case 'array':
						$array[$arrayValue['index']] = $this->mapArray($arrayValue['value']);
					break;
					case 'SplObjectStorage':
						$array[$arrayValue['index']] = $this->mapSplObjectStorage($arrayValue['value']);
					break;
					default:
						$array[$arrayValue['index']] = $this->mapToObject($arrayValue['value']);
					break;
				}
			}
		}

		return $array;
	}

	/**
	 * Maps an SplObjectStorage proxy record back to an SplObjectStorage
	 *
	 * @param array $objectStorageValues
	 * @param boolean $createLazySplObjectStorage
	 * @return \SplObjectStorage
	 * @todo restore information attached to objects?
	 */
	protected function mapSplObjectStorage(array $objectStorageValues = NULL, $createLazySplObjectStorage = FALSE) {
		if ($objectStorageValues === NULL) return new \SplObjectStorage();

		if ($createLazySplObjectStorage) {
			$objectIdentifiers = array();
			foreach ($objectStorageValues as $arrayValue) {
				if ($arrayValue['value'] !== NULL) {
					$objectIdentifiers[] = $arrayValue['value']['identifier'];
				}
			}
			return new LazySplObjectStorage($objectIdentifiers);
		} else {
			$objectStorage = new \SplObjectStorage();

			foreach ($objectStorageValues as $arrayValue) {
				if ($arrayValue['value'] !== NULL) {
					$objectStorage->attach($this->mapToObject($arrayValue['value']));
				}
			}
			return $objectStorage;
		}
	}

}

?>
