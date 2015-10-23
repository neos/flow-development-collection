<?php
namespace TYPO3\Flow\Persistence\Doctrine\Mapping\Driver;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\IndexedReader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver as DoctrineMappingDriverInterface;
use Doctrine\Common\Persistence\ObjectManager as DoctrineObjectManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Builder\EntityListenerBuilder;
use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\NamedQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\Builder\ClassNameIndex;
use TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface;
use TYPO3\Flow\Object\Proxy\Compiler;
use TYPO3\Flow\Persistence\Doctrine\Mapping\Exception\ClassSchemaNotFoundException;
use TYPO3\Flow\Reflection\ClassSchema;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * This driver reads the mapping metadata from docblock annotations.
 *
 * It gives precedence to Doctrine annotations but fills gaps from other info
 * if possible:
 *
 * - Entity.repositoryClass is set to the repository found in the class schema
 * - Table.name is set to a sane value
 * - Column.type is set to property type
 * - *.targetEntity is set to property type
 *
 * If a property is not marked as an association the mapping type is set to
 * "object" for objects.
 *
 * @Flow\Scope("singleton")
 */
class BaseAnnotationDriver implements DoctrineMappingDriverInterface, PointcutFilterInterface
{
    /**
     * @var integer
     */
    const MAPPING_REGULAR = 0;

    const MAPPING_MM_REGULAR = 1;

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var AnnotationReader
     */
    protected $reader;

    /**
     * @var DoctrineObjectManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $classNames;

    /**
     * @var integer
     */
    protected $tableNameLengthLimit = null;

    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
     * docblock annotations.
     */
    public function __construct()
    {
        $this->reader = new IndexedReader(new AnnotationReader());
    }

    /**
     * @param ReflectionService $reflectionService
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param DoctrineObjectManager $entityManager
     * @return void
     */
    public function setEntityManager(DoctrineObjectManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Fetch a class schema for the given class, if possible.
     *
     * @param string $className
     * @return ClassSchema
     * @throws ClassSchemaNotFoundException
     */
    protected function getClassSchema($className)
    {
        $classSchema = $this->reflectionService->getClassSchema($className);
        if (!$classSchema) {
            throw new ClassSchemaNotFoundException('No class schema found for "' . $className . '".', 1295973082);
        }

        return $classSchema;
    }

    /**
     * Check for $className being an aggregate root.
     *
     * @param string $className
     * @param string $propertySourceHint
     * @return boolean
     * @throws ClassSchemaNotFoundException
     */
    protected function isAggregateRoot($className, $propertySourceHint)
    {
        $className = $this->getUnproxiedClassName($className);
        try {
            $classSchema = $this->getClassSchema($className);

            return $classSchema->isAggregateRoot();
        } catch (ClassSchemaNotFoundException $exception) {
            throw new ClassSchemaNotFoundException('No class schema found for "' . $className . '". The class should probably marked as entity or value object! This happened while examining "' . $propertySourceHint . '"', 1340185197);
        }
    }

    /**
     * Check for $className being a value object.
     *
     * @param string $className
     * @param string $propertySourceHint
     * @return boolean
     * @throws ClassSchemaNotFoundException
     */
    protected function isValueObject($className, $propertySourceHint)
    {
        $className = $this->getUnproxiedClassName($className);
        try {
            $classSchema = $this->getClassSchema($className);

            return $classSchema->getModelType() === ClassSchema::MODELTYPE_VALUEOBJECT;
        } catch (ClassSchemaNotFoundException $exception) {
            throw new ClassSchemaNotFoundException('No class schema found for "' . $className . '". The class should probably marked as entity or value object! This happened while examining "' . $propertySourceHint . '"', 1340185197);
        }
    }

    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string $className
     * @param ClassMetadata $metadata
     * @return void
     * @throws MappingException
     * @throws \UnexpectedValueException
     * @todo adjust when Doctrine 2.5 is used, see http://www.doctrine-project.org/jira/browse/DDC-93
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        /**
         * This is the actual type we have at this point, but we cannot change the
         * signature due to inheritance.
         *
         * @var OrmClassMetadata $metadata
         */

        $class = $metadata->getReflectionClass();
        $classSchema = $this->getClassSchema($class->getName());
        $classAnnotations = $this->reader->getClassAnnotations($class);

        $this->configureObjectType($metadata, $classAnnotations);

        $primaryTable = $this->configurePrimaryTable($classAnnotations);
        if (!isset($primaryTable['name'])) {
            $primaryTable['name'] = $this->inferTableNameFromClassName($className);
        }

        // build unique index for table
        $idProperties = array_keys($classSchema->getIdentityProperties());
        if (!isset($primaryTable['uniqueConstraints']) && array_diff($idProperties, $metadata->getIdentifierFieldNames()) !== []) {
            $uniqueIndexName = $this->truncateIdentifier('flow_identity_' . $primaryTable['name']);
            foreach ($idProperties as $idProperty) {
                $primaryTable['uniqueConstraints'][$uniqueIndexName]['columns'][] = isset($metadata->columnNames[$idProperty]) ? $metadata->columnNames[$idProperty] : strtolower($idProperty);
            }
        }

        $sqlResultSetMappings = $this->evaluateSqlResultSetMappings($classAnnotations);
        array_walk($sqlResultSetMappings, [$metadata, 'addSqlResultSetMapping']);

        $namedNativeQueries = $this->evaluateNamedNativeQueriesAnnotation($classAnnotations);
        array_walk($namedNativeQueries, [$metadata, 'addNamedNativeQuery']);

        $namedQueries = $this->evaluateNamedQueriesAnnotation($classAnnotations);
        array_walk($namedQueries, [$metadata, 'addNamedQuery']);

        // Evaluate InheritanceType annotation
        if (isset($classAnnotations[\Doctrine\ORM\Mapping\InheritanceType::class])) {
            $this->applyInheritanceTypeToMetadata($metadata, $classAnnotations);
        }

        // Evaluate DoctrineChangeTrackingPolicy annotation
        if (isset($classAnnotations[\Doctrine\ORM\Mapping\ChangeTrackingPolicy::class])) {
            $changeTrackingAnnotation = $classAnnotations[\Doctrine\ORM\Mapping\ChangeTrackingPolicy::class];
            $metadata->setChangeTrackingPolicy(constant('Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_' . strtoupper($changeTrackingAnnotation->value)));
        } else {
            $metadata->setChangeTrackingPolicy(OrmClassMetadata::CHANGETRACKING_DEFERRED_EXPLICIT);
        }

        // Evaluate annotations on properties/fields
        try {
            $this->evaluatePropertyAnnotations($metadata);
        } catch (MappingException $exception) {
            throw new MappingException(sprintf('Failure while evaluating property annotations for class "%s": %s', $metadata->getName(), $exception->getMessage()), 1382003497, $exception);
        }

        $metadata->setPrimaryTable($primaryTable);

        // Evaluate AssociationOverrides annotation
        $this->evaluateOverridesAnnotations($classAnnotations, $metadata);

        // Evaluate EntityListeners annotation
        $this->evaluateEntityListenersAnnotation($class, $metadata, $classAnnotations);

        // Evaluate @HasLifecycleCallbacks annotation
        $this->evaluateLifeCycleAnnotations($class, $metadata);
    }

