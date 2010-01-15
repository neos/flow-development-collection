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
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Object\Builder
	 */
	protected $objectBuilder;

	/**
	 * @var \F3\FLOW3\Persistence\Session
	 */
	protected $persistenceSession;

	/**
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the object builder
	 *
	 * @param \F3\FLOW3\Object\Builder $objectBuilder
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectBuilder(\F3\FLOW3\Object\Builder $objectBuilder) {
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
	 * @param \F3\FLOW3\Reflection\Service $reflectionService
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Maps the (aggregate root) nodes and registers them as reconstituted
	 * with the session.
	 *
	 * @param array $objectRows
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function map(array $objectRows) {
		$objects = array();
		foreach ($objectRows as $objectRow) {
			$objects[] = $this->mapSingleObject($objectRow);
		}

		return $objects;
	}

	/**
	 * Maps a single record into the object it represents
	 *
	 * @param array $objectData
	 * @return object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapSingleObject(array $objectData) {
		if ($this->persistenceSession->hasIdentifier($objectData['identifier'])) {
			return $this->persistenceSession->getObjectByIdentifier($objectData['identifier']);
		} else {
			$className = $objectData['classname'];
			$classSchema = $this->reflectionService->getClassSchema($className);
			$objectConfiguration = $this->objectManager->getObjectConfiguration($className);

			$object = $this->objectBuilder->createEmptyObject($className, $objectConfiguration);
			$this->persistenceSession->registerObject($object, $objectData['identifier']);

			$this->objectBuilder->reinjectDependencies($object, $objectConfiguration);
			$this->thawProperties($object, $objectData['identifier'], $objectData, $classSchema);
			$object->FLOW3_Persistence_memorizeCleanState();
			$this->persistenceSession->registerReconstitutedObject($object);

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
		$propertyValues = $objectData['propertyData'];
		foreach ($classSchema->getProperties() as $propertyName => $propertyData) {
			$propertyValue = NULL;
			if (isset($propertyValues[$propertyName]) && $propertyValues[$propertyName]['value'] !== NULL) {
				switch ($propertyData['type']) {
					case 'integer':
						$propertyValue = (int) $propertyValues[$propertyName]['value']['value'];
					break;
					case 'float':
						$propertyValue = (float) $propertyValues[$propertyName]['value']['value'];
					break;
					case 'boolean':
						$propertyValue = (boolean) $propertyValues[$propertyName]['value']['value'];
					break;
					case 'string':
						$propertyValue = (string) $propertyValues[$propertyName]['value']['value'];
					break;
					case 'array':
						$propertyValue = $this->mapArray($propertyValues[$propertyName]['value']);
					break;
					case 'DateTime':
						$propertyValue = $this->mapDateTime($propertyValues[$propertyName]['value']['value']);
					break;
					case 'SplObjectStorage':
						$propertyValue = $this->mapSplObjectStorage($propertyValues[$propertyName]['value']);
					break;
					default:
						$propertyValue = $this->mapSingleObject($propertyValues[$propertyName]['value']['value']);
					break;
				}

				if ($propertyValue !== NULL) {
					$object->FLOW3_AOP_Proxy_setProperty($propertyName, $propertyValue);
				}
			}
		}

		$uuidPropertyName = $classSchema->getUUIDPropertyName();
		$object->FLOW3_AOP_Proxy_setProperty(($uuidPropertyName !== NULL ? $uuidPropertyName : 'FLOW3_Persistence_Entity_UUID'), $identifier);
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
		if ($timestamp !== NULL) {
			$datetime = new \DateTime();
			$datetime->setTimestamp((integer) $timestamp);
			return $datetime;
		} else {
			return NULL;
		}
	}

	/**
	 * Maps an array proxy structure back to a native PHP array
	 *
	 * @param array $arrayValues
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo remove the check on the node/property names and use name pattern
	 */
	protected function mapArray(array $arrayValues) {
		$array = array();
		foreach ($arrayValues as $key => $arrayValue) {
			switch ($arrayValue['type']) {
				case 'integer':
				case 'float':
				case 'boolean':
				case 'string':
					$array[$key] = $arrayValue['value'];
				break;
				case 'DateTime':
					$array[$key] = $this->mapDateTime($arrayValue['value']);
				break;
				case 'array':
					throw new \RuntimeException('no nested arrays, please', 1260541003);
				break;
				case 'SplObjectStorage':
						$array[$key] = $this->mapSplObjectStorage($arrayValue['value']);
				break;
				default:
					$array[$key] = $this->mapSingleObject($arrayValue['value']);
				break;
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
	protected function mapSplObjectStorage(array $arrayValues) {
		$objectStorage = new \SplObjectStorage();
		foreach ($arrayValues as $arrayValue) {
			$objectStorage->attach($this->mapSingleObject($arrayValue['value']));
		}

		return $objectStorage;
	}

}

?>
