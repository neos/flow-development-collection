<?php
namespace Neos\Flow\ObjectManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\Configuration\Configuration;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\PropertyReflection;
use Neos\Flow\Reflection\ReflectionService;

/**
 * The object serializer. This serializer traverses an object tree and transforms
 * it into an array.
 * Dependant objects are only included if they are not singleton and the property
 * is not annotated transient.
 * Afterwards it can reconstitute the objects from the array.
 *
 * @Flow\Scope("singleton")
 */
class ObjectSerializer
{
    const TYPE = 't';
    const VALUE = 'v';
    const CLASSNAME = 'c';
    const PROPERTIES = 'p';

    /**
     * Objects stored as an array of properties
     * @var array
     */
    protected $objectsAsArray = [];

    /**
     * @var array
     */
    protected $reconstitutedObjects = [];

    /**
     * @var \SplObjectStorage
     */
    protected $objectReferences;

    /**
     * The object manager
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * The reflection service
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * The persistence manager
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Injects the object manager
     *
     * @param ObjectManagerInterface $objectManager The object manager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Injects the reflection service
     *
     * @param ReflectionService $reflectionService The reflection service
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Inject the persistence manager
     *
     * @param PersistenceManagerInterface $persistenceManager The persistence manager
     * @return void
     */
    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Clears the internal state, discarding all stored objects.
     *
     * @return void
     */
    public function clearState()
    {
        $this->objectsAsArray = [];
        $this->reconstitutedObjects = [];
    }

    /**
     * Serializes an object as property array.
     *
     * @param object $object The object to store in the registry
     * @param boolean $isTopLevelItem Internal flag for managing the recursion
     * @return array The property array
     */
    public function serializeObjectAsPropertyArray($object, $isTopLevelItem = true)
    {
        if ($isTopLevelItem) {
            $this->objectReferences = new \SplObjectStorage();
        }
        $this->objectReferences->attach($object);

        $className = get_class($object);
        $propertyArray = [];
        foreach ($this->reflectionService->getClassPropertyNames($className) as $propertyName) {
            if ($this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'transient')) {
                continue;
            }

            $propertyReflection = new PropertyReflection($className, $propertyName);
            $propertyValue = $propertyReflection->getValue($object);

            if (is_object($propertyValue) && $propertyValue instanceof DependencyInjection\DependencyProxy) {
                continue;
            }

            if (is_object($propertyValue) && isset($this->objectReferences[$propertyValue])) {
                $propertyArray[$propertyName][self::TYPE] = 'object';
                $propertyArray[$propertyName][self::VALUE] = \spl_object_hash($propertyValue);
                continue;
            }

            $propertyClassName = (is_object($propertyValue)) ? get_class($propertyValue) : '';

            if ($propertyClassName === 'SplObjectStorage') {
                $propertyArray[$propertyName][self::TYPE] = 'SplObjectStorage';
                $propertyArray[$propertyName][self::VALUE] = [];

                foreach ($propertyValue as $storedObject) {
                    $propertyArray[$propertyName][self::VALUE][] = spl_object_hash($storedObject);
                    $this->serializeObjectAsPropertyArray($storedObject, false);
                }
            } elseif (is_object($propertyValue) && $propertyValue instanceof \Doctrine\Common\Collections\Collection) {
                $propertyArray[$propertyName][self::TYPE] = 'Collection';
                $propertyArray[$propertyName][self::CLASSNAME] = get_class($propertyValue);
                foreach ($propertyValue as $storedObject) {
                    $propertyArray[$propertyName][self::VALUE][] = spl_object_hash($storedObject);
                    $this->serializeObjectAsPropertyArray($storedObject, false);
                }
            } elseif (is_object($propertyValue) && $propertyValue instanceof \ArrayObject) {
                $propertyArray[$propertyName][self::TYPE] = 'ArrayObject';
                $propertyArray[$propertyName][self::VALUE] = $this->buildStorageArrayForArrayProperty($propertyValue->getArrayCopy());
            } elseif (is_object($propertyValue)
                        && $this->persistenceManager->isNewObject($propertyValue) === false
                        && (
                            $this->reflectionService->isClassAnnotatedWith($propertyClassName, Flow\Entity::class)
                            || $this->reflectionService->isClassAnnotatedWith($propertyClassName, Flow\ValueObject::class)
                            || $this->reflectionService->isClassAnnotatedWith($propertyClassName, ORM\Entity::class)
                        )) {
                $propertyArray[$propertyName][self::TYPE] = 'persistenceObject';
                $propertyArray[$propertyName][self::VALUE] = get_class($propertyValue) . ':' . $this->persistenceManager->getIdentifierByObject($propertyValue);
            } elseif (is_object($propertyValue)) {
                $propertyObjectName = $this->objectManager->getObjectNameByClassName($propertyClassName);
                if ($this->objectManager->getScope($propertyObjectName) === Configuration::SCOPE_SINGLETON) {
                    continue;
                }

                $propertyArray[$propertyName][self::TYPE] = 'object';
                $propertyArray[$propertyName][self::VALUE] = spl_object_hash($propertyValue);
                $this->serializeObjectAsPropertyArray($propertyValue, false);
            } elseif (is_array($propertyValue)) {
                $propertyArray[$propertyName][self::TYPE] = 'array';
                $propertyArray[$propertyName][self::VALUE] = $this->buildStorageArrayForArrayProperty($propertyValue);
            } else {
                $propertyArray[$propertyName][self::TYPE] = 'simple';
                $propertyArray[$propertyName][self::VALUE] = $propertyValue;
            }
        }