    /**
     * @param ClassMetadata $metadata
     * @param array $classAnnotations
     * @throws MappingException
     */
    protected function configureObjectType(ClassMetadata $metadata, array $classAnnotations)
    {
        // Evaluate Entity annotation
        if (isset($classAnnotations[\Doctrine\ORM\Mapping\MappedSuperclass::class])) {
            $mappedSuperclassAnnotation = $classAnnotations[\Doctrine\ORM\Mapping\MappedSuperclass::class];
            if ($mappedSuperclassAnnotation->repositoryClass !== null) {
                $metadata->setCustomRepositoryClass($mappedSuperclassAnnotation->repositoryClass);
            }
            $metadata->isMappedSuperclass = true;

            return;
        }

        $classSchema = $this->getClassSchema($metadata->getName());

        if ($classSchema->getModelType() === ClassSchema::MODELTYPE_VALUEOBJECT) {
            // also ok... but we make it read-only
            $metadata->markReadOnly();

            return;
        }

        if (isset($classAnnotations[\Doctrine\ORM\Mapping\Entity::class])) {
            $entityAnnotation = $classAnnotations[\Doctrine\ORM\Mapping\Entity::class];
        }

        if (isset($classAnnotations[\TYPO3\Flow\Annotations\Entity::class])) {
            $entityAnnotation = $classAnnotations[\TYPO3\Flow\Annotations\Entity::class];
        }

        if (!isset($entityAnnotation)) {
            throw MappingException::classIsNotAValidEntityOrMappedSuperClass($metadata->getName());
        }

        if ($entityAnnotation->repositoryClass !== null) {
            $metadata->setCustomRepositoryClass($entityAnnotation->repositoryClass);
        } elseif ($classSchema->getRepositoryClassName() !== null) {
            if ($this->reflectionService->isClassImplementationOf($classSchema->getRepositoryClassName(),
                \Doctrine\ORM\EntityRepository::class)
            ) {
                $metadata->setCustomRepositoryClass($classSchema->getRepositoryClassName());
            }
        }

        if ($entityAnnotation->readOnly) {
            $metadata->markReadOnly();
        }
    }

    /**
     * @param array $classAnnotations
     * @return array
     */
    protected function configurePrimaryTable(array $classAnnotations)
    {
        if (!isset($classAnnotations[\Doctrine\ORM\Mapping\Table::class])) {
            return [];
        }

        $tableAnnotation = $classAnnotations[\Doctrine\ORM\Mapping\Table::class];
        $primaryTable = [
            'name' => $tableAnnotation->name,
            'schema' => $tableAnnotation->schema
        ];

        if ($tableAnnotation->indexes !== null) {
            foreach ($tableAnnotation->indexes as $indexAnnotation) {
                $index = ['columns' => $indexAnnotation->columns];
                if (!empty($indexAnnotation->name)) {
                    $primaryTable['indexes'][$indexAnnotation->name] = $index;
                } else {
                    $primaryTable['indexes'][] = $index;
                }
            }
        }

        if ($tableAnnotation->uniqueConstraints !== null) {
            foreach ($tableAnnotation->uniqueConstraints as $uniqueConstraint) {
                $uniqueConstraint = ['columns' => $uniqueConstraint->columns];
                if (!empty($uniqueConstraint->name)) {
                    $primaryTable['uniqueConstraints'][$uniqueConstraint->name] = $uniqueConstraint;
                } else {
                    $primaryTable['uniqueConstraints'][] = $uniqueConstraint;
                }
            }
        }

        if ($tableAnnotation->options !== null) {
            $primaryTable['options'] = $tableAnnotation->options;
        }

        return $primaryTable;
    }

    /**
     * @param array $classAnnotations
     * @return array
     */
    protected function evaluateSqlResultSetMappings(array $classAnnotations)
    {
        if (!isset($classAnnotations[\Doctrine\ORM\Mapping\SqlResultSetMappings::class])) {
            return [];
        }

        $sqlResultSetMappingsAnnotation = $classAnnotations[\Doctrine\ORM\Mapping\SqlResultSetMappings::class];

        // Maps \Doctrine\ORM\Mapping\FieldResult objects to arrays
        $fieldMappingFunction = function ($fieldResultAnnotation) {
            return [
                'name' => $fieldResultAnnotation->name,
                'column' => $fieldResultAnnotation->column
            ];
        };

        // Maps \Doctrine\ORM\Mapping\EntityResult objects to arrays including the mapped field annotations
        $entityMappingFunction = function ($entityResultAnnotation) use ($fieldMappingFunction) {
            $fields = array_map($fieldMappingFunction, $entityResultAnnotation->fields);

            return [
                'fields' => $fields,
                'entityClass' => $entityResultAnnotation->entityClass,
                'discriminatorColumn' => $entityResultAnnotation->discriminatorColumn,
            ];
        };

        // Maps \Doctrine\ORM\Mapping\ColumnResult objects to arrays
        $columnMappingFunction = function ($columnResultAnnotation) {
            return ['name' => $columnResultAnnotation->name];
        };

        $mappings = [];
        foreach ($sqlResultSetMappingsAnnotation->value as $resultSetMapping) {
            $entities = array_map($entityMappingFunction, $resultSetMapping->entities);
            $columns = array_map($columnMappingFunction, $resultSetMapping->columns);

            $mappings[] = [
                'name' => $resultSetMapping->name,
                'entities' => $entities,
                'columns' => $columns
            ];
        }

        return $mappings;
    }

