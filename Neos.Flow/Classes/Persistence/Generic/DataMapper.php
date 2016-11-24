<?php
namespace Neos\Flow\Persistence\Generic;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Exception\UnknownObjectException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ClassSchema;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Persistence\Exception as PersistenceException;

/**
 * A data mapper to map raw records to objects
 *
 * @Flow\Scope("singleton")
 */
class DataMapper
{
    /**
     * @var Session
     */
    protected $persistenceSession;

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Injects the persistence session
     *
     * @param Session $persistenceSession The persistence session
     * @return void
     */
    public function injectPersistenceSession(Session $persistenceSession)
    {
        $this->persistenceSession = $persistenceSession;
    }

    /**
     * Injects a Reflection Service instance used for processing objects
     *
     * @param ReflectionService $reflectionService
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Injects the persistence manager
     *
     * @param PersistenceManagerInterface $persistenceManager The persistence manager
     * @return void
     */
    public function setPersistenceManager(PersistenceManagerInterface $persistenceManager)
    {
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
    public function mapToObjects(array $objectsData)
    {
        $objects = [];
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
     * @throws Exception\InvalidObjectDataException
     * @throws PersistenceException
     */
    public function mapToObject(array $objectData)
    {
        if ($objectData === []) {
            throw new Exception\InvalidObjectDataException('The array with object data was empty, probably object not found or access denied.', 1277974338);
        }

        if ($this->persistenceSession->hasIdentifier($objectData['identifier'])) {
            return $this->persistenceSession->getObjectByIdentifier($objectData['identifier']);
        } else {
            $className = $objectData['classname'];
            $classSchema = $this->reflectionService->getClassSchema($className);

            $object = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
            $this->persistenceSession->registerObject($object, $objectData['identifier']);
            if ($classSchema->getModelType() === ClassSchema::MODELTYPE_ENTITY) {
                $this->persistenceSession->registerReconstitutedEntity($object, $objectData);
            }
            if ($objectData['properties'] === []) {
                if (!$classSchema->isLazyLoadableObject()) {
                    throw new PersistenceException('The object of type "' . $className . '" is not marked as lazy loadable.', 1268309017);
                }
                $persistenceManager = $this->persistenceManager;
                $persistenceSession = $this->persistenceSession;
                $dataMapper = $this;
                $identifier = $objectData['identifier'];
                $modelType = $classSchema->getModelType();
                $object->Flow_Persistence_LazyLoadingObject_thawProperties = function ($object) use ($persistenceManager, $persistenceSession, $dataMapper, $identifier, $modelType) {
                    $objectData = $persistenceManager->getObjectDataByIdentifier($identifier);
                    $dataMapper->thawProperties($object, $identifier, $objectData);
                    if ($modelType === ClassSchema::MODELTYPE_ENTITY) {
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
     * @throws UnknownObjectException
     */
    public function thawProperties($object, $identifier, array $objectData)
    {
        $classSchema = $this->reflectionService->getClassSchema($objectData['classname']);

        foreach ($objectData['properties'] as $propertyName => $propertyData) {
            if (!$classSchema->hasProperty($propertyName) ||
                $classSchema->isPropertyTransient($propertyName)) {
                continue;
            }
            $propertyValue = null;

            if ($propertyData['value'] !== null) {
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
                        $propertyValue = new ArrayCollection($this->mapArray($propertyData['value']));
                    break;
                    case 'SplObjectStorage':
                        $propertyValue = $this->mapSplObjectStorage($propertyData['value'], $classSchema->isPropertyLazy($propertyName));
                    break;
                    case 'DateTime':
                        $propertyValue = $this->mapDateTime($propertyData['value']);
                    break;
                    default:
                        if ($propertyData['value'] === false) {
                            throw new UnknownObjectException('An expected object was not found by the backend. It was expected for ' . $objectData['classname'] . '::' . $propertyName, 1289509867);
                        }
                        $propertyValue = $this->mapToObject($propertyData['value']);
                    break;
                }
            } else {
                switch ($propertyData['type']) {
                    case 'NULL':
                        continue;
                    case 'array':
                        $propertyValue = $this->mapArray(null);
                    break;
                    case Collection::class:
                    case ArrayCollection::class:
                        $propertyValue = new ArrayCollection();
                    break;
                    case 'SplObjectStorage':
                        $propertyValue = $this->mapSplObjectStorage(null);
                    break;
                }
            }

            ObjectAccess::setProperty($object, $propertyName, $propertyValue, true);
        }

        if (isset($objectData['metadata'])) {
            $object->Flow_Persistence_Metadata = $objectData['metadata'];
        }

        ObjectAccess::setProperty($object, 'Persistence_Object_Identifier', $identifier, true);
    }

    /**
     * Creates a \DateTime from an unix timestamp. If the input is not an integer
     * NULL is returned.
     *
     * @param integer $timestamp
     * @return \DateTime
     */
    protected function mapDateTime($timestamp)
    {
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
    protected function mapArray(array $arrayValues = null)
    {
        if ($arrayValues === null) {
            return [];
        }

        $array = [];
        foreach ($arrayValues as $arrayValue) {
            if ($arrayValue['value'] === null) {
                $array[$arrayValue['index']] = null;
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
    protected function mapSplObjectStorage(array $objectStorageValues = null, $createLazySplObjectStorage = false)
    {
        if ($objectStorageValues === null) {
            return new \SplObjectStorage();
        }

        if ($createLazySplObjectStorage) {
            $objectIdentifiers = [];
            foreach ($objectStorageValues as $arrayValue) {
                if ($arrayValue['value'] !== null) {
                    $objectIdentifiers[] = $arrayValue['value']['identifier'];
                }
            }
            return new LazySplObjectStorage($objectIdentifiers);
        } else {
            $objectStorage = new \SplObjectStorage();

            foreach ($objectStorageValues as $arrayValue) {
                if ($arrayValue['value'] !== null) {
                    $objectStorage->attach($this->mapToObject($arrayValue['value']));
                }
            }
            return $objectStorage;
        }
    }
}