        $this->objectsAsArray[spl_object_hash($object)] = [
            self::CLASSNAME => $className,
            self::PROPERTIES => $propertyArray
        ];

        if ($isTopLevelItem) {
            return $this->objectsAsArray;
        }
    }

    /**
     * Builds a storable array out of an array property. It calls itself recursively
     * for multidimensional arrays. For objects putObject() ist called with the object's
     * hash value as $objectName.
     *
     * @param array $arrayProperty The source array property
     * @return array The array property to store
     */
    protected function buildStorageArrayForArrayProperty(array $arrayProperty)
    {
        $storableArray = [];

        foreach ($arrayProperty as $key => $value) {
            $storableArray[$key] = [];

            if (is_array($value)) {
                $storableArray[$key][self::TYPE] = 'array';
                $storableArray[$key][self::VALUE] = $this->buildStorageArrayForArrayProperty($value);
            } elseif (is_object($value)) {
                $storableArray[$key][self::TYPE] = 'object';
                $storableArray[$key][self::VALUE] = spl_object_hash($value);

                $this->serializeObjectAsPropertyArray($value, false);
            } else {
                $storableArray[$key][self::TYPE] = 'simple';
                $storableArray[$key][self::VALUE] = $value;
            }
        }

        return $storableArray;
    }

    /**
     * Deserializes a given object tree and reinjects all dependencies.
     *
     * @param array $dataArray The serialized objects array
     * @return array The deserialized objects in an array
     */
    public function deserializeObjectsArray(array $dataArray)
    {
        $this->objectsAsArray = $dataArray;
        $objects = [];

        foreach ($this->objectsAsArray as $objectHash => $objectData) {
            if (!isset($objectData[self::CLASSNAME]) || !$this->objectManager->isRegistered($objectData[self::CLASSNAME])) {
                continue;
            }
            $objects[$objectHash] = $this->reconstituteObject($objectHash, $objectData);
        }

        return $objects;
    }

    /**
     * Reconstitutes an object from a serialized object without calling the constructor.
     *
     * @param string $objectHash Identifier of the serialized object
     * @param array $objectData The object data array
     * @return object
     */
    protected function reconstituteObject($objectHash, array $objectData)
    {
        if (isset($this->reconstitutedObjects[$objectHash])) {
            return $this->reconstitutedObjects[$objectHash];
        }

        $className = $this->objectManager->getClassNameByObjectName($objectData[self::CLASSNAME]);
        $object = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
        $this->reconstitutedObjects[$objectHash] = $object;

        foreach ($objectData[self::PROPERTIES] as $propertyName => $propertyData) {
            switch ($propertyData[self::TYPE]) {
                case 'simple':
                    $propertyValue = $propertyData[self::VALUE];
                    break;
                case 'array':
                    $propertyValue = $this->reconstituteArray($propertyData[self::VALUE]);
                    break;
                case 'Collection':
                    $propertyValue = $this->reconstituteCollection($propertyData[self::CLASSNAME], $propertyData[self::VALUE]);
                    break;
                case 'ArrayObject':
                    $propertyValue = new \ArrayObject($this->reconstituteArray($propertyData[self::VALUE]));
                    break;
                case 'object':
                    $propertyValue = $this->reconstituteObject($propertyData[self::VALUE], $this->objectsAsArray[$propertyData[self::VALUE]]);
                    break;
                case 'SplObjectStorage':
                    $propertyValue = $this->reconstituteSplObjectStorage($propertyData[self::VALUE]);
                    break;
                case 'persistenceObject':
                    list($propertyClassName, $propertyUuid) = explode(':', $propertyData[self::VALUE]);
                    $propertyValue = $this->reconstitutePersistenceObject($propertyClassName, $propertyUuid);
                    break;
            }

            $reflectionProperty = new \ReflectionProperty(get_class($object), $propertyName);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($object, $propertyValue);
        }

        return $object;
    }

    /**
     * Reconstitutes an array from a data array.
     *
     * @param array $dataArray The data array to reconstitute from
     * @return array The reconstituted array
     */
    protected function reconstituteArray($dataArray)
    {
        $result = [];

        foreach ($dataArray as $key => $entryData) {
            $value = null;

            switch ($entryData[self::TYPE]) {
                case 'simple':
                    $value = $entryData[self::VALUE];
                    break;
                case 'array':
                    $value = $this->reconstituteArray($entryData[self::VALUE]);
                    break;
                case 'object':
                    $value = $this->reconstituteObject($entryData[self::VALUE], $this->objectsAsArray[$entryData[self::VALUE]]);
                    break;
                case 'SplObjectStorage':
                    $value = $this->reconstituteSplObjectStorage($this->objectsAsArray[$entryData[self::VALUE]]);
                    break;
                case 'Collection':
                    $value = $this->reconstituteCollection($entryData[self::CLASSNAME], $entryData[self::VALUE]);
                    break;
                case 'persistenceObject':
                    $value = $this->reconstitutePersistenceObject($entryData[self::VALUE][self::CLASSNAME], $entryData[self::VALUE]['UUID']);
                    break;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Reconstitutes a Doctrine Collection from a data array.
     *
     * @param string $type The collection type (class name) to create
     * @param array $dataArray The data array to reconstitute from
     * @return \Doctrine\Common\Collections\Collection The reconstituted Collection
     */
    protected function reconstituteCollection($type, array $dataArray)
    {
        $result = new $type();

        foreach ($dataArray as $objectHash) {
            $result->add($this->reconstituteObject($objectHash, $this->objectsAsArray[$objectHash]));
        }

        return $result;
    }

    /**
     * Reconstitutes an SplObjectStorage from a data array.
     *
     * @param array $dataArray The data array to reconstitute from
     * @return \SplObjectStorage The reconstituted SplObjectStorage
     */
    protected function reconstituteSplObjectStorage(array $dataArray)
    {
        $result = new \SplObjectStorage();

        foreach ($dataArray as $objectHash) {
            $result->attach($this->reconstituteObject($objectHash, $this->objectsAsArray[$objectHash]));
        }

        return $result;
    }

    /**
     * Reconstitutes a persistence object (entity or valueobject) identified by the given UUID.
     *
     * @param string $className The class name of the object to retrieve
     * @param string $uuid The UUID of the object
     * @return object The reconstituted persistence object, NULL if none was found
     */
    protected function reconstitutePersistenceObject($className, $uuid)
    {
        return $this->persistenceManager->getObjectByIdentifier($uuid, $className);
    }
}
