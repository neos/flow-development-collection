<?php
namespace Neos\Flow\Persistence\Generic\Backend;

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
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Persistence\Aspect\PersistenceMagicInterface;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\Exception\ObjectValidationFailedException;
use Neos\Flow\Persistence\Generic\Exception\UnexpectedTypeException;
use Neos\Flow\Persistence\Generic\LazySplObjectStorage;
use Neos\Flow\Persistence\Generic\Session;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ClassSchema;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\TypeHandling;
use Neos\Flow\Validation\ValidatorResolver;
use Neos\Flow\Persistence\Exception as PersistenceException;

/**
 * An abstract storage backend for the Flow persistence
 *
 * @api
 */
abstract class AbstractBackend implements BackendInterface
{
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
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var Session
     */
    protected $persistenceSession;

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var ValidatorResolver
     */
    protected $validatorResolver;

    /**
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

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
     * @var \SplObjectStorage
     */
    protected $changedEntities;

    /**
     * @var array
     */
    protected $classSchemata = [];

    /**
     * Constructs the backend
     *
     */
    public function __construct()
    {
        $this->aggregateRootObjects = new \SplObjectStorage();
        $this->deletedEntities = new \SplObjectStorage();
        $this->changedEntities = new \SplObjectStorage();
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
     * Injects the persistence session
     *
     * @param Session $persistenceSession
     * @return void
     */
    public function injectPersistenceSession(Session $persistenceSession)
    {
        $this->persistenceSession = $persistenceSession;
    }

    /**
     * Set a PersistenceManager instance.
     *
     * @param PersistenceManagerInterface $persistenceManager
     * @return void
     */
    public function setPersistenceManager(PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Injects the ValidatorResolver
     *
     * @param ValidatorResolver $validatorResolver
     * @return void
     */
    public function injectValidatorResolver(ValidatorResolver $validatorResolver)
    {
        $this->validatorResolver = $validatorResolver;
    }

    /**
     * Injects the system logger
     *
     * @param SystemLoggerInterface $systemLogger
     * @return void
     */
    public function injectSystemLogger(SystemLoggerInterface $systemLogger)
    {
        $this->systemLogger = $systemLogger;
    }

    /**
     * Signalizes that the given object has been removed
     *
     * @param object $object The object that will be removed
     * @return void
     * @Flow\Signal
     * @api
     */
    protected function emitRemovedObject($object)
    {
    }

    /**
     * Signalizes that the given object has been persisted
     *
     * @param object $object The object that will be persisted
     * @param integer $objectState The state, see self::OBJECTSTATE_*
     * @return void
     * @Flow\Signal
     * @api
     */
    protected function emitPersistedObject($object, $objectState)
    {
    }

    /**
     * Initializes the backend
     *
     * @param array $options
     * @return void
     */
    public function initialize(array $options)
    {
        foreach ($options as $optionName => $optionValue) {
            $methodName = 'set' . ucfirst($optionName);
            if (method_exists($this, $methodName)) {
                $this->$methodName($optionValue);
            }
        }
    }

    /**
     * Sets the aggregate root objects
     *
     * @param \SplObjectStorage $objects
     * @return void
     */
    public function setAggregateRootObjects(\SplObjectStorage $objects)
    {
        $this->aggregateRootObjects = $objects;
    }

    /**
     * Sets the changed objects
     *
     * @param \SplObjectStorage $entities
     * @return void
     */
    public function setChangedEntities(\SplObjectStorage $entities)
    {
        $this->changedEntities = $entities;
    }

    /**
     * Sets the deleted objects
     *
     * @param \SplObjectStorage $entities
     * @return void
     */
    public function setDeletedEntities(\SplObjectStorage $entities)
    {
        $this->deletedEntities = $entities;
    }

    /**
     * Commits the current persistence session.
     *
     * @return void
     */
    public function commit()
    {
        $this->persistObjects();
        $this->processDeletedObjects();
    }

    /**
     * Returns TRUE, if an active connection to the persistence
     * backend has been established, e.g. entities can be persisted.
     *
     * @return boolean TRUE, if an connection has been established, FALSE if add object will not be persisted by the backend
     * @api
     */
    public function isConnected()
    {
        return false;
    }

    /**
     * First persist new objects, then check reconstituted entities.
     *
     * @return void
     */
    protected function persistObjects()
    {
        $this->visitedDuringPersistence = new \SplObjectStorage();
        foreach ($this->aggregateRootObjects as $object) {
            $this->persistObject($object, null);
        }
        foreach ($this->changedEntities as $object) {
            $this->persistObject($object, null);
        }
    }

    /**
     * Stores or updates an object in the underlying storage.
     *
     * @param object $object The object to persist
     * @param string $parentIdentifier
     * @return string
     * @api
     */
    protected function persistObject($object, $parentIdentifier)
    {
        if (isset($this->visitedDuringPersistence[$object])) {
            return $this->visitedDuringPersistence[$object];
        }

        $identifier = $this->persistenceSession->getIdentifierByObject($object);
        $this->visitedDuringPersistence[$object] = $identifier;

        $objectData = [];
        $objectState = $this->storeObject($object, $identifier, $parentIdentifier, $objectData);

        if ($this->reflectionService->getClassSchema($object)->getModelType() === ClassSchema::MODELTYPE_ENTITY) {
            $this->persistenceSession->registerReconstitutedEntity($object, $objectData);
        }
        $this->emitPersistedObject($object, $objectState);

        return $identifier;
    }

    /**
     * Actually store an object, backend-specific
     *
     * @param object $object
     * @param string $identifier
     * @param string $parentIdentifier
     * @param array $objectData
     * @return integer one of self::OBJECTSTATE_*
     */
    abstract protected function storeObject($object, $identifier, $parentIdentifier, array &$objectData);

    /**
     * Iterate over deleted entities and process them
     *
     * @return void
     */
    protected function processDeletedObjects()
    {
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
     * Remove a value object
     *
     * @param object $object
     * @return void
     */
    abstract protected function removeValueObject($object);

    /**
     * Validates the given object and throws an exception if validation fails.
     *
     * @param object $object
     * @return void
     * @throws ObjectValidationFailedException
     * @api
     */
    protected function validateObject($object)
    {
        $classSchema = $this->reflectionService->getClassSchema($object);
        $validator = $this->validatorResolver->getBaseValidatorConjunction($classSchema->getClassName());
        if ($validator === null) {
            return;
        }
        $validationResult = $validator->validate($object);
        if ($validationResult->hasErrors()) {
            $errorMessages = '';
            $allErrors = $validationResult->getFlattenedErrors();
            foreach ($allErrors as $path => $errors) {
                $errorMessages .= $path . ':' . PHP_EOL;
                foreach ($errors as $error) {
                    $errorMessages .= (string)$error . PHP_EOL;
                }
            }
            throw new ObjectValidationFailedException('An instance of "' . get_class($object) . '" failed to pass validation with ' . count($errors) . ' error(s): ' . PHP_EOL . $errorMessages, 1322585162);
        }
    }

    /**
     * Returns the type of $value, i.e. the class name or primitive type.
     *
     * @param mixed $value
     * @return string
     */
    protected function getType($value)
    {
        if (is_object($value)) {
            return get_class($value);
        } else {
            return gettype($value) === 'double' ? 'float' : gettype($value);
        }
    }

    /**
     * Returns the type name as used in the database table names.
     *
     * @param string $type
     * @return string
     */
    protected function getTypeName($type)
    {
        if (strstr($type, '\\')) {
            return 'object';
        } else {
            return strtolower($type);
        }
    }

    /**
     *
     * @param string $identifier The object's identifier
     * @param object $object The object to work on
     * @param array $properties The properties to collect (as per class schema)
     * @param boolean $dirty A dirty flag that is passed by reference and set to TRUE if a dirty property was found
     * @return array
     */
    protected function collectProperties($identifier, $object, array $properties, &$dirty)
    {
        $propertyData = [];
        foreach ($properties as $propertyName => $propertyMetaData) {
            $propertyValue = $this->checkPropertyValue($object, $propertyName, $propertyMetaData);

            // handle all objects now, because even clean ones need to be traversed
            // as dirty checking is not recursive
            if ($propertyValue instanceof PersistenceMagicInterface) {
                if ($this->persistenceSession->isDirty($object, $propertyName)) {
                    $dirty = true;
                    $this->flattenValue($identifier, $object, $propertyName, $propertyMetaData, $propertyData);
                } else {
                    $this->persistObject($propertyValue, $identifier);
                }
            } elseif ($this->persistenceSession->isDirty($object, $propertyName)) {
                $dirty = true;
                $this->flattenValue($identifier, $object, $propertyName, $propertyMetaData, $propertyData);
            }
        }

        return $propertyData;
    }

    /**
     * Convert a value to the internal object data format
     *
     * @param string $identifier The object's identifier
     * @param object $object The object with the property to flatten
     * @param string $propertyName The name of the property
     * @param array $propertyMetaData The property metadata
     * @param array $propertyData Reference to the property data array
     * @return void
     * @api
     */
    protected function flattenValue($identifier, $object, $propertyName, array $propertyMetaData, array &$propertyData)
    {
        $propertyValue = ObjectAccess::getProperty($object, $propertyName, true);

        if ($propertyValue instanceof PersistenceMagicInterface) {
            $propertyData[$propertyName] = [
                'type' => get_class($propertyValue),
                'multivalue' => false,
                'value' => $this->processObject($propertyValue, $identifier)
            ];
        } else {
            switch ($propertyMetaData['type']) {
                case 'DateTime':
                    $propertyData[$propertyName] = [
                        'multivalue' => false,
                        'value' => $this->processDateTime($propertyValue)
                    ];
                break;
                case Collection::class:
                case ArrayCollection::class:
                    $propertyValue = $propertyValue === null ? [] : $propertyValue->toArray();
                case 'array':
                    $propertyData[$propertyName] = [
                        'multivalue' => true,
                        'value' => $this->processArray($propertyValue, $identifier, $this->persistenceSession->getCleanStateOfProperty($object, $propertyName))
                    ];
                break;
                case 'SplObjectStorage':
                    $propertyData[$propertyName] = [
                        'multivalue' => true,
                        'value' => $this->processSplObjectStorage($propertyValue, $identifier, $this->persistenceSession->getCleanStateOfProperty($object, $propertyName))
                    ];
                break;
                default:
                    if ($propertyValue === null && !TypeHandling::isSimpleType($propertyMetaData['type'])) {
                        $this->removeDeletedReference($object, $propertyName, $propertyMetaData);
                    }
                    $propertyData[$propertyName] = [
                        'multivalue' => false,
                        'value' => $propertyValue
                    ];
                break;
            }
            $propertyData[$propertyName]['type'] = $propertyMetaData['type'];
        }
    }

    /**
     * Remove any unreferenced non aggregate root entity
     *
     * @param object $object
     * @param string $propertyName
     * @param array $propertyMetaData
     * @return void
     */
    protected function removeDeletedReference($object, $propertyName, $propertyMetaData)
    {
        $previousValue = $this->persistenceSession->getCleanStateOfProperty($object, $propertyName);
        if ($previousValue !== null && is_array($previousValue) && isset($previousValue['value']['identifier'])
            && $this->reflectionService->getClassSchema($propertyMetaData['type'])->getModelType() === ClassSchema::MODELTYPE_ENTITY
            && $this->reflectionService->getClassSchema($propertyMetaData['type'])->isAggregateRoot() === false) {
            $object = $this->persistenceSession->getObjectByIdentifier($previousValue['value']['identifier']);
            if (!$this->visitedDuringPersistence->contains($object)) {
                $this->removeEntity($object);
            }
        }
    }

    /**
     * @param object $object
     * @param string $parentIdentifier
     * @return array
     */
    protected function processObject($object, $parentIdentifier)
    {
        if (isset($this->classSchemata[get_class($object)]) && $this->classSchemata[get_class($object)]->isAggregateRoot() && !$this->persistenceManager->isNewObject($object)) {
            return [
                'identifier' => $this->persistenceSession->getIdentifierByObject($object)
            ];
        } else {
            return [
                'identifier' => $this->persistObject($object, $parentIdentifier)
            ];
        }
    }

    /**
     * Check the property value for allowed types and throw exceptions for
     * unsupported types.
     *
     * @param object $object The object with the property to check
     * @param string $propertyName The name of the property to check
     * @param array $propertyMetaData Property metadata
     * @return mixed The value of the property
     * @throws UnexpectedTypeException
     * @throws PersistenceException
     * @throws IllegalObjectTypeException
     * @api
     */
    protected function checkPropertyValue($object, $propertyName, array $propertyMetaData)
    {
        $propertyValue = ObjectAccess::getProperty($object, $propertyName, true);
        $propertyType = $propertyMetaData['type'];
        if ($propertyType === 'ArrayObject') {
            throw new PersistenceException('ArrayObject properties are not supported - missing feature?!?', 1283524355);
        }

        if (is_object($propertyValue)) {
            if ($propertyType === 'object') {
                if (!($propertyValue instanceof PersistenceMagicInterface)) {
                    throw new IllegalObjectTypeException('Property of generic type object holds "' . get_class($propertyValue) . '", which is not persistable (no entity or value object), in ' . get_class($object) . '::' . $propertyName, 1283531761);
                }
            } elseif (!($propertyValue instanceof $propertyType)) {
                throw new UnexpectedTypeException('Expected property of type ' . $propertyType . ', but got ' . get_class($propertyValue) . ' for ' . get_class($object) . '::' . $propertyName, 1244465558);
            }
        } elseif ($propertyValue !== null && $propertyType !== $this->getType($propertyValue)) {
            throw new UnexpectedTypeException('Expected property of type ' . $propertyType . ', but got ' . gettype($propertyValue) . ' for ' . get_class($object) . '::' . $propertyName, 1244465559);
        }

        return $propertyValue;
    }

    /**
     * Store an array as a set of records, with each array element becoming a
     * property named like the key and the value.
     *
     * Note: Objects contained in the array will have a matching entry created,
     * the objects must be persisted elsewhere!
     *
     * @param array $array The array to persist
     * @param string $parentIdentifier
     * @param array $previousArray the previously persisted state of the array
     * @return array An array with "flat" values representing the array
     * @throws PersistenceException
     */
    protected function processArray(array $array = null, $parentIdentifier, array $previousArray = null)
    {
        if ($previousArray !== null && is_array($previousArray['value'])) {
            $this->removeDeletedArrayEntries($array, $previousArray['value']);
        }

        if ($array === null) {
            return null;
        }

        $values = [];
        foreach ($array as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $values[] = [
                    'type' => 'DateTime',
                    'index' => $key,
                    'value' => $this->processDateTime($value)
                ];
            } elseif ($value instanceof \SplObjectStorage) {
                throw new PersistenceException('SplObjectStorage instances in arrays are not supported - missing feature?!?', 1261048721);
            } elseif ($value instanceof \ArrayObject) {
                throw new PersistenceException('ArrayObject instances in arrays are not supported - missing feature?!?', 1283524345);
            } elseif (is_object($value)) {
                $values[] = [
                    'type' => $this->getType($value),
                    'index' => $key,
                    'value' => $this->processObject($value, $parentIdentifier)
                ];
            } elseif (is_array($value)) {
                $values[] = [
                    'type' => 'array',
                    'index' => $key,
                    'value' => $this->processNestedArray($parentIdentifier, $value)
                ];
            } else {
                $values[] = [
                    'type' => $this->getType($value),
                    'index' => $key,
                    'value' => $value
                ];
            }
        }

        return $values;
    }

    /**
     * "Serializes" a nested array for storage.
     *
     * @param string $parentIdentifier
     * @param array $nestedArray
     * @param \Closure $handler
     * @return string
     */
    protected function processNestedArray($parentIdentifier, array $nestedArray, \Closure $handler = null)
    {
        $identifier = uniqid('a', true);
        $data = [
            'multivalue' => true,
            'value' => $this->processArray($nestedArray, $parentIdentifier)
        ];
        if ($handler instanceof \Closure) {
            $handler($parentIdentifier, $identifier, $data);
        }
        return $identifier;
    }

    /**
     * Remove objects removed from array compared to $previousArray.
     *
     * @param array $array
     * @param array $previousArray
     * @return void
     */
    protected function removeDeletedArrayEntries(array $array = null, array $previousArray)
    {
        foreach ($previousArray as $item) {
            if ($item['type'] === 'array') {
                $this->removeDeletedArrayEntries($array[$item['index']], $item['value']);
            } elseif ($this->getTypeName($item['type']) === 'object' && !($item['type'] === 'DateTime' || $item['type'] === 'SplObjectStorage')) {
                if (!$this->persistenceSession->hasIdentifier($item['value']['identifier'])) {
                    // ingore this identifier, assume it was blocked by security query rewriting
                    continue;
                }

                $object = $this->persistenceSession->getObjectByIdentifier($item['value']['identifier']);
                if ($array === null || !$this->arrayContainsObject($array, $object, $item['value']['identifier'])) {
                    if ($this->reflectionService->getClassSchema($item['type'])->getModelType() === ClassSchema::MODELTYPE_ENTITY
                            && $this->reflectionService->getClassSchema($item['type'])->isAggregateRoot() === false) {
                        $this->removeEntity($this->persistenceSession->getObjectByIdentifier($item['value']['identifier']));
                    } elseif ($this->reflectionService->getClassSchema($item['type'])->getModelType() === ClassSchema::MODELTYPE_VALUEOBJECT) {
                        $this->removeValueObject($this->persistenceSession->getObjectByIdentifier($item['value']['identifier']));
                    }
                }
            }
        }
    }

    /**
     * Checks whether the given object is contained in the array. This checks
     * for object identity in terms of the persistence layer, i.e. the UUID,
     * when comparing entities.
     *
     * @param array $array
     * @param object $object
     * @param string $identifier
     * @return boolean
     */
    protected function arrayContainsObject(array $array, $object, $identifier)
    {
        if (in_array($object, $array, true) === true) {
            return true;
        }

        foreach ($array as $value) {
            if ($value instanceof $object && $this->persistenceSession->getIdentifierByObject($value) === $identifier) {
                return true;
            }
        }

        return false;
    }

    /**
     * Store an SplObjectStorage as a set of records.
     *
     * Note: Objects contained in the SplObjectStorage will have a matching
     * entry created, the objects must be persisted elsewhere!
     *
     * @param \SplObjectStorage $splObjectStorage The SplObjectStorage to persist
     * @param string $parentIdentifier
     * @param array $previousObjectStorage the previously persisted state of the SplObjectStorage
     * @return array An array with "flat" values representing the SplObjectStorage
     * @throws PersistenceException
     */
    protected function processSplObjectStorage(\SplObjectStorage $splObjectStorage = null, $parentIdentifier, array $previousObjectStorage = null)
    {
        if ($previousObjectStorage !== null && is_array($previousObjectStorage['value'])) {
            $this->removeDeletedSplObjectStorageEntries($splObjectStorage, $previousObjectStorage['value']);
        }

        if ($splObjectStorage === null) {
            return null;
        }

        $values = [];
        foreach ($splObjectStorage as $object) {
            if ($object instanceof \DateTimeInterface) {
                $values[] = [
                    'type' => 'DateTime',
                    'index' => null,
                    'value' => $this->processDateTime($object)
                ];
            } elseif ($object instanceof \SplObjectStorage) {
                throw new PersistenceException('SplObjectStorage instances in SplObjectStorage are not supported - missing feature?!?', 1283524360);
            } elseif ($object instanceof \ArrayObject) {
                throw new PersistenceException('ArrayObject instances in SplObjectStorage are not supported - missing feature?!?', 1283524350);
            } else {
                $values[] = [
                    'type' => $this->getType($object),
                    'index' => null,
                    'value' => $this->processObject($object, $parentIdentifier)
                ];
            }
        }

        return $values;
    }

    /**
     * Remove objects removed from SplObjectStorage compared to
     * $previousSplObjectStorage.
     *
     * @param \SplObjectStorage $splObjectStorage
     * @param array $previousObjectStorage
     * @return void
     */
    protected function removeDeletedSplObjectStorageEntries(\SplObjectStorage $splObjectStorage = null, array $previousObjectStorage)
    {
        // remove objects detached since reconstitution
        foreach ($previousObjectStorage as $item) {
            if ($splObjectStorage instanceof LazySplObjectStorage && !$this->persistenceSession->hasIdentifier($item['value']['identifier'])) {
                // ingore this identifier, assume it was blocked by security query rewriting upon activation
                continue;
            }

            $object = $this->persistenceSession->getObjectByIdentifier($item['value']['identifier']);
            if ($splObjectStorage === null || !$splObjectStorage->contains($object)) {
                if ($this->reflectionService->getClassSchema($object)->getModelType() === ClassSchema::MODELTYPE_ENTITY
                        && $this->reflectionService->getClassSchema($object)->isAggregateRoot() === false) {
                    $this->removeEntity($object);
                } elseif ($this->reflectionService->getClassSchema($object)->getModelType() === ClassSchema::MODELTYPE_VALUEOBJECT) {
                    $this->removeValueObject($object);
                }
            }
        }
    }

    /**
     * Creates a unix timestamp from the given DateTime object. If NULL is given
     * NULL will be returned.
     *
     * @param \DateTime $dateTime
     * @return integer
     */
    protected function processDateTime(\DateTimeInterface $dateTime = null)
    {
        if ($dateTime instanceof \DateTimeInterface) {
            return $dateTime->getTimestamp();
        } else {
            return null;
        }
    }
}
