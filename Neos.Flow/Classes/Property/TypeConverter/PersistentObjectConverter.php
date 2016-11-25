<?php
namespace Neos\Flow\Property\TypeConverter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Annotations\ValueObject;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\Exception\DuplicateObjectException;
use Neos\Flow\Property\Exception\InvalidPropertyMappingConfigurationException;
use Neos\Flow\Property\Exception\InvalidSourceException;
use Neos\Flow\Property\Exception\InvalidTargetException;
use Neos\Flow\Property\Exception\TargetNotFoundException;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\Error\TargetNotFoundError;
use Neos\Utility\ObjectAccess;
use Neos\Utility\TypeHandling;

/**
 * This converter transforms arrays or strings to persistent objects. It does the following:
 *
 * - If the input is string, it is assumed to be a UUID. Then, the object is fetched from persistence.
 * - If the input is array, we check if it has an identity property.
 *
 * - If the input has NO identity property, but additional properties, we create a new object and return it.
 *   However, we only do this if the configuration option "CONFIGURATION_CREATION_ALLOWED" is TRUE.
 * - If the input has an identity property AND the configuration option "CONFIGURATION_IDENTITY_CREATION_ALLOWED" is set,
 *   we fetch the object from persistent or create a new object if none was found and then set the sub-properties.
 * - If the input has an identity property and NO additional properties, we fetch the object from persistence.
 * - If the input has an identity property AND additional properties, we fetch the object from persistence,
 *   and set the sub-properties. We only do this if the configuration option "CONFIGURATION_MODIFICATION_ALLOWED" is TRUE.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class PersistentObjectConverter extends ObjectConverter
{
    /**
     * @var string
     */
    const PATTERN_MATCH_UUID = '/([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}/';

    /**
     * @var integer
     */
    const CONFIGURATION_MODIFICATION_ALLOWED = 1;

    /**
     * @var integer
     */
    const CONFIGURATION_CREATION_ALLOWED = 2;

    /**
     * @var integer
     */
    const CONFIGURATION_IDENTITY_CREATION_ALLOWED = 5;

    /**
     * @var array
     */
    protected $sourceTypes = ['string', 'array'];

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * We can only convert if the $targetType is either tagged with entity or value object.
     *
     * @param mixed $source
     * @param string $targetType
     * @return boolean
     */
    public function canConvertFrom($source, $targetType)
    {
        return (
            $this->reflectionService->isClassAnnotatedWith($targetType, Flow\Entity::class) ||
            $this->reflectionService->isClassAnnotatedWith($targetType, ValueObject::class) ||
            $this->reflectionService->isClassAnnotatedWith($targetType, \Doctrine\ORM\Mapping\Entity::class)
        );
    }

    /**
     * All properties in the source array except __identity are sub-properties.
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        if (is_string($source)) {
            return [];
        }
        if (isset($source['__identity'])) {
            unset($source['__identity']);
        }
        return parent::getSourceChildPropertiesToBeConverted($source);
    }

    /**
     * The type of a property is determined by the reflection service.
     *
     * @param string $targetType
     * @param string $propertyName
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws InvalidTargetException
     */
    public function getTypeOfChildProperty($targetType, $propertyName, PropertyMappingConfigurationInterface $configuration)
    {
        $configuredTargetType = $configuration->getConfigurationFor($propertyName)->getConfigurationValue(\Neos\Flow\Property\TypeConverter\PersistentObjectConverter::class, self::CONFIGURATION_TARGET_TYPE);
        if ($configuredTargetType !== null) {
            return $configuredTargetType;
        }

        $schema = $this->reflectionService->getClassSchema($targetType);
        $setterMethodName = ObjectAccess::buildSetterMethodName($propertyName);
        $constructorParameters = $this->reflectionService->getMethodParameters($targetType, '__construct');

        if (isset($constructorParameters[$propertyName]) && isset($constructorParameters[$propertyName]['type'])) {
            return $constructorParameters[$propertyName]['type'];
        } elseif ($schema->hasProperty($propertyName)) {
            $propertyInformation = $schema->getProperty($propertyName);
            return $propertyInformation['type'] . ($propertyInformation['elementType'] !== null ? '<' . $propertyInformation['elementType'] . '>' : '');
        } elseif ($this->reflectionService->hasMethod($targetType, $setterMethodName)) {
            $methodParameters = $this->reflectionService->getMethodParameters($targetType, $setterMethodName);
            $methodParameter = current($methodParameters);
            if (!isset($methodParameter['type'])) {
                throw new InvalidTargetException('Setter for property "' . $propertyName . '" had no type hint or documentation in target object of type "' . $targetType . '".', 1303379158);
            } else {
                return $methodParameter['type'];
            }
        } else {
            throw new InvalidTargetException('Property "' . $propertyName . '" was not found in target object of type "' . $targetType . '".', 1297978366);
        }
    }

    /**
     * Convert an object from $source to an entity or a value object.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return object|TargetNotFoundError the converted entity/value object or an instance of TargetNotFoundError if the object could not be resolved
     * @throws \InvalidArgumentException|InvalidTargetException
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        if (is_array($source)) {
            if ($this->reflectionService->isClassAnnotatedWith($targetType, ValueObject::class)) {
                if (isset($source['__identity']) && (count($source) > 1)) {
                    // @TODO fix that in the URI building and transfer VOs as values instead as with their identities
                    // Unset identity for value objects to use constructor mapping, since the identity is determined from
                    // property values after construction
                    unset($source['__identity']);
                }
            }
            $object = $this->handleArrayData($source, $targetType, $convertedChildProperties, $configuration);
            if ($object instanceof TargetNotFoundError) {
                return $object;
            }
        } elseif (is_string($source)) {
            if ($source === '') {
                return null;
            }
            $object = $this->fetchObjectFromPersistence($source, $targetType);
            if ($object === null) {
                return new TargetNotFoundError(sprintf('Object of type "%s" with identity "%s" not found.', $targetType, $source), 1412283033);
            }
        } else {
            throw new \InvalidArgumentException('Only strings and arrays are accepted.', 1305630314);
        }

        $objectConstructorArguments = $this->getConstructorArgumentsForClass(TypeHandling::getTypeForValue($object));

        foreach ($convertedChildProperties as $propertyName => $propertyValue) {
            // We need to check for "immutable" constructor arguments that have no setter and remove them.
            if (isset($objectConstructorArguments[$propertyName]) && !ObjectAccess::isPropertySettable($object, $propertyName)) {
                $currentPropertyValue = ObjectAccess::getProperty($object, $propertyName);
                if ($currentPropertyValue === $propertyValue) {
                    continue;
                } else {
                    $exceptionMessage = sprintf(
                        'Property "%s" having a value of type "%s" could not be set in target object of type "%s". The property has no setter and is not equal to the value in the object, in that case it would have been skipped.',
                        $propertyName,
                        (is_object($propertyValue) ? TypeHandling::getTypeForValue($propertyValue) : gettype($propertyValue)),
                        $targetType
                    );
                    throw new InvalidTargetException($exceptionMessage, 1421498771);
                }
            }
            $result = ObjectAccess::setProperty($object, $propertyName, $propertyValue);
            if ($result === false) {
                $exceptionMessage = sprintf(
                    'Property "%s" having a value of type "%s" could not be set in target object of type "%s". Make sure that the property is accessible properly, for example via an appropriate setter method.',
                    $propertyName,
                    (is_object($propertyValue) ? TypeHandling::getTypeForValue($propertyValue) : gettype($propertyValue)),
                    $targetType
                );
                throw new InvalidTargetException($exceptionMessage, 1297935345);
            }
        }

        return $object;
    }

    /**
     * Handle the case if $source is an array.
     *
     * @param array $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return object|TargetNotFoundError
     * @throws InvalidPropertyMappingConfigurationException
     */
    protected function handleArrayData(array $source, $targetType, array &$convertedChildProperties, PropertyMappingConfigurationInterface $configuration = null)
    {
        if (!isset($source['__identity'])) {
            if ($this->reflectionService->isClassAnnotatedWith($targetType, ValueObject::class) === true) {
                // Allow creation for ValueObjects by default, but prevent if explicitly disallowed
                if ($configuration !== null && $configuration->getConfigurationValue(PersistentObjectConverter::class, self::CONFIGURATION_CREATION_ALLOWED) === false) {
                    throw new InvalidPropertyMappingConfigurationException('Creation of value objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_CREATION_ALLOWED" to TRUE');
                }
            } elseif ($configuration === null || $configuration->getConfigurationValue(PersistentObjectConverter::class, self::CONFIGURATION_CREATION_ALLOWED) !== true) {
                throw new InvalidPropertyMappingConfigurationException('Creation of objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_CREATION_ALLOWED" to TRUE');
            }
            $object = $this->buildObject($convertedChildProperties, $targetType);
        } elseif ($configuration !== null && $configuration->getConfigurationValue(PersistentObjectConverter::class, self::CONFIGURATION_IDENTITY_CREATION_ALLOWED) === true) {
            $object = $this->fetchObjectFromPersistence($source['__identity'], $targetType);
            if ($object === null) {
                $object = $this->buildObject($convertedChildProperties, $targetType);
                $this->setIdentity($object, $source['__identity']);
            }
        } else {
            $object = $this->fetchObjectFromPersistence($source['__identity'], $targetType);

            if ($object === null) {
                return new TargetNotFoundError(sprintf('Object of type %s with identity "%s" not found.', $targetType, print_r($source['__identity'], true)), 1412283038);
            }

            if (count($convertedChildProperties) > 0 && ($configuration === null || $configuration->getConfigurationValue(PersistentObjectConverter::class, self::CONFIGURATION_MODIFICATION_ALLOWED) !== true)) {
                throw new InvalidPropertyMappingConfigurationException('Modification of persistent objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_MODIFICATION_ALLOWED" to TRUE.', 1297932028);
            }
        }

        return $object;
    }

    /**
     * Set the given $identity on the created $object.
     *
     * @param object $object
     * @param string|array $identity
     * @return void
     * @todo set identity properly if it is composite or custom property
     */
    protected function setIdentity($object, $identity)
    {
        ObjectAccess::setProperty($object, 'Persistence_Object_Identifier', $identity, true);
    }

    /**
     * Fetch an object from persistence layer.
     *
     * @param mixed $identity
     * @param string $targetType
     * @return object
     * @throws TargetNotFoundException
     * @throws InvalidSourceException
     */
    protected function fetchObjectFromPersistence($identity, $targetType)
    {
        if (is_string($identity)) {
            $object = $this->persistenceManager->getObjectByIdentifier($identity, $targetType);
        } elseif (is_array($identity)) {
            $object = $this->findObjectByIdentityProperties($identity, $targetType);
        } else {
            throw new InvalidSourceException(sprintf('The identity property is neither a string nor an array but of type "%s".', gettype($identity)), 1297931020);
        }

        return $object;
    }

    /**
     * Finds an object from the repository by searching for its identity properties.
     *
     * @param array $identityProperties Property names and values to search for
     * @param string $type The object type to look for
     * @return object Either the object matching the identity or NULL if no object was found
     * @throws DuplicateObjectException if more than one object was found
     */
    protected function findObjectByIdentityProperties(array $identityProperties, $type)
    {
        $query = $this->persistenceManager->createQueryForType($type);
        $classSchema = $this->reflectionService->getClassSchema($type);

        $equals = [];
        foreach ($classSchema->getIdentityProperties() as $propertyName => $propertyType) {
            if (isset($identityProperties[$propertyName])) {
                if ($propertyType === 'string') {
                    $equals[] = $query->equals($propertyName, $identityProperties[$propertyName], false);
                } else {
                    $equals[] = $query->equals($propertyName, $identityProperties[$propertyName]);
                }
            }
        }

        if (count($equals) === 1) {
            $constraint = current($equals);
        } else {
            $constraint = $query->logicalAnd(current($equals), next($equals));
            while (($equal = next($equals)) !== false) {
                $constraint = $query->logicalAnd($constraint, $equal);
            }
        }

        $objects = $query->matching($constraint)->execute();
        $numberOfResults = $objects->count();
        if ($numberOfResults === 1) {
            return $objects->getFirst();
        } elseif ($numberOfResults === 0) {
            return null;
        } else {
            throw new DuplicateObjectException('More than one object was returned for the given identity, this is a constraint violation.', 1259612399);
        }
    }
}