    /**
     * @param array $classAnnotations
     * @return array
     */
    protected function evaluateNamedNativeQueriesAnnotation(array $classAnnotations)
    {
        if (!isset($classAnnotations[\Doctrine\ORM\Mapping\NamedNativeQueries::class])) {
            return [];
        }

        $namedNativeQueriesAnnotation = $classAnnotations[\Doctrine\ORM\Mapping\NamedNativeQueries::class];
        $namedNativeQueries = array_map(function ($namedNativeQuery) {
            return [
                'name' => $namedNativeQuery->name,
                'query' => $namedNativeQuery->query,
                'resultClass' => $namedNativeQuery->resultClass,
                'resultSetMapping' => $namedNativeQuery->resultSetMapping
            ];
        }, $namedNativeQueriesAnnotation->value);

        return $namedNativeQueries;
    }

    /**
     * @param array $classAnnotations
     * @return array
     */
    protected function evaluateNamedQueriesAnnotation(array $classAnnotations)
    {
        // Evaluate NamedQueries annotation
        if (!isset($classAnnotations[\Doctrine\ORM\Mapping\NamedQueries::class])) {
            return [];
        }

        $namedQueriesAnnotation = $classAnnotations[\Doctrine\ORM\Mapping\NamedQueries::class];

        if (!is_array($namedQueriesAnnotation->value)) {
            throw new \UnexpectedValueException('@NamedQueries should contain an array of @NamedQuery annotations.');
        }

        $namedQueries = array_map(function ($namedQuery) {
            if (!($namedQuery instanceof NamedQuery)) {
                throw new \UnexpectedValueException('@NamedQueries should contain an array of @NamedQuery annotations.');
            }

            return [
                'name' => $namedQuery->name,
                'query' => $namedQuery->query
            ];
        }, $namedQueriesAnnotation->value);

        return $namedQueries;
    }

    /**
     * @param ClassMetadata $metadata
     * @param $classAnnotations
     */
    protected function applyInheritanceTypeToMetadata(ClassMetadata $metadata, array $classAnnotations)
    {
        $inheritanceTypeAnnotation = $classAnnotations[\Doctrine\ORM\Mapping\InheritanceType::class];
        $inheritanceType = constant('Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_' . strtoupper($inheritanceTypeAnnotation->value));

        if ($inheritanceType === OrmClassMetadata::INHERITANCE_TYPE_NONE) {
            $metadata->setInheritanceType($inheritanceType);

            return;
        }

        $discriminatorColumn = [
            'name' => 'dtype',
            'type' => 'string',
            'length' => 255
        ];

        // Evaluate DiscriminatorColumn annotation
        if (isset($classAnnotations[\Doctrine\ORM\Mapping\DiscriminatorColumn::class])) {
            $discriminatorColumnAnnotation = $classAnnotations[\Doctrine\ORM\Mapping\DiscriminatorColumn::class];
            $discriminatorColumn = [
                'name' => $discriminatorColumnAnnotation->name,
                'type' => $discriminatorColumnAnnotation->type,
                'length' => $discriminatorColumnAnnotation->length,
                'columnDefinition' => $discriminatorColumnAnnotation->columnDefinition
            ];
        }

        $discriminatorMap = $this->evaluateDiscriminatorMap($metadata, $classAnnotations);

        if (empty($discriminatorMap)) {
            $inheritanceType = OrmClassMetadata::INHERITANCE_TYPE_NONE;
        }

        if (!empty($discriminatorMap)) {
            $metadata->setDiscriminatorColumn($discriminatorColumn);
            $metadata->setDiscriminatorMap($discriminatorMap);
        }

        $metadata->setInheritanceType($inheritanceType);
    }

    /**
     * @param ClassMetadata $metadata
     * @param $classAnnotations
     * @return array
     */
    protected function evaluateDiscriminatorMap(ClassMetadata $metadata, array $classAnnotations)
    {
        if (isset($classAnnotations[\Doctrine\ORM\Mapping\DiscriminatorMap::class])) {
            $discriminatorMapAnnotation = $classAnnotations[\Doctrine\ORM\Mapping\DiscriminatorMap::class];
            $discriminatorMap = $discriminatorMapAnnotation->value;

            return $discriminatorMap;
        }

        $discriminatorMap = [];
        $className = $metadata->getName();
        if (!$this->reflectionService->isClassAbstract($className)) {
            $mappedClassName = strtolower(str_replace('Domain_Model_', '', str_replace('\\', '_', $className)));
            $discriminatorMap[$mappedClassName] = $className;
        }

        $subclassNames = $this->reflectionService->getAllSubClassNamesForClass($className);
        foreach ($subclassNames as $subclassName) {
            $mappedSubclassName = strtolower(str_replace('Domain_Model_', '', str_replace('\\', '_', $subclassName)));
            $discriminatorMap[$mappedSubclassName] = $subclassName;
        }

        return $discriminatorMap;
    }

    /**
     * Given a class name a table name is returned. That name should be reasonably unique.
     *
     * @param string $className
     * @param integer $lengthLimit
     * @return string
     */
    protected function inferTableNameFromClassName($className, $lengthLimit = null)
    {
        return $this->truncateIdentifier(strtolower(str_replace('\\', '_', $className)), $lengthLimit, $className);
    }

