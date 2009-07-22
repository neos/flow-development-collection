<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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
 * The object serializer. This serializer traverses an object tree and transforms
 * it into an array.
 * Dependant objects are only included if they are not singleton and the property
 * is not annotated transient.
 * Afterwards it can reconstitute the objects from the array.
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id: TransientRegistry.php 2293 2009-05-20 18:14:45Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ObjectSerializer {

	/**
	 * Location where the objects are stored as property array
	 * @var array
	 */
	protected $objectsAsArray = array();

	/**
	 * The object manager
	 * @var F3\FLOW3\Object\Manager
	 */
	protected $objectManager;

	/**
	 * The object builder
	 * @var F3\FLOW3\Object\Builder
	 */
	protected $objectBuilder;

	/**
	 * The reflection service
	 * @var F3\FLOW3\Reflection\ServiceInterface
	 */
	protected $reflectionService;

	/**
	 * The persistence manager
	 * @var F3\FLOW3\Persistence\ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * The query factory
	 * @var F3\FLOW3\Persistence\QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * Injects the object manager
	 *
	 * @param F3\FLOW3\Object\Manager $objectManager The object manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\Manager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the object builder
	 *
	 * @param F3\FLOW3\Object\Builder $objectBuilder The object builder
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectObjectBuilder(\F3\FLOW3\Object\Builder $objectBuilder) {
		$this->objectBuilder = $objectBuilder;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param F3\FLOW3\Reflection\Service $reflectionService The reflection service
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Inject the persistence manager
	 *
	 * @param F3\FLOW3\Persistence\ManagerInterface $persistenceManager The persistence manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\ManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Injects the query factory
	 *
	 * @param F3\FLOW3\Persistence\QueryFactoryInterface $queryFactory The query factory
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectQueryFactory(\F3\FLOW3\Persistence\QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
	}

	/**
	 *
	 *
	 * @return
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function clearState() {
		unset($this->objectsAsArray);
	}

	/**
	 * Serializes an object as property array.
	 *
	 * @param string $objectName Name of the object the object is made for
	 * @param object $object The object to store in the registry
	 * @return array The property array
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function serializeObjectAsPropertyArray($objectName, $object) {
		if (isset($this->objectsAsArray[$objectName])) return $this->objectsAsArray;

		$className = get_class($object);

		$propertyArray = array();
		foreach ($this->reflectionService->getClassPropertyNames($className) as $propertyName) {
			$propertyValue = '';

			if ($this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'transient')) continue;

			if ($object instanceof \F3\FLOW3\AOP\ProxyInterface) {
				$propertyValue = $object->FLOW3_AOP_Proxy_getProperty($propertyName);

			} else {
				$propertyReflection = new \F3\FLOW3\Reflection\PropertyReflection($className, $propertyName);
				$propertyValue = $propertyReflection->getValue($object);
			}

			$propertyClassName = '';
			if (is_object($propertyValue)) $propertyClassName = get_class($propertyValue);

			if (is_object($propertyValue) && $propertyClassName === 'SplObjectStorage') {
				$propertyArray[$propertyName]['type'] = 'SplObjectStorage';
				$propertyArray[$propertyName]['value'] = array();

				foreach ($propertyValue as $storedObject) {
					$objectHash = spl_object_hash($storedObject);
					$propertyArray[$propertyName]['value'][] = $objectHash;
					$this->serializeObjectAsPropertyArray($objectHash, $storedObject);
				}

			} else if (is_object($propertyValue)
						&& $propertyValue instanceof \F3\FLOW3\AOP\ProxyInterface
						&& $propertyValue instanceof \F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface
						&& $this->persistenceManager->getBackend()->isNewObject($propertyValue) === FALSE
						&& ($this->reflectionService->isClassTaggedWith($propertyClassName, 'entity')
							|| $this->reflectionService->isClassTaggedWith($propertyClassName, 'valueobject'))) {

				$propertyArray[$propertyName]['type'] = 'persistenceObject';
				$propertyArray[$propertyName]['value']['className'] = $propertyValue->FLOW3_AOP_Proxy_getProxyTargetClassName();
				$propertyArray[$propertyName]['value']['UUID'] = $this->persistenceManager->getBackend()->getIdentifierByObject($propertyValue);

			} else if (is_object($propertyValue)) {
				$propertyObjectName = $this->objectManager->getObjectNameByClassName($propertyClassName);
				$objectConfiguration = $this->objectManager->getObjectConfiguration($propertyObjectName);
				if ($objectConfiguration->getScope() === 'singleton') continue;

				$objectHash = spl_object_hash($propertyValue);
				$propertyArray[$propertyName]['type'] = 'object';
				$propertyArray[$propertyName]['value'] = $objectHash;
				$this->serializeObjectAsPropertyArray($objectHash, $propertyValue);

			} else if (is_array($propertyValue)) {
				$propertyArray[$propertyName]['type'] = 'array';
				$propertyArray[$propertyName]['value'] = $this->buildStorageArrayForArrayProperty($propertyValue);

			} else {
				$propertyArray[$propertyName]['type'] = 'simple';
				$propertyArray[$propertyName]['value'] = $propertyValue;
			}
		}

		$this->objectsAsArray[$objectName] = array(
			'className' => $className,
			'properties' => $propertyArray
		);

		return $this->objectsAsArray;
	}

	/**
	 * Deserializes a given object tree and reinjects all dependencies.
	 *
	 * @param array $dataArray The serialized objects array
	 * @return array The deserialized objects in an array
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function deserializeObjectsArray(array $dataArray) {
		$this->objectsAsArray = $dataArray;
		$objects = array();

		foreach ($this->objectsAsArray as $objectName => $objectData) {
			if (!$this->objectManager->isObjectRegistered($objectName)) continue;
			$objects[$objectName] = $this->reconstituteObject($objectData);
		}

		return $objects;
	}

	/**
	 * Builds a storable array out of an array property. It calls itself recursively
	 * for multidimensional arrays. For objects putObject() ist called with the object's
	 * hash value as $objectName.
	 *
	 * @param array $arrayProperty The source array property
	 * @return array The array property to store
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function buildStorageArrayForArrayProperty(array $arrayProperty) {
		$storableArray = array();

		foreach ($arrayProperty as $key => $value) {
			$storableArray[$key] = array();

			if (is_array($value)) {
				$storableArray[$key]['type'] = 'array';
				$storableArray[$key]['value'] = $this->buildStorageArrayForArrayProperty($value);
			} else if (is_object($value)) {
				$objectName = spl_object_hash($value);

				$storableArray[$key]['type'] = 'object';
				$storableArray[$key]['value'] = $objectName;

				$this->serializeObjectAsPropertyArray($objectName, $value);
			} else {
				$storableArray[$key]['type'] = 'simple';
				$storableArray[$key]['value'] = $value;
			}
		}

		return $storableArray;
	}

	/**
	 * Reconstitutes an empty object without calling the constructor.
	 *
	 * @param string $className The class of which the object should be created
	 * @return object The created object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function createEmptyObject($className) {
		if (!class_exists($className)) throw new \F3\FLOW3\Object\Exception\UnknownClass('Could not reconstitute object from the session, because class "' . $className . '" does not exist.', 1246717390);

		return unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
	}

	/**
	 * Reconstitutes an object from a serialized object without calling the constructor.
	 *
	 * @param object $object The object to reconstitute the properties in
	 * @param array $objectData The object data array
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function reconstituteObject($objectData) {
		$object = $this->createEmptyObject($objectData['className']);

		foreach ($objectData['properties'] as $propertyName => $propertyData) {
			switch($propertyData['type']) {
				case 'simple':
					$propertyValue = $propertyData['value'];
					break;
				case 'array':
					$propertyValue = $this->reconstituteArray($propertyData['value']);
					break;
				case 'object':
					$propertyValue = $this->reconstituteObject($this->objectsAsArray[$propertyData['value']]);
					break;
				case 'SplObjectStorage':
					$propertyValue = $this->reconstituteSplObjectStorage($propertyData['value']);
					break;
				case 'persistenceObject':
					$propertyValue = $this->reconstitutePersistenceObject($propertyData['value']['className'], $propertyData['value']['UUID']);
					break;
			}

			$reflectionProperty = new \ReflectionProperty(get_class($object), $propertyName);
			$reflectionProperty->setAccessible(TRUE);
			$reflectionProperty->setValue($object, $propertyValue);
		}

		$objectName = $this->objectManager->getObjectNameByClassName($objectData['className']);
		$objectConfigruation = $this->objectManager->getObjectConfiguration($objectName);
		$this->objectBuilder->reinjectDependencies($object, $objectConfigruation);
		$this->objectManager->registerShutdownObject($object, $objectConfigruation->getLifecycleShutdownMethodName());

		return $object;
	}

	/**
	 * Reconstitutes an array from a data array.
	 *
	 * @param array $dataArray The data array to reconstitute from
	 * @return array The reconstituted array
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function reconstituteArray($dataArray) {
		$result = array();

		foreach ($dataArray as $key => $entryData) {
			$value = NULL;

			switch($entryData['type']) {
				case 'simple':
					$value = $entryData['value'];
					break;
				case 'array':
					$value = $this->reconstituteArray($entryData['value']);
					break;
				case 'object':
					$value = $this->reconstituteObject($this->objectsAsArray[$entryData['value']]);
					break;
				case 'SplObjectStorage':
					$value = $this->reconstituteSplObjectStorage($this->objectsAsArray[$entryData['value']]['className'], $this->objectsAsArray[$entryData['value']]);
					break;
				case 'persistenceObject':
					$value = $this->reconstitutePersistenceObject($entryData['value']['className'], $entryData['value']['UUID']);
					break;
			}

			$result[$key] = $value;
		}

		return $result;
	}

	/**
	 * Reconstitutes an SplObjectStorage from a data array.
	 *
	 * @param array $dataArray The data array to reconstitute from
	 * @return SplObjectStorage The reconstituted SplObjectStorage
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function reconstituteSplObjectStorage($dataArray) {
		$result = new \SplObjectStorage();

		foreach ($dataArray as $objectName) {
			$result->attach($this->reconstituteObject($this->objectsAsArray[$objectName]));
		}

		return $result;
	}

	/**
	 * Reconstitutes a persistence object (entity or valueobject) identified by the given UUID.
	 *
	 * @param string $className The class name of the object to retrieve
	 * @param string $UUID The UUID of the object
	 * @return object The reconstituted persistence object, NULL if none was found
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function reconstitutePersistenceObject($className, $UUID) {
		$query = $this->queryFactory->create($className);
		$objects = $query->matching($query->withUUID($UUID))->execute();
		if (count($objects) === 1) return current($objects);
		return NULL;
	}
}

?>