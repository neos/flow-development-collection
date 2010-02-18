<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Backend\GenericPdo;

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
class DataMapper implements \F3\FLOW3\Persistence\DataMapperInterface {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Object\ObjectBuilder
	 */
	protected $objectBuilder;

	/**
	 * @var \F3\FLOW3\Persistence\Session
	 */
	protected $persistenceSession;

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

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
	 * Injects the object builder
	 *
	 * @param \F3\FLOW3\Object\ObjectBuilder $objectBuilder
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectBuilder(\F3\FLOW3\Object\ObjectBuilder $objectBuilder) {
		$this->objectBuilder = $objectBuilder;
	}

	/**
	 * Injects the persistence session
	 *
	 * @param \F3\FLOW3\Persistence\Session $persistenceSession The persistence session
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectPersistenceSession(\F3\FLOW3\Persistence\Session $persistenceSession) {
		$this->persistenceSession = $persistenceSession;
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
		if ($this->persistenceSession->hasIdentifier($objectData['identifier'])) {
			return $this->persistenceSession->getObjectByIdentifier($objectData['identifier']);
		} else {
			$className = $objectData['classname'];
			$classSchema = $this->reflectionService->getClassSchema($className);
			$objectConfiguration = $this->objectManager->getObjectConfiguration($className);

			$object = $this->objectBuilder->createEmptyObject($className, $objectConfiguration);
			$this->persistenceSession->registerObject($object, $objectData['identifier']);
			if ($classSchema->getModelType() === \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY) {
				$this->persistenceSession->registerReconstitutedEntity($object, $objectData);
			}

			$this->objectBuilder->reinjectDependencies($object, $objectConfiguration);
			$this->thawProperties($object, $objectData['identifier'], $objectData, $classSchema);

			return $object;
		}
	}

	/**
	 * Sets the given properties on the object.
	 *
	 * @param \F3\FLOW3\AOP\ProxyInterface $object The object to set properties on
	 * @param string $identifier The identifier of the object
	 * @param array $objectData
	 * @param \F3\FLOW3\Reflection\ClassSchema $classSchema
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function thawProperties(\F3\FLOW3\AOP\ProxyInterface $object, $identifier, array $objectData, \F3\FLOW3\Reflection\ClassSchema $classSchema) {
		$propertyValues = $objectData['properties'];
		foreach ($classSchema->getProperties() as $propertyName => $propertyData) {
			$propertyValue = NULL;
			if (isset($propertyValues[$propertyName])) {
				if ($propertyValues[$propertyName]['value'] !== NULL) {
					switch ($propertyData['type']) {
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
							$propertyValue = $this->mapSplObjectStorage($propertyValues[$propertyName]['value']);
						break;
						case 'DateTime':
							$propertyValue = $this->mapDateTime($propertyValues[$propertyName]['value']);
						break;
						default:
							$propertyValue = $this->mapToObject($propertyValues[$propertyName]['value']);
						break;
					}
				} else {
					switch ($propertyData['type']) {
						case 'array':
							$propertyValue = $this->mapArray($propertyValues[$propertyName]['value']);
						break;
						case 'SplObjectStorage':
							$propertyValue = $this->mapSplObjectStorage($propertyValues[$propertyName]['value']);
						break;
					}
				}

				if ($propertyValue !== NULL) {
					$object->FLOW3_AOP_Proxy_setProperty($propertyName, $propertyValue);
				}
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
						throw new \RuntimeException('no nested arrays, please', 1260541003);
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
	 * @param array $arrayValues
	 * @return \SplObjectStorage
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo restore information attached to objects?
	 */
	protected function mapSplObjectStorage(array $arrayValues = NULL) {
		$objectStorage = new \SplObjectStorage();
		if ($arrayValues === NULL) return $objectStorage;

		foreach ($arrayValues as $arrayValue) {
			if ($arrayValue['value'] !== NULL) {
				$objectStorage->attach($this->mapToObject($arrayValue['value']));
			}
		}

		return $objectStorage;
	}

}

?>