    /**
     * Truncate an identifier if needed and append a hash to ensure uniqueness.
     *
     * @param string $identifier
     * @param integer $lengthLimit
     * @param string $hashSource
     * @return string
     */
    protected function truncateIdentifier($identifier, $lengthLimit = null, $hashSource = null)
    {
        if ($lengthLimit === null) {
            $lengthLimit = $this->getMaxIdentifierLength();
        }
        if (strlen($identifier) > $lengthLimit) {
            $identifier = substr($identifier, 0, $lengthLimit - 6) . '_' . substr(sha1($hashSource !== null ? $hashSource : $identifier), 0, 5);
        }

        return $identifier;
    }

    /**
     * Given a class and property name a table name is returned. That name should be reasonably unique.
     *
     * @param string $className Model class name the table corresponds to
     * @param string $propertyName Name of the property to be joined
     * @return string Truncated database table name
     */
    protected function inferJoinTableNameFromClassAndPropertyName($className, $propertyName)
    {
        $prefix = $this->inferTableNameFromClassName($className);
        $suffix = '_' . strtolower($propertyName . '_join');

        // In order to keep backwards compatibility with earlier versions, truncate the table name in two steps:
        if (strlen($prefix . $suffix) > $this->getMaxIdentifierLength()) {
            $prefix = $this->inferTableNameFromClassName($className, $this->getMaxIdentifierLength() - strlen($suffix));
        }
        // Truncate a second time if the property name was too long as well:
        if (strlen($prefix . $suffix) > $this->getMaxIdentifierLength()) {
            return $this->truncateIdentifier($prefix . $suffix, $this->getMaxIdentifierLength());
        } else {
            return $prefix . $suffix;
        }
    }

    /**
     * Build a name for a column in a jointable.
     *
     * @param string $className
     * @return string
     */
    protected function buildJoinTableColumnName($className)
    {
        if (preg_match('/^(?P<PackageNamespace>\w+(?:\\\\\w+)*)\\\\Domain\\\\Model\\\\(?P<ModelNamePrefix>(\w+\\\\)?)(?P<ModelName>\w+)$/', $className, $matches)) {
            $packageNamespaceParts = explode('\\', $matches['PackageNamespace']);
            $tableName = strtolower(strtr($packageNamespaceParts[count($packageNamespaceParts) - 1], '\\', '_') . ($matches['ModelNamePrefix'] !== '' ? '_' . strtr(rtrim($matches['ModelNamePrefix'], '\\'), '\\', '_') : '') . '_' . $matches['ModelName']);
        } else {
            $classNameParts = explode('\\', $className);
            $tableName = strtolower($classNameParts[1] . '_' . implode('_', array_slice($classNameParts, -2, 2)));
        }

        return $this->truncateIdentifier($tableName);
    }

    /**
     * Check if the referenced column name is set (and valid) and if not make sure
     * it is initialized properly.
     *
     * @param array $joinColumns
     * @param array $mapping
     * @param \ReflectionProperty $property
     * @param integer $direction regular or inverse mapping (use is to be coded)
     * @return array
     */
    protected function buildJoinColumnsIfNeeded(array $joinColumns, array $mapping, \ReflectionProperty $property, $direction = self::MAPPING_REGULAR)
    {
        if ($joinColumns === []) {
            $joinColumns[] = [
                'name' => strtolower($property->getName()),
                'referencedColumnName' => null,
            ];
        }
        foreach ($joinColumns as &$joinColumn) {
            if ($joinColumn['referencedColumnName'] === null || $joinColumn['referencedColumnName'] === 'id') {
                if ($direction === self::MAPPING_REGULAR) {
                    $idProperties = $this->reflectionService->getPropertyNamesByTag($mapping['targetEntity'], 'id');
                    $joinColumnName = $this->buildJoinTableColumnName($mapping['targetEntity']);
                } else {
                    $className = $this->getUnproxiedClassName($property->getDeclaringClass()->getName());
                    $idProperties = $this->reflectionService->getPropertyNamesByTag($className, 'id');
                    $joinColumnName = $this->buildJoinTableColumnName($className);
                }
                if (count($idProperties) === 0) {
                    $joinColumn['name'] = $joinColumn['name'] === null ? $joinColumnName : $joinColumn['name'];
                    $joinColumn['referencedColumnName'] = $this->getFallbackReferenceColumnName($property);
                } elseif (count($idProperties) === 1) {
                    $joinColumn['name'] = $joinColumn['name'] === null ? $joinColumnName : $joinColumn['name'];
                    $joinColumn['referencedColumnName'] = strtolower(current($idProperties));
                }
            }
        }

        return $joinColumns;
    }

    /**
     * Get a fallback reference column (which for Flow is the Persistence_Object_Identifier)
     *
     * @var \ReflectionProperty
     * @return string
     * @throws \TYPO3\Flow\Persistence\Exception
     * @see FlowAnnotationDriver::getFallbackReferenceColumnName()
     */
    protected function getFallbackReferenceColumnName(\ReflectionProperty $property)
    {
        throw new \TYPO3\Flow\Persistence\Exception('No reference column for the relation at property "' . $property->getName() . '" could be found.', 1405406193);
    }

