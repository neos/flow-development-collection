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
 * A data mapper to map raw records to objects
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DataMapper {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Persistence\Session
	 */
	protected $persistenceSession;

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Persistence\PersistenceManager
	 */
	protected $persistenceManager;

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the persistence session
	 *
	 * @param \F3\FLOW3\Persistence\Session $persistenceManager The persistence session
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectPersistenceSession(\F3\FLOW3\Persistence\Session $persistenceManager) {
		$this->persistenceSession = $persistenceManager;
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
	 * Injects the persistence manager
	 *
	 * @param \F3\FLOW3\Persistence\PersistenceManager $persistenceManager The persistence manager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setPersistenceManager(\F3\FLOW3\Persistence\PersistenceManager $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Maps the (aggregate root) node data and registers the objects as
	 * reconstituted with the session.
	 *
	 * @param array $objectsData
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapToObject(array $objectData) {
		if ($objectData === array()) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidObjectDataException('The array with object data was empty, probably object not found or access denied.', 1277974338);
		}

		if ($this->persistenceSession->hasIdentifier($objectData['identifier'])) {
			return $this->persistenceSession->getObjectByIdentifier($objectData['identifier']);
		} else {
			$className = $objectData['classname'];
			$classSchema = $this->reflectionService->getClassSchema($className);

				// We expect that the object name === AOP proxy target class name of the model:
			$object = $this->objectManager->recreate($className);
			$this->persistenceSession->registerObject($object, $objectData['identifier']);
			if ($classSchema->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY) {
				$this->persistenceSession->registerReconstitutedEntity($object, $objectData);
			}
			if ($objectData['properties'] === array()) {
				if (!$classSchema->isLazyLoadableObject()) {
					throw new \F3\FLOW3\Persistence\Exception('The object of type "' . $className . '" is not marked as lazy loadable.', 1268309017);
				}
				$persistenceManager = $this->persistenceManager;
				$persistenceSession = $this->persistenceSession;
				$dataMapper = $this;
				$identifier = $objectData['identifier'];
				$modelType = $classSchema->getModelType();
				$object->FLOW3_Persistence_LazyLoadingObject_thawProperties = function () use ($persistenceManager, $persistenceSession, $dataMapper, $identifier, $object, $modelType) {
					$objectData = $persistenceManager->getObjectDataByIdentifier($identifier);
					$dataMapper->thawProperties($object, $identifier, $objectData);
					if ($modelType === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY) {
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
	 * @param \F3\FLOW3\AOP\ProxyInterface $object The object to set properties on
	 * @param string $identifier The identifier of the object
	 * @param array $objectData
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function thawProperties(\F3\FLOW3\AOP\ProxyInterface $object, $identifier, array $objectData) {
		$classSchema = $this->reflectionService->getClassSchema($objectData['classname']);
		$propertyValues = $objectData['properties'];

		foreach ($classSchema->getProperties() as $propertyName => $propertyData) {
			$propertyValue = NULL;
			if (isset($propertyValues[$propertyName])) {
				if ($propertyValues[$propertyName]['value'] !== NULL) {
					switch ($propertyValues[$propertyName]['type']) {
						case 'integer':
							$propertyValue = (int) $propertyValues[$propertyName]['value'];
						break;
						case 'float':
							$propertyValue = (float) $propertyValues[$propertyName]['value'];
						break;
						case 'boolean':
							$propertyValue = (boolean) $propertyValues[$propertyName]['value'];
						break;
						case 'string':
							$propertyValue = (string) $propertyValues[$propertyName]['value'];
						break;
						case 'array':
							$propertyValue = $this->mapArray($propertyValues[$propertyName]['value']);
						break;
						case 'SplObjectStorage':
							$propertyValue = $this->mapSplObjectStorage($propertyValues[$propertyName]['value'], $propertyData['lazy']);
						break;
						case 'DateTime':
							$propertyValue = $this->mapDateTime($propertyValues[$propertyName]['value']);
						break;
						default:
							$propertyValue = $this->mapToObject($propertyValues[$propertyName]['value']);
						break;
					}
				} else {
					switch ($propertyValues[$propertyName]['type']) {
						case 'NULL':
							continue;
						break;
						case 'array':
							$propertyValue = $this->mapArray(NULL);
						break;
						case 'SplObjectStorage':
							$propertyValue = $this->mapSplObjectStorage(NULL);
						break;
					}
				}

				$object->FLOW3_AOP_Proxy_setProperty($propertyName, $propertyValue);
			}
		}

		if ($classSchema->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY) {
			$uuidPropertyName = $classSchema->getUuidPropertyName();
			$object->FLOW3_AOP_Proxy_setProperty(($uuidPropertyName !== NULL ? $uuidPropertyName : 'FLOW3_Persistence_Entity_UUID'), $identifier);
		} else {
			$object->FLOW3_Persistence_ValueObject_Hash = $identifier;
		}
	}

	/**
	 * Creates a \DateTime from an unix timestamp. If the input is not an integer
	 * NULL is returned.
	 *
	 * @param integer $timestamp
	 * @return \DateTime
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo remove the check on the node/property names and use name pattern
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
			return $this->objectManager->get('F3\FLOW3\Persistence\LazySplObjectStorage', $objectIdentifiers);
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