    /**
     * Evaluate the property annotations and amend the metadata accordingly.
     *
     * @param ClassMetadataInfo $metadata
     * @return void
     * @throws MappingException
     */
    protected function evaluatePropertyAnnotations(ClassMetadataInfo $metadata)
    {
        $className = $metadata->name;

        $class = $metadata->getReflectionClass();
        $classSchema = $this->getClassSchema($className);

        foreach ($class->getProperties() as $property) {
            if (!$classSchema->hasProperty($property->getName())
                || $classSchema->isPropertyTransient($property->getName())
                || ($metadata->isMappedSuperclass && !$property->isPrivate())
                || $metadata->isInheritedField($property->getName())
                || $metadata->isInheritedAssociation($property->getName())
            ) {
                continue;
            }

            $propertyMetaData = $classSchema->getProperty($property->getName());

            $mapping = [];
            $mapping['fieldName'] = $property->getName();
            $mapping['columnName'] = strtolower($property->getName());
            $mapping['targetEntity'] = $propertyMetaData['type'];

            $joinColumns = $this->evaluateJoinColumnAnnotations($property);

            // Field can only be annotated with one of:
            // @OneToOne, @OneToMany, @ManyToOne, @ManyToMany, @Column (optional)
            if ($oneToOneAnnotation = $this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\OneToOne::class)) {
                if ($oneToOneAnnotation->targetEntity) {
                    $mapping['targetEntity'] = $oneToOneAnnotation->targetEntity;
                }
                if ($oneToOneAnnotation->inversedBy !== null || $oneToOneAnnotation->mappedBy === null) {
                    $mapping['joinColumns'] = $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property);
                }
                $mapping['mappedBy'] = $oneToOneAnnotation->mappedBy;
                $mapping['inversedBy'] = $oneToOneAnnotation->inversedBy;
                if ($oneToOneAnnotation->cascade) {
                    $mapping['cascade'] = $oneToOneAnnotation->cascade;
                } elseif ($this->isValueObject($mapping['targetEntity'], $className)) {
                    $mapping['cascade'] = ['persist'];
                } elseif ($this->isAggregateRoot($mapping['targetEntity'], $className) === false) {
                    $mapping['cascade'] = ['all'];
                }
                if ($oneToOneAnnotation->orphanRemoval) {
                    $mapping['orphanRemoval'] = $oneToOneAnnotation->orphanRemoval;
                } elseif ($this->isAggregateRoot($mapping['targetEntity'], $className) === false &&
                    $this->isValueObject($mapping['targetEntity'], $className) === false
                ) {
                    $mapping['orphanRemoval'] = true;
                }
                $mapping['fetch'] = $this->getFetchMode($className, $oneToOneAnnotation->fetch);
                $metadata->mapOneToOne($mapping);
            } elseif ($oneToManyAnnotation = $this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\OneToMany::class)) {
                $mapping['mappedBy'] = $oneToManyAnnotation->mappedBy;
                if ($oneToManyAnnotation->targetEntity) {
                    $mapping['targetEntity'] = $oneToManyAnnotation->targetEntity;
                } elseif (isset($propertyMetaData['elementType'])) {
                    $mapping['targetEntity'] = $propertyMetaData['elementType'];
                }
                if ($oneToManyAnnotation->cascade) {
                    $mapping['cascade'] = $oneToManyAnnotation->cascade;
                } elseif ($this->isValueObject($mapping['targetEntity'], $className)) {
                    $mapping['cascade'] = ['persist'];
                } elseif ($this->isAggregateRoot($mapping['targetEntity'], $className) === false) {
                    $mapping['cascade'] = ['all'];
                }
                $mapping['indexBy'] = $oneToManyAnnotation->indexBy;
                if ($oneToManyAnnotation->orphanRemoval) {
                    $mapping['orphanRemoval'] = $oneToManyAnnotation->orphanRemoval;
                } elseif ($this->isAggregateRoot($mapping['targetEntity'], $className) === false &&
                    $this->isValueObject($mapping['targetEntity'], $className) === false
                ) {
                    $mapping['orphanRemoval'] = true;
                }
                $mapping['fetch'] = $this->getFetchMode($className, $oneToManyAnnotation->fetch);

                if ($orderByAnnotation = $this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\OrderBy::class)) {
                    $mapping['orderBy'] = $orderByAnnotation->value;
                }

                $metadata->mapOneToMany($mapping);
            } elseif ($manyToOneAnnotation = $this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\ManyToOne::class)) {
                if ($manyToOneAnnotation->targetEntity) {
                    $mapping['targetEntity'] = $manyToOneAnnotation->targetEntity;
                }

                $mapping['joinColumns'] = $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property);
                if ($manyToOneAnnotation->cascade) {
                    $mapping['cascade'] = $manyToOneAnnotation->cascade;
                } elseif ($this->isValueObject($mapping['targetEntity'], $className)) {
                    $mapping['cascade'] = ['persist'];
                } elseif ($this->isAggregateRoot($mapping['targetEntity'], $className) === false) {
                    $mapping['cascade'] = ['all'];
                }
                $mapping['inversedBy'] = $manyToOneAnnotation->inversedBy;
                $mapping['fetch'] = $this->getFetchMode($className, $manyToOneAnnotation->fetch);
                $metadata->mapManyToOne($mapping);
            } elseif ($manyToManyAnnotation = $this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\ManyToMany::class)) {
                if ($manyToManyAnnotation->targetEntity) {
                    $mapping['targetEntity'] = $manyToManyAnnotation->targetEntity;
                } elseif (isset($propertyMetaData['elementType'])) {
                    $mapping['targetEntity'] = $propertyMetaData['elementType'];
                }
                /** @var JoinTable $joinTableAnnotation */
                if ($joinTableAnnotation = $this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\JoinTable::class)) {
                    $joinTable = $this->evaluateJoinTableAnnotation($joinTableAnnotation, $property, $className, $mapping);
                } else {
                    $joinColumns = [
                        [
                            'name' => null,
                            'referencedColumnName' => null,
                        ]
                    ];

                    $joinTable = [
                        'name' => $this->inferJoinTableNameFromClassAndPropertyName($className, $property->getName()),
                        'joinColumns' => $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property, self::MAPPING_MM_REGULAR),
                        'inverseJoinColumns' => $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property)
                    ];
                }

                $mapping['joinTable'] = $joinTable;
                $mapping['mappedBy'] = $manyToManyAnnotation->mappedBy;
                $mapping['inversedBy'] = $manyToManyAnnotation->inversedBy;
                if ($manyToManyAnnotation->cascade) {
                    $mapping['cascade'] = $manyToManyAnnotation->cascade;
                } elseif ($this->isValueObject($mapping['targetEntity'], $className)) {
                    $mapping['cascade'] = ['persist'];
                } elseif ($this->isAggregateRoot($mapping['targetEntity'], $className) === false) {
                    $mapping['cascade'] = ['all'];
                }
                $mapping['indexBy'] = $manyToManyAnnotation->indexBy;
                $mapping['orphanRemoval'] = $manyToManyAnnotation->orphanRemoval;
                $mapping['fetch'] = $this->getFetchMode($className, $manyToManyAnnotation->fetch);

                if ($orderByAnnotation = $this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\OrderBy::class)) {
                    $mapping['orderBy'] = $orderByAnnotation->value;
                }

                $metadata->mapManyToMany($mapping);
            } else {
                $mapping['nullable'] = false;

                /** @var Column $columnAnnotation */
                if ($columnAnnotation = $this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\Column::class)) {
                    $mapping = $this->addColumnToMappingArray($columnAnnotation, $mapping);
                }

                if (!isset($mapping['type'])) {
                    switch ($propertyMetaData['type']) {
                        case 'DateTime':
                            $mapping['type'] = 'datetime';
                            break;
                        case 'string':
                        case 'integer':
                        case 'boolean':
                        case 'float':
                        case 'array':
                            $mapping['type'] = $propertyMetaData['type'];
                            break;
                        default:
                            if (strpos($propertyMetaData['type'], '\\') !== false) {
                                if ($this->reflectionService->isClassAnnotatedWith($propertyMetaData['type'], \TYPO3\Flow\Annotations\ValueObject::class)) {
                                    $mapping['type'] = 'object';
                                } elseif (class_exists($propertyMetaData['type'])) {
                                    throw MappingException::missingRequiredOption($property->getName(), 'OneToOne', sprintf('The property "%s" in class "%s" has a non standard data type and doesn\'t define the type of the relation. You have to use one of these annotations: @OneToOne, @OneToMany, @ManyToOne, @ManyToMany', $property->getName(), $className));
                                }
                            } else {
                                throw MappingException::propertyTypeIsRequired($className, $property->getName());
                            }
                    }
                }

                if ($this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\Id::class) !== null) {
                    $mapping['id'] = true;
                }

                if ($generatedValueAnnotation = $this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\GeneratedValue::class)) {
                    $metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_' . strtoupper($generatedValueAnnotation->strategy)));
                }

                if ($this->reflectionService->isPropertyAnnotatedWith($className, $property->getName(), \Doctrine\ORM\Mapping\Version::class)) {
                    $metadata->setVersionMapping($mapping);
                }

                $metadata->mapField($mapping);

                // Check for SequenceGenerator/TableGenerator definition
                if ($seqGeneratorAnnotation = $this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\SequenceGenerator::class)) {
                    $metadata->setSequenceGeneratorDefinition([
                        'sequenceName' => $seqGeneratorAnnotation->sequenceName,
                        'allocationSize' => $seqGeneratorAnnotation->allocationSize,
                        'initialValue' => $seqGeneratorAnnotation->initialValue
                    ]);
                } elseif ($this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\TableGenerator::class) !== null) {
                    throw MappingException::tableIdGeneratorNotImplemented($className);
                } elseif ($customGeneratorAnnotation = $this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\CustomIdGenerator::class)) {
                    $metadata->setCustomGeneratorDefinition([
                        'class' => $customGeneratorAnnotation->class
                    ]);
                }
            }
        }
    }

    /**
     * Evaluate JoinTable annotations and fill missing bits as needed.
     *
     * @param JoinTable $joinTableAnnotation
     * @param \ReflectionProperty $property
     * @param string $className
     * @param array $mapping
     * @return array
     */
    protected function evaluateJoinTableAnnotation(JoinTable $joinTableAnnotation, \ReflectionProperty $property, $className, array $mapping)
    {
        $joinTable = [
            'name' => $joinTableAnnotation->name,
            'schema' => $joinTableAnnotation->schema
        ];
        if ($joinTable['name'] === null) {
            $joinTable['name'] = $this->inferJoinTableNameFromClassAndPropertyName($className, $property->getName());
        }

        foreach ($joinTableAnnotation->joinColumns as $joinColumn) {
            $joinTable['joinColumns'][] = $this->joinColumnToArray($joinColumn);
        }
        if (array_key_exists('joinColumns', $joinTable)) {
            $joinTable['joinColumns'] = $this->buildJoinColumnsIfNeeded($joinTable['joinColumns'], $mapping, $property, self::MAPPING_MM_REGULAR);
        } else {
            $joinColumns = [
                [
                    'name' => null,
                    'referencedColumnName' => null,
                ]
            ];
            $joinTable['joinColumns'] = $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property, self::MAPPING_MM_REGULAR);
        }

        foreach ($joinTableAnnotation->inverseJoinColumns as $joinColumn) {
            $joinTable['inverseJoinColumns'][] = $this->joinColumnToArray($joinColumn);
        }
        if (array_key_exists('inverseJoinColumns', $joinTable)) {
            $joinTable['inverseJoinColumns'] = $this->buildJoinColumnsIfNeeded($joinTable['inverseJoinColumns'], $mapping, $property);
        } else {
            $joinColumns = [
                [
                    'name' => null,
                    'referencedColumnName' => null,
                ]
            ];
            $joinTable['inverseJoinColumns'] = $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property);
        }

        return $joinTable;
    }

    /**
     * Check for and build JoinColummn/JoinColumns annotations.
     *
     * If no annotations are found, a default is returned.
     *
     * @param \ReflectionProperty $property
     * @return array
     */
    protected function evaluateJoinColumnAnnotations(\ReflectionProperty $property)
    {
        $joinColumns = [];

        /** @var JoinColumn $joinColumnAnnotation */
        if ($joinColumnAnnotation = $this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\JoinColumn::class)) {
            $joinColumns[] = $this->joinColumnToArray($joinColumnAnnotation, strtolower($property->getName()));
        } elseif ($joinColumnsAnnotation = $this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\JoinColumns::class)) {
            foreach ($joinColumnsAnnotation->value as $joinColumnAnnotation) {
                $joinColumns[] = $this->joinColumnToArray($joinColumnAnnotation, strtolower($property->getName()));
            }
        }

        return $joinColumns;
    }

    /**
     * Evaluate the association overrides annotations and amend the metadata accordingly.
     *
     * @param array $classAnnotations
     * @param ClassMetadataInfo $metadata
     * @return void
     */
    protected function evaluateOverridesAnnotations(array $classAnnotations, ClassMetadataInfo $metadata)
    {
        if (isset($classAnnotations[\Doctrine\ORM\Mapping\AssociationOverrides::class])) {
            $associationOverridesAnnotation = $classAnnotations[\Doctrine\ORM\Mapping\AssociationOverrides::class];

            foreach ($associationOverridesAnnotation->value as $associationOverride) {
                $override = [];
                $fieldName = $associationOverride->name;

                // Check for JoinColumn/JoinColumns annotations
                if ($associationOverride->joinColumns) {
                    $joinColumns = [];
                    foreach ($associationOverride->joinColumns as $joinColumn) {
                        $joinColumns[] = $this->joinColumnToArray($joinColumn);
                    }
                    $override['joinColumns'] = $joinColumns;
                }

                // Check for JoinTable annotations
                if ($associationOverride->joinTable) {
                    $joinTable = null;
                    $joinTableAnnotation = $associationOverride->joinTable;
                    $joinTable = [
                        'name' => $joinTableAnnotation->name,
                        'schema' => $joinTableAnnotation->schema
                    ];

                    foreach ($joinTableAnnotation->joinColumns as $joinColumn) {
                        $joinTable['joinColumns'][] = $this->joinColumnToArray($joinColumn);
                    }

                    foreach ($joinTableAnnotation->inverseJoinColumns as $joinColumn) {
                        $joinTable['inverseJoinColumns'][] = $this->joinColumnToArray($joinColumn);
                    }

                    $override['joinTable'] = $joinTable;
                }

                $metadata->setAssociationOverride($fieldName, $override);
            }
        }

        // Evaluate AttributeOverrides annotation
        if (isset($classAnnotations[\Doctrine\ORM\Mapping\AttributeOverrides::class])) {
            $attributeOverridesAnnotation = $classAnnotations[\Doctrine\ORM\Mapping\AttributeOverrides::class];
            foreach ($attributeOverridesAnnotation->value as $attributeOverrideAnnotation) {
                $attributeOverride = $this->addColumnToMappingArray($attributeOverrideAnnotation->column, [], $attributeOverrideAnnotation->name);
                $metadata->setAttributeOverride($attributeOverrideAnnotation->name, $attributeOverride);
            }
        }
    }

    /**
     * Evaluate the EntityListeners annotation and amend the metadata accordingly.
     *
     * @param \ReflectionClass $class
     * @param OrmClassMetadata $metadata
     * @param array $classAnnotations
     * @return void
     * @throws MappingException
     */
    protected function evaluateEntityListenersAnnotation(\ReflectionClass $class, OrmClassMetadata $metadata, array $classAnnotations)
    {
        if (isset($classAnnotations[\Doctrine\ORM\Mapping\EntityListeners::class])) {
            $entityListenersAnnotation = $classAnnotations[\Doctrine\ORM\Mapping\EntityListeners::class];

            foreach ($entityListenersAnnotation->value as $item) {
                $listenerClassName = $metadata->fullyQualifiedClassName($item);

                if (!class_exists($listenerClassName)) {
                    throw MappingException::entityListenerClassNotFound($listenerClassName, $class->getName());
                }

                $hasMapping = false;
                foreach ($class->getMethods() as $method) {
                    if ($method->isPublic()) {
                        // find method callbacks.
                        $callbacks = $this->getMethodCallbacks($method);
                        $hasMapping = $hasMapping ?: (!empty($callbacks));

                        foreach ($callbacks as $value) {
                            $metadata->addEntityListener($value[1], $listenerClassName, $value[0]);
                        }
                    }
                }

                // Evaluate the listener using naming convention.
                if ($hasMapping === false) {
                    EntityListenerBuilder::bindEntityListener($metadata, $listenerClassName);
                }
            }
        }
    }

    /**
     * Evaluate the lifecycle annotations and amend the metadata accordingly.
     *
     * @param \ReflectionClass $class
     * @param ClassMetadataInfo $metadata
     * @return void
     */
    protected function evaluateLifeCycleAnnotations(\ReflectionClass $class, ClassMetadataInfo $metadata)
    {
        foreach ($class->getMethods() as $method) {
            if ($method->isPublic()) {
                foreach ($this->getMethodCallbacks($method) as $value) {
                    $metadata->addLifecycleCallback($value[0], $value[1]);
                }
            }
        }

        $this->additionalLifecycleCallbacks($class, $metadata);
    }

    /**
     * Add additional lifecycle callbacks that are "hardcoded"
     *
     * @param \ReflectionClass $class
     * @param \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata
     */
    protected function additionalLifecycleCallbacks(\ReflectionClass $class, \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata)
    {
    }

    /**
     * Returns an array of callbacks for lifecycle annotations on the given method.
     *
     * @param \ReflectionMethod $method
     * @return array
     */
    protected function getMethodCallbacks(\ReflectionMethod $method)
    {
        $callbacks = [];
        $annotations = $this->reader->getMethodAnnotations($method);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof \Doctrine\ORM\Mapping\PrePersist) {
                $callbacks[] = [$method->name, Events::prePersist];
            }

            if ($annotation instanceof \Doctrine\ORM\Mapping\PostPersist) {
                $callbacks[] = [$method->name, Events::postPersist];
            }

            if ($annotation instanceof \Doctrine\ORM\Mapping\PreUpdate) {
                $callbacks[] = [$method->name, Events::preUpdate];
            }

            if ($annotation instanceof \Doctrine\ORM\Mapping\PostUpdate) {
                $callbacks[] = [$method->name, Events::postUpdate];
            }

            if ($annotation instanceof \Doctrine\ORM\Mapping\PreRemove) {
                $callbacks[] = [$method->name, Events::preRemove];
            }

            if ($annotation instanceof \Doctrine\ORM\Mapping\PostRemove) {
                $callbacks[] = [$method->name, Events::postRemove];
            }

            if ($annotation instanceof \Doctrine\ORM\Mapping\PostLoad) {
                $callbacks[] = [$method->name, Events::postLoad];
            }

            if ($annotation instanceof \Doctrine\ORM\Mapping\PreFlush) {
                $callbacks[] = [$method->name, Events::preFlush];
            }
        }

        return $callbacks;
    }

    /**
     * Derive maximum identifier length from doctrine DBAL
     *
     * @return integer
     */
    protected function getMaxIdentifierLength()
    {
        if ($this->tableNameLengthLimit === null) {
            $this->tableNameLengthLimit = $this->entityManager->getConnection()->getDatabasePlatform()->getMaxIdentifierLength();
        }

        return $this->tableNameLengthLimit;
    }

    /**
     * Returns whether the class with the specified name is transient. Only non-transient
     * classes, that is entities and mapped superclasses, should have their metadata loaded.
     *
     * @param string $className
     * @return boolean
     */
    public function isTransient($className)
    {
        return strpos($className, Compiler::ORIGINAL_CLASSNAME_SUFFIX) !== false ||
        (
            !$this->reflectionService->isClassAnnotatedWith($className, \TYPO3\Flow\Annotations\Entity::class) &&
            !$this->reflectionService->isClassAnnotatedWith($className, \TYPO3\Flow\Annotations\ValueObject::class) &&
            !$this->reflectionService->isClassAnnotatedWith($className, \Doctrine\ORM\Mapping\Entity::class) &&
            !$this->reflectionService->isClassAnnotatedWith($className, \Doctrine\ORM\Mapping\MappedSuperclass::class)
        );
    }

    /**
     * Returns the names of all mapped (non-transient) classes known to this driver.
     *
     * @return array
     */
    public function getAllClassNames()
    {
        if (is_array($this->classNames)) {
            return $this->classNames;
        }

        $this->classNames = array_merge(
            $this->reflectionService->getClassNamesByAnnotation(\TYPO3\Flow\Annotations\ValueObject::class),
            $this->reflectionService->getClassNamesByAnnotation(\TYPO3\Flow\Annotations\Entity::class),
            $this->reflectionService->getClassNamesByAnnotation(\Doctrine\ORM\Mapping\Entity::class),
            $this->reflectionService->getClassNamesByAnnotation(\Doctrine\ORM\Mapping\MappedSuperclass::class)
        );
        $this->classNames = array_filter($this->classNames,
            function ($className) {
                return !interface_exists($className, false)
                && strpos($className, Compiler::ORIGINAL_CLASSNAME_SUFFIX) === false;
            }
        );

        return $this->classNames;
    }

    /**
     * Parse the given JoinColumn into an array
     *
     * @param JoinColumn $joinColumnAnnotation
     * @param string $propertyName
     * @return array
     */
    protected function joinColumnToArray(JoinColumn $joinColumnAnnotation, $propertyName = null)
    {
        return [
            'name' => $joinColumnAnnotation->name === null ? $propertyName : $joinColumnAnnotation->name,
            'unique' => $joinColumnAnnotation->unique,
            'nullable' => $joinColumnAnnotation->nullable,
            'onDelete' => $joinColumnAnnotation->onDelete,
            'columnDefinition' => $joinColumnAnnotation->columnDefinition,
            'referencedColumnName' => $joinColumnAnnotation->referencedColumnName,
        ];
    }

    /**
     * Parse the given Column into an array
     *
     * @param Column $columnAnnotation
     * @param array $mapping
     * @param string $fieldName
     * @return array
     */
    protected function addColumnToMappingArray(Column $columnAnnotation, array $mapping = [], $fieldName = null)
    {
        if ($fieldName !== null) {
            $mapping['fieldName'] = $fieldName;
        }

        $mapping['type'] = ($columnAnnotation->type === 'string') ? null : $columnAnnotation->type;
        $mapping['scale'] = $columnAnnotation->scale;
        $mapping['length'] = $columnAnnotation->length;
        $mapping['unique'] = $columnAnnotation->unique;
        $mapping['nullable'] = $columnAnnotation->nullable;
        $mapping['precision'] = $columnAnnotation->precision;

        if ($columnAnnotation->options) {
            $mapping['options'] = $columnAnnotation->options;
        }

        if (isset($columnAnnotation->name)) {
            $mapping['columnName'] = $columnAnnotation->name;
        }

        if (isset($columnAnnotation->columnDefinition)) {
            $mapping['columnDefinition'] = $columnAnnotation->columnDefinition;
        }

        return $mapping;
    }

    /**
     * Returns the classname after stripping a potentially present Compiler::ORIGINAL_CLASSNAME_SUFFIX.
     *
     * @param string $className
     * @return string
     */
    protected function getUnproxiedClassName($className)
    {
        $className = preg_replace('/' . Compiler::ORIGINAL_CLASSNAME_SUFFIX . '$/', '', $className);

        return $className;
    }

    /**
     * Attempts to resolve the fetch mode.
     *
     * @param string $className The class name
     * @param string $fetchMode The fetch mode
     * @return integer The fetch mode as defined in ClassMetadata
     * @throws MappingException If the fetch mode is not valid
     */
    private function getFetchMode($className, $fetchMode)
    {
        $fetchMode = strtoupper($fetchMode);
        if (!defined('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $fetchMode)) {
            throw MappingException::invalidFetchMode($className, $fetchMode);
        }

        return constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $fetchMode);
    }

    /**
     * Checks if the specified class has a property annotated with Id
     *
     * @param string $className Name of the class to check against
     * @param string $methodName Name of the method to check against
     * @param string $methodDeclaringClassName Name of the class the method was originally declared in
     * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
     * @return boolean TRUE if the class has *no* Id properties
     */
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)
    {
        $class = new \ReflectionClass($className);
        foreach ($class->getProperties() as $property) {
            if ($this->reader->getPropertyAnnotation($property, \Doctrine\ORM\Mapping\Id::class) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
     *
     * @return boolean TRUE if this filter has runtime evaluations
     */
    public function hasRuntimeEvaluationsDefinition()
    {
        return false;
    }

    /**
     * Returns runtime evaluations for a previously matched pointcut
     *
     * @return array Runtime evaluations
     */
    public function getRuntimeEvaluationsDefinition()
    {
        return [];
    }

    /**
     * This method is used to optimize the matching process.
     *
     * @param ClassNameIndex $classNameIndex
     * @return ClassNameIndex
     */
    public function reduceTargetClassNames(ClassNameIndex $classNameIndex)
    {
        return $classNameIndex;
    }
}
