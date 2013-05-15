<?php
namespace TYPO3\Flow\Persistence\Doctrine\Mapping\Driver;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * This driver reads the mapping metadata from docblock annotations.
 * It gives precedence to Doctrine annotations but fills gaps from other info
 * if possible:
 *  Entity.repositoryClass is set to the repository found in the class schema
 *  Table.name is set to a sane value
 *  Column.type is set to @var type
 *  *.targetEntity is set to @var type
 *
 * If a property is not marked as an association the mapping type is set to
 * "object" for objects.
 *
 * @Flow\Scope("singleton")
 */
class FlowAnnotationDriver implements \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver, \TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface {

	/**
	 * @var integer
	 */
	const MAPPING_REGULAR = 0;
	const MAPPING_MM_REGULAR = 1;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \Doctrine\Common\Annotations\AnnotationReader
	 */
	protected $reader;

	/**
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @var array
	 */
	protected $classNames;

	/**
	 * @var integer
	 */
	protected $tableNameLengthLimit = NULL;

	/**
	 * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
	 * docblock annotations.
	 */
	public function __construct() {
		$this->reader = new \Doctrine\Common\Annotations\IndexedReader(new \Doctrine\Common\Annotations\AnnotationReader());
	}

	/**
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \Doctrine\Common\Persistence\ObjectManager $entityManager
	 * @return void
	 */
	public function setEntityManager(\Doctrine\Common\Persistence\ObjectManager $entityManager) {
		$this->entityManager = $entityManager;
	}

	/**
	 * Fetch a class schema for the given class, if possible.
	 *
	 * @param string $className
	 * @return \TYPO3\Flow\Reflection\ClassSchema
	 * @throws \TYPO3\Flow\Persistence\Doctrine\Mapping\Exception\ClassSchemaNotFoundException
	 */
	protected function getClassSchema($className) {
		$className = preg_replace('/' . \TYPO3\Flow\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX . '$/', '', $className);

		$classSchema = $this->reflectionService->getClassSchema($className);
		if (!$classSchema) {
			throw new \TYPO3\Flow\Persistence\Doctrine\Mapping\Exception\ClassSchemaNotFoundException('No class schema found for "' . $className . '".', 1295973082);
		}
		return $classSchema;
	}

	/**
	 * Check for $className being an aggregate root.
	 *
	 * @param string $className
	 * @param string $propertySourceHint
	 * @return boolean
	 * @throws \TYPO3\Flow\Persistence\Doctrine\Mapping\Exception\ClassSchemaNotFoundException
	 */
	protected function isAggregateRoot($className, $propertySourceHint) {
		$className = preg_replace('/' . \TYPO3\Flow\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX . '$/', '', $className);
		try {
			$classSchema = $this->getClassSchema($className);
			return $classSchema->isAggregateRoot();
		} catch (\TYPO3\Flow\Persistence\Doctrine\Mapping\Exception\ClassSchemaNotFoundException $exception) {
			throw new \TYPO3\Flow\Persistence\Doctrine\Mapping\Exception\ClassSchemaNotFoundException('No class schema found for "' . $className . '". The class should probably marked as entity or value object! This happened while examining "' . $propertySourceHint . '"', 1340185197);
		}
	}

	/**
	 * Loads the metadata for the specified class into the provided container.
	 *
	 * @param string $className
	 * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata
	 * @return void
	 * @throws \Doctrine\ORM\Mapping\MappingException
	 * @throws \UnexpectedValueException
	 * @todo adjust when Doctrine 2 supports value objects, see http://www.doctrine-project.org/jira/browse/DDC-93
	 */
	public function loadMetadataForClass($className, \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata) {
		$class = $metadata->getReflectionClass();
		$classSchema = $this->getClassSchema($class->getName());
		$classAnnotations = $this->reader->getClassAnnotations($class);

			// Evaluate Entity annotation
		if (isset($classAnnotations['Doctrine\ORM\Mapping\MappedSuperclass'])) {
			$mappedSuperclassAnnotation = $classAnnotations['Doctrine\ORM\Mapping\MappedSuperclass'];
			if ($mappedSuperclassAnnotation->repositoryClass !== NULL) {
				$metadata->setCustomRepositoryClass($mappedSuperclassAnnotation->repositoryClass);
			}
			$metadata->isMappedSuperclass = TRUE;
		} elseif (isset($classAnnotations['TYPO3\Flow\Annotations\Entity']) || isset($classAnnotations['Doctrine\ORM\Mapping\Entity'])) {
			$entityAnnotation = isset($classAnnotations['TYPO3\Flow\Annotations\Entity']) ? $classAnnotations['TYPO3\Flow\Annotations\Entity'] : $classAnnotations['Doctrine\ORM\Mapping\Entity'];
			if ($entityAnnotation->repositoryClass !== NULL) {
				$metadata->setCustomRepositoryClass($entityAnnotation->repositoryClass);
			} elseif ($classSchema->getRepositoryClassName() !== NULL) {
				if ($this->reflectionService->isClassImplementationOf($classSchema->getRepositoryClassName(), 'Doctrine\ORM\EntityRepository')) {
					$metadata->setCustomRepositoryClass($classSchema->getRepositoryClassName());
				}
			}
			if ($entityAnnotation->readOnly) {
				$metadata->markReadOnly();
			}
		} elseif ($classSchema->getModelType() === \TYPO3\Flow\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT) {
				// also ok... but we make it read-only
			$metadata->markReadOnly();
		} else {
			throw \Doctrine\ORM\Mapping\MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
		}

			// Evaluate Table annotation
		$primaryTable = array();
		if (isset($classAnnotations['Doctrine\ORM\Mapping\Table'])) {
			$tableAnnotation = $classAnnotations['Doctrine\ORM\Mapping\Table'];
			$primaryTable['name'] = $tableAnnotation->name;
			$primaryTable['schema'] = $tableAnnotation->schema;

			if ($tableAnnotation->indexes !== NULL) {
				foreach ($tableAnnotation->indexes as $indexAnnotation) {
					$index = array('columns' => $indexAnnotation->columns);
					if (!empty($indexAnnotation->name)) {
						$primaryTable['indexes'][$indexAnnotation->name] = $index;
					} else {
						$primaryTable['indexes'][] = $index;
					}
				}
			}

			if ($tableAnnotation->uniqueConstraints !== NULL) {
				foreach ($tableAnnotation->uniqueConstraints as $uniqueConstraint) {
					$uniqueConstraint = array('columns' => $uniqueConstraint->columns);
					if (!empty($uniqueConstraint->name)) {
						$primaryTable['uniqueConstraints'][$uniqueConstraint->name] = $uniqueConstraint;
					} else {
						$primaryTable['uniqueConstraints'][] = $uniqueConstraint;
					}
				}
			}

			if ($tableAnnotation->options !== NULL) {
				$primaryTable['options'] = $tableAnnotation->options;
			}
		}
		if (!isset($primaryTable['name'])) {
			$className = $classSchema->getClassName();
			$primaryTable['name'] = $this->inferTableNameFromClassName($className);
		}

			// Evaluate NamedNativeQueries annotation
		if (isset($classAnnotations['Doctrine\ORM\Mapping\NamedNativeQueries'])) {
			$namedNativeQueriesAnnotation = $classAnnotations['Doctrine\ORM\Mapping\NamedNativeQueries'];

			foreach ($namedNativeQueriesAnnotation->value as $namedNativeQuery) {
				$metadata->addNamedNativeQuery(array(
					'name' => $namedNativeQuery->name,
					'query' => $namedNativeQuery->query,
					'resultClass' => $namedNativeQuery->resultClass,
					'resultSetMapping' => $namedNativeQuery->resultSetMapping,
				));
			}
		}

			// Evaluate SqlResultSetMappings annotation
		if (isset($classAnnotations['Doctrine\ORM\Mapping\SqlResultSetMappings'])) {
			$sqlResultSetMappingsAnnotation = $classAnnotations['Doctrine\ORM\Mapping\SqlResultSetMappings'];

			foreach ($sqlResultSetMappingsAnnotation->value as $resultSetMapping) {
				$entities = array();
				$columns  = array();
				foreach ($resultSetMapping->entities as $entityResultAnnotation) {
					$entityResult = array(
						'fields' => array(),
						'entityClass' => $entityResultAnnotation->entityClass,
						'discriminatorColumn' => $entityResultAnnotation->discriminatorColumn,
					);

					foreach ($entityResultAnnotation->fields as $fieldResultAnnotation) {
						$entityResult['fields'][] = array(
							'name' => $fieldResultAnnotation->name,
							'column' => $fieldResultAnnotation->column
						);
					}

					$entities[] = $entityResult;
				}

				foreach ($resultSetMapping->columns as $columnResultAnnotation) {
					$columns[] = array(
						'name' => $columnResultAnnotation->name,
					);
				}

				$metadata->addSqlResultSetMapping(array(
					'name' => $resultSetMapping->name,
					'entities' => $entities,
					'columns' => $columns
				));
			}
		}

			// Evaluate NamedQueries annotation
		if (isset($classAnnotations['Doctrine\ORM\Mapping\NamedQueries'])) {
			$namedQueriesAnnotation = $classAnnotations['Doctrine\ORM\Mapping\NamedQueries'];

			if (!is_array($namedQueriesAnnotation->value)) {
				throw new \UnexpectedValueException('@NamedQueries should contain an array of @NamedQuery annotations.');
			}

			foreach ($namedQueriesAnnotation->value as $namedQuery) {
				if (!($namedQuery instanceof \Doctrine\ORM\Mapping\NamedQuery)) {
					throw new \UnexpectedValueException('@NamedQueries should contain an array of @NamedQuery annotations.');
				}
				$metadata->addNamedQuery(array(
					'name'  => $namedQuery->name,
					'query' => $namedQuery->query
				));
			}
		}

			// Evaluate InheritanceType annotation
		if (isset($classAnnotations['Doctrine\ORM\Mapping\InheritanceType'])) {
			$inheritanceTypeAnnotation = $classAnnotations['Doctrine\ORM\Mapping\InheritanceType'];
			$inheritanceType = constant('Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_' . strtoupper($inheritanceTypeAnnotation->value));

			if ($inheritanceType !== \Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_NONE) {

					// Evaluate DiscriminatorColumn annotation
				if (isset($classAnnotations['Doctrine\ORM\Mapping\DiscriminatorColumn'])) {
					$discriminatorColumnAnnotation = $classAnnotations['Doctrine\ORM\Mapping\DiscriminatorColumn'];
					$discriminatorColumn = array(
						'name' => $discriminatorColumnAnnotation->name,
						'type' => $discriminatorColumnAnnotation->type,
						'length' => $discriminatorColumnAnnotation->length,
						'columnDefinition' => $discriminatorColumnAnnotation->columnDefinition
					);
				} else {
					$discriminatorColumn = array(
						'name' => 'dtype', 'type' => 'string', 'length' => 255
					);
				}

					// Evaluate DiscriminatorMap annotation
				if (isset($classAnnotations['Doctrine\ORM\Mapping\DiscriminatorMap'])) {
					$discriminatorMapAnnotation = $classAnnotations['Doctrine\ORM\Mapping\DiscriminatorMap'];
					$discriminatorMap = $discriminatorMapAnnotation->value;
				} else {
					$discriminatorMap = array();
					$subclassNames = $this->reflectionService->getAllSubClassNamesForClass($className);
					if (!$this->reflectionService->isClassAbstract($className)) {
						$mappedClassName = strtolower(str_replace('Domain_Model_', '', str_replace('\\', '_', $className)));
						$discriminatorMap[$mappedClassName] = $className;
					}
					foreach ($subclassNames as $subclassName) {
						$mappedSubclassName = strtolower(str_replace('Domain_Model_', '', str_replace('\\', '_', $subclassName)));
						$discriminatorMap[$mappedSubclassName] = $subclassName;
					}
				}

				if (count($discriminatorMap) > 1) {
					$metadata->setDiscriminatorColumn($discriminatorColumn);
					$metadata->setDiscriminatorMap($discriminatorMap);
				} else {
					$inheritanceType = \Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_NONE;
				}
			}

			$metadata->setInheritanceType($inheritanceType);
		}

			// Evaluate DoctrineChangeTrackingPolicy annotation
		if (isset($classAnnotations['Doctrine\ORM\Mapping\ChangeTrackingPolicy'])) {
			$changeTrackingAnnotation = $classAnnotations['Doctrine\ORM\Mapping\ChangeTrackingPolicy'];
			$metadata->setChangeTrackingPolicy(constant('Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_' . strtoupper($changeTrackingAnnotation->value)));
		} else {
			$metadata->setChangeTrackingPolicy(\Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_DEFERRED_EXPLICIT);
		}

			// Evaluate annotations on properties/fields
		$this->evaluatePropertyAnnotations($metadata);

			// build unique index for table
		if (!isset($primaryTable['uniqueConstraints'])) {
			$idProperties = array_keys($classSchema->getIdentityProperties());
			if (array_diff($idProperties, $metadata->getIdentifierFieldNames()) !== array()) {
				$uniqueIndexName = $this->truncateIdentifier('flow_identity_' . $primaryTable['name']);
				foreach ($idProperties as $idProperty) {
					$primaryTable['uniqueConstraints'][$uniqueIndexName]['columns'][] = isset($metadata->columnNames[$idProperty]) ? $metadata->columnNames[$idProperty] : strtolower($idProperty);
				}
			}
		}

		$metadata->setPrimaryTable($primaryTable);

			// Evaluate AssociationOverrides annotation
		$this->evaluateOverridesAnnotations($classAnnotations, $metadata);

			// Evaluate @HasLifecycleCallbacks annotation
		$this->evaluateLifeCycleAnnotations($class, $metadata);
	}

	/**
	 * Given a class name a table name is returned. That name should be reasonably unique.
	 *
	 * @param string $className
	 * @param integer $lengthLimit
	 * @return string
	 */
	public function inferTableNameFromClassName($className, $lengthLimit = NULL) {
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
	protected function truncateIdentifier($identifier, $lengthLimit = NULL, $hashSource = NULL) {
		if ($lengthLimit === NULL) {
			$lengthLimit = $this->getMaxIdentifierLength();
		}
		if (strlen($identifier) > $lengthLimit) {
			$identifier = substr($identifier, 0, $lengthLimit - 6) . '_' . substr(sha1($hashSource !== NULL ? $hashSource : $identifier), 0, 5);
		}
		return $identifier;
	}

	/**
	 * Given a class and property name a table name is returned. That name should be reasonably unique.
	 *
	 * @param string $className
	 * @param string $propertyName
	 * @return string
	 */
	protected function inferJoinTableNameFromClassAndPropertyName($className, $propertyName) {
		$prefix = $this->inferTableNameFromClassName($className);
		$suffix = '_' . strtolower($propertyName . '_join');
		if (strlen($prefix . $suffix) > $this->getMaxIdentifierLength()) {
			$prefix = $this->inferTableNameFromClassName($className, $this->getMaxIdentifierLength() - strlen($suffix));
		}
		return $prefix . $suffix;
	}

	/**
	 * Build a name for a column in a jointable.
	 *
	 * @param string $className
	 * @return string
	 */
	protected function buildJoinTableColumnName($className) {
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
	protected function buildJoinColumnsIfNeeded(array $joinColumns, array $mapping, \ReflectionProperty $property, $direction = self::MAPPING_REGULAR) {
		if ($joinColumns === array()) {
			$joinColumns[] = array(
				'name' => strtolower($property->getName()),
				'referencedColumnName' => NULL,
			);
		}
		foreach ($joinColumns as &$joinColumn) {
			if ($joinColumn['referencedColumnName'] === NULL || $joinColumn['referencedColumnName'] === 'id') {
				if ($direction === self::MAPPING_REGULAR) {
					$idProperties = $this->reflectionService->getPropertyNamesByTag($mapping['targetEntity'], 'id');
					$joinColumnName = $this->buildJoinTableColumnName($mapping['targetEntity']);
				} else {
					$className = preg_replace('/' . \TYPO3\Flow\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX . '$/', '', $property->getDeclaringClass()->getName());
					$idProperties = $this->reflectionService->getPropertyNamesByTag($className, 'id');
					$joinColumnName = $this->buildJoinTableColumnName($className);
				}
				if (count($idProperties) === 0) {
					$joinColumn['name'] = $joinColumn['name'] === NULL ? $joinColumnName : $joinColumn['name'];
					$joinColumn['referencedColumnName'] = strtolower('Persistence_Object_Identifier');
				} elseif (count($idProperties) === 1) {
					$joinColumn['name'] = $joinColumn['name'] === NULL ? $joinColumnName : $joinColumn['name'];
					$joinColumn['referencedColumnName'] = strtolower(current($idProperties));
				}
			}
		}

		return $joinColumns;
	}

	/**
	 * Evaluate the property annotations and amend the metadata accordingly.
	 *
	 * @param \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata
	 * @return void
	 * @throws \Doctrine\ORM\Mapping\MappingException
	 */
	protected function evaluatePropertyAnnotations(\Doctrine\ORM\Mapping\ClassMetadataInfo $metadata) {
		$className = $metadata->name;

		$class = $metadata->getReflectionClass();
		$classSchema = $this->getClassSchema($className);

		foreach ($class->getProperties() as $property) {
			if (!$classSchema->hasProperty($property->getName())
					|| $metadata->isMappedSuperclass && !$property->isPrivate()
					|| $metadata->isInheritedField($property->getName())
					|| $metadata->isInheritedAssociation($property->getName())) {
				continue;
			}

			$propertyMetaData = $classSchema->getProperty($property->getName());

			$mapping = array();
			$mapping['fieldName'] = $property->getName();
			$mapping['columnName'] = strtolower($property->getName());
			$mapping['targetEntity'] = $propertyMetaData['type'];

			$joinColumns = $this->evaluateJoinColumnAnnotations($property);

				// Field can only be annotated with one of:
				// @OneToOne, @OneToMany, @ManyToOne, @ManyToMany, @Column (optional)
			if ($oneToOneAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OneToOne')) {
				if ($oneToOneAnnotation->targetEntity) {
					$mapping['targetEntity'] = $oneToOneAnnotation->targetEntity;
				}
				$mapping['joinColumns'] = $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property);
				$mapping['mappedBy'] = $oneToOneAnnotation->mappedBy;
				$mapping['inversedBy'] = $oneToOneAnnotation->inversedBy;
				if ($oneToOneAnnotation->cascade) {
					$mapping['cascade'] = $oneToOneAnnotation->cascade;
				} elseif ($this->isAggregateRoot($mapping['targetEntity'], $className) === FALSE) {
					$mapping['cascade'] = array('all');
				}
				if ($oneToOneAnnotation->orphanRemoval) {
					$mapping['orphanRemoval'] = $oneToOneAnnotation->orphanRemoval;
				} elseif ($this->isAggregateRoot($mapping['targetEntity'], $className) === FALSE) {
					$mapping['orphanRemoval'] = TRUE;
				}
				$mapping['fetch'] = $this->getFetchMode($className, $oneToOneAnnotation->fetch);
				$metadata->mapOneToOne($mapping);
			} elseif ($oneToManyAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OneToMany')) {
				$mapping['mappedBy'] = $oneToManyAnnotation->mappedBy;
				if ($oneToManyAnnotation->targetEntity) {
					$mapping['targetEntity'] = $oneToManyAnnotation->targetEntity;
				} elseif (isset($propertyMetaData['elementType'])) {
					$mapping['targetEntity'] = $propertyMetaData['elementType'];
				}
				if ($oneToManyAnnotation->cascade) {
					$mapping['cascade'] = $oneToManyAnnotation->cascade;
				} elseif ($this->isAggregateRoot($mapping['targetEntity'], $className) === FALSE) {
					$mapping['cascade'] = array('all');
				}
				if ($oneToManyAnnotation->orphanRemoval) {
					$mapping['orphanRemoval'] = $oneToManyAnnotation->orphanRemoval;
				} elseif ($this->isAggregateRoot($mapping['targetEntity'], $className) === FALSE) {
					$mapping['orphanRemoval'] = TRUE;
				}
				$mapping['fetch'] = $this->getFetchMode($className, $oneToManyAnnotation->fetch);

				if ($orderByAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OrderBy')) {
					$mapping['orderBy'] = $orderByAnnotation->value;
				}

				$metadata->mapOneToMany($mapping);
			} elseif ($manyToOneAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToOne')) {
				if ($manyToOneAnnotation->targetEntity) {
					$mapping['targetEntity'] = $manyToOneAnnotation->targetEntity;
				}
				$mapping['joinColumns'] = $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property);
				if ($manyToOneAnnotation->cascade) {
					$mapping['cascade'] = $manyToOneAnnotation->cascade;
				} elseif ($this->isAggregateRoot($mapping['targetEntity'], $className) === FALSE) {
					$mapping['cascade'] = array('all');
				}
				$mapping['inversedBy'] = $manyToOneAnnotation->inversedBy;
				$mapping['fetch'] = $this->getFetchMode($className, $manyToOneAnnotation->fetch);
				$metadata->mapManyToOne($mapping);
			} elseif ($manyToManyAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToMany')) {
				if ($manyToManyAnnotation->targetEntity) {
					$mapping['targetEntity'] = $manyToManyAnnotation->targetEntity;
				} elseif (isset($propertyMetaData['elementType'])) {
					$mapping['targetEntity'] = $propertyMetaData['elementType'];
				}
				if ($joinTableAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinTable')) {
					$joinTable = $this->evaluateJoinTableAnnotation($joinTableAnnotation, $property, $className, $mapping);
				} else {
					$joinColumns = array(
						array(
							'name' => NULL,
							'referencedColumnName' => NULL,
						)
					);

					$joinTable = array(
						'name' => $this->inferJoinTableNameFromClassAndPropertyName($className, $property->getName()),
						'joinColumns' => $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property, self::MAPPING_MM_REGULAR),
						'inverseJoinColumns' => $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property)
					);
				}

				$mapping['joinTable'] = $joinTable;
				$mapping['mappedBy'] = $manyToManyAnnotation->mappedBy;
				$mapping['inversedBy'] = $manyToManyAnnotation->inversedBy;
				if ($manyToManyAnnotation->cascade) {
					$mapping['cascade'] = $manyToManyAnnotation->cascade;
				} elseif ($this->isAggregateRoot($mapping['targetEntity'], $className) === FALSE) {
					$mapping['cascade'] = array('all');
				}

				$mapping['orphanRemoval'] = $manyToManyAnnotation->orphanRemoval;
				$mapping['fetch'] = $this->getFetchMode($className, $manyToManyAnnotation->fetch);

				if ($orderByAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OrderBy')) {
					$mapping['orderBy'] = $orderByAnnotation->value;
				}

				$metadata->mapManyToMany($mapping);
			} else {
				$mapping['nullable'] = FALSE;

				if ($columnAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Column')) {
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
							if (strpos($propertyMetaData['type'], '\\') !== FALSE) {
								if ($this->reflectionService->isClassAnnotatedWith($propertyMetaData['type'], 'TYPO3\Flow\Annotations\ValueObject')) {
									$mapping['type'] = 'object';
								} elseif (class_exists($propertyMetaData['type'])) {

									throw \Doctrine\ORM\Mapping\MappingException::missingRequiredOption($property->getName(), 'OneToOne', sprintf('The property "%s" in class "%s" has a non standard data type and doesn\'t define the type of the relation. You have to use one of these annotations: @OneToOne, @OneToMany, @ManyToOne, @ManyToMany', $property->getName(), $className));
								}
							} else {
								throw \Doctrine\ORM\Mapping\MappingException::propertyTypeIsRequired($className, $property->getName());
							}
					}

				}

				if ($this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Id') !== NULL) {
					$mapping['id'] = TRUE;
				}

				if ($generatedValueAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\GeneratedValue')) {
					$metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_' . strtoupper($generatedValueAnnotation->strategy)));
				}

				if ($this->reflectionService->isPropertyAnnotatedWith($className, $property->getName(), 'Doctrine\ORM\Mapping\Version')) {
					$metadata->setVersionMapping($mapping);
				}

				$metadata->mapField($mapping);

					// Check for SequenceGenerator/TableGenerator definition
				if ($seqGeneratorAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\SequenceGenerator')) {
					$metadata->setSequenceGeneratorDefinition(array(
						'sequenceName' => $seqGeneratorAnnotation->sequenceName,
						'allocationSize' => $seqGeneratorAnnotation->allocationSize,
						'initialValue' => $seqGeneratorAnnotation->initialValue
					));
				} elseif ($this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\TableGenerator') !== NULL) {
					throw \Doctrine\ORM\Mapping\MappingException::tableIdGeneratorNotImplemented($className);
				} elseif ($customGeneratorAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\CustomIdGenerator')) {
					$metadata->setCustomGeneratorDefinition(array(
						'class' => $customGeneratorAnnotation->class
					));
				}
			}

		}
	}

	/**
	 * Evaluate JoinTable annotations and fill missing bits as needed.
	 *
	 * @param \Doctrine\ORM\Mapping\JoinTable $joinTableAnnotation
	 * @param \ReflectionProperty $property
	 * @param string $className
	 * @param array $mapping
	 * @return array
	 */
	protected function evaluateJoinTableAnnotation(\Doctrine\ORM\Mapping\JoinTable $joinTableAnnotation, \ReflectionProperty $property, $className, array $mapping) {
		$joinTable = array(
			'name' => $joinTableAnnotation->name,
			'schema' => $joinTableAnnotation->schema
		);
		if ($joinTable['name'] === NULL) {
			$joinTable['name'] = $this->inferJoinTableNameFromClassAndPropertyName($className, $property->getName());
		}

		foreach ($joinTableAnnotation->joinColumns as $joinColumn) {
			$joinTable['joinColumns'][] = $this->joinColumnToArray($joinColumn);
		}
		if (array_key_exists('joinColumns', $joinTable)) {
			$joinTable['joinColumns'] = $this->buildJoinColumnsIfNeeded($joinTable['joinColumns'], $mapping, $property, self::MAPPING_MM_REGULAR);
		} else {
			$joinColumns = array(
				array(
					'name' => NULL,
					'referencedColumnName' => NULL,
				)
			);
			$joinTable['joinColumns'] = $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property, self::MAPPING_MM_REGULAR);
		}

		foreach ($joinTableAnnotation->inverseJoinColumns as $joinColumn) {
			$joinTable['inverseJoinColumns'][] = $this->joinColumnToArray($joinColumn);
		}
		if (array_key_exists('inverseJoinColumns', $joinTable)) {
			$joinTable['inverseJoinColumns'] = $this->buildJoinColumnsIfNeeded($joinTable['inverseJoinColumns'], $mapping, $property);
		} else {
			$joinColumns = array(
				array(
					'name' => NULL,
					'referencedColumnName' => NULL,
				)
			);
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
	protected function evaluateJoinColumnAnnotations(\ReflectionProperty $property) {
		$joinColumns = array();

		if ($joinColumnAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumn')) {
			$joinColumns[] = $this->joinColumnToArray($joinColumnAnnotation, strtolower($property->getName()));
		} elseif ($joinColumnsAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumns')) {
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
	 * @param \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata
	 * @return void
	 */
	protected function evaluateOverridesAnnotations(array $classAnnotations, \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata) {
		if (isset($classAnnotations['Doctrine\ORM\Mapping\AssociationOverrides'])) {
			$associationOverridesAnnotation = $classAnnotations['Doctrine\ORM\Mapping\AssociationOverrides'];

			foreach ($associationOverridesAnnotation->value as $associationOverride) {
				$override = array();
				$fieldName = $associationOverride->name;

					// Check for JoinColummn/JoinColumns annotations
				if ($associationOverride->joinColumns) {
					$joinColumns = array();
					foreach ($associationOverride->joinColumns as $joinColumn) {
						$joinColumns[] = $this->joinColumnToArray($joinColumn);
					}
					$override['joinColumns'] = $joinColumns;
				}

					// Check for JoinTable annotations
				if ($associationOverride->joinTable) {
					$joinTable = NULL;
					$joinTableAnnotation = $associationOverride->joinTable;
					$joinTable = array(
						'name' => $joinTableAnnotation->name,
						'schema' => $joinTableAnnotation->schema
					);

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
		if (isset($classAnnotations['Doctrine\ORM\Mapping\AttributeOverrides'])) {
			$attributeOverridesAnnotation = $classAnnotations['Doctrine\ORM\Mapping\AttributeOverrides'];
			foreach ($attributeOverridesAnnotation->value as $attributeOverrideAnnotation) {
				$attributeOverride = $this->addColumnToMappingArray($attributeOverrideAnnotation->column, array(), $attributeOverrideAnnotation->name);
				$metadata->setAttributeOverride($attributeOverrideAnnotation->name, $attributeOverride);
			}
		}
	}

	/**
	 * Evaluate the lifecycle annotations and amend the metadata accordingly.
	 *
	 * @param \ReflectionClass $class
	 * @param \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata
	 * @return void
	 */
	protected function evaluateLifeCycleAnnotations(\ReflectionClass $class, \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata) {
		foreach ($class->getMethods() as $method) {
			if ($method->isPublic()) {
				$annotations = $this->reader->getMethodAnnotations($method);

				if (isset($annotations['Doctrine\ORM\Mapping\PrePersist'])) {
					$metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::prePersist);
				}

				if (isset($annotations['Doctrine\ORM\Mapping\PostPersist'])) {
					$metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::postPersist);
				}

				if (isset($annotations['Doctrine\ORM\Mapping\PreUpdate'])) {
					$metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::preUpdate);
				}

				if (isset($annotations['Doctrine\ORM\Mapping\PostUpdate'])) {
					$metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::postUpdate);
				}

				if (isset($annotations['Doctrine\ORM\Mapping\PreRemove'])) {
					$metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::preRemove);
				}

				if (isset($annotations['Doctrine\ORM\Mapping\PostRemove'])) {
					$metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::postRemove);
				}

				if (isset($annotations['Doctrine\ORM\Mapping\PostLoad'])) {
					$metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::postLoad);
				}

				if (isset($annotations['Doctrine\ORM\Mapping\PreFlush'])) {
					$metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::preFlush);
				}
			}
		}

			// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
		$metadata->addLifecycleCallback('Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies', \Doctrine\ORM\Events::postLoad);
			// FIXME this can be removed again once Doctrine is fixed (see fixInjectedPropertiesForDoctrineProxiesCode())
		$metadata->addLifecycleCallback('Flow_Aop_Proxy_fixInjectedPropertiesForDoctrineProxies', \Doctrine\ORM\Events::postLoad);
	}

	/**
	 * Derive maximum identifier length from doctrine DBAL
	 *
	 * @return integer
	 */
	protected function getMaxIdentifierLength() {
		if ($this->tableNameLengthLimit === NULL) {
			$this->tableNameLengthLimit = $this->entityManager->getConnection()->getDatabasePlatform()->getMaxIdentifierLength();
		}
		return $this->tableNameLengthLimit;
	}

	/**
	 * Whether the class with the specified name should have its metadata loaded.
	 * This is only the case if it is either mapped as an Entity or a
	 * MappedSuperclass (i.e. is not transient).
	 *
	 * @param string $className
	 * @return boolean
	 */
	public function isTransient($className) {
		return strpos($className, \TYPO3\Flow\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX) !== FALSE ||
			(
				!$this->reflectionService->isClassAnnotatedWith($className, 'TYPO3\Flow\Annotations\Entity') &&
					!$this->reflectionService->isClassAnnotatedWith($className, 'TYPO3\Flow\Annotations\ValueObject') &&
					!$this->reflectionService->isClassAnnotatedWith($className, 'Doctrine\ORM\Mapping\Entity') &&
					!$this->reflectionService->isClassAnnotatedWith($className, 'Doctrine\ORM\Mapping\MappedSuperclass')
			);
	}

	/**
	 * Returns the names of all mapped (non-transient) classes known to this driver.
	 *
	 * @return array
	 */
	public function getAllClassNames() {
		if (is_array($this->classNames)) {
			return $this->classNames;
		}

		$this->classNames = array_merge(
			$this->reflectionService->getClassNamesByAnnotation('TYPO3\Flow\Annotations\ValueObject'),
			$this->reflectionService->getClassNamesByAnnotation('TYPO3\Flow\Annotations\Entity'),
			$this->reflectionService->getClassNamesByAnnotation('Doctrine\ORM\Mapping\Entity'),
			$this->reflectionService->getClassNamesByAnnotation('Doctrine\ORM\Mapping\MappedSuperclass')
		);
		$this->classNames = array_filter($this->classNames,
			function ($className) {
				return !interface_exists($className, FALSE)
						&& strpos($className, \TYPO3\Flow\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX) === FALSE;
			}
		);

		return $this->classNames;
	}

	/**
	 * Parse the given JoinColumn into an array
	 *
	 * @param \Doctrine\ORM\Mapping\JoinColumn $joinColumnAnnotation
	 * @param string $propertyName
	 * @return array
	 */
	protected function joinColumnToArray(\Doctrine\ORM\Mapping\JoinColumn $joinColumnAnnotation, $propertyName = NULL) {
		return array(
			'name' => $joinColumnAnnotation->name === NULL ? $propertyName : $joinColumnAnnotation->name,
			'unique' => $joinColumnAnnotation->unique,
			'nullable' => $joinColumnAnnotation->nullable,
			'onDelete' => $joinColumnAnnotation->onDelete,
			'columnDefinition' => $joinColumnAnnotation->columnDefinition,
			'referencedColumnName' => $joinColumnAnnotation->referencedColumnName,
		);
	}

	/**
	 * Parse the given Column into an array
	 *
	 * @param \Doctrine\ORM\Mapping\Column $columnAnnotation
	 * @param array $mapping
	 * @param string $fieldName
	 * @return array
	 */
	protected function addColumnToMappingArray(\Doctrine\ORM\Mapping\Column $columnAnnotation, $mapping = array(), $fieldName = NULL) {
		if ($fieldName !== NULL) {
			$mapping['fieldName'] = $fieldName;
		}

		$mapping['type'] = ($columnAnnotation->type === 'string') ? NULL : $columnAnnotation->type;
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
	 * Attempts to resolve the fetch mode.
	 *
	 * @param string $className The class name
	 * @param string $fetchMode The fetch mode
	 * @return integer The fetch mode as defined in ClassMetadata
	 * @throws \Doctrine\ORM\Mapping\MappingException If the fetch mode is not valid
	 */
	private function getFetchMode($className, $fetchMode) {
		$fetchMode = strtoupper($fetchMode);
		if (!defined('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $fetchMode)) {
			throw \Doctrine\ORM\Mapping\MappingException::invalidFetchMode($className, $fetchMode);
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
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		$class = new \ReflectionClass($className);
		foreach ($class->getProperties() as $property) {
			if ($this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Id') !== NULL) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
	 *
	 * @return boolean TRUE if this filter has runtime evaluations
	 */
	public function hasRuntimeEvaluationsDefinition() {
		return FALSE;
	}

	/**
	 * Returns runtime evaluations for a previously matched pointcut
	 *
	 * @return array Runtime evaluations
	 */
	public function getRuntimeEvaluationsDefinition() {
		return array();
	}

	/**
	 * This method is used to optimize the matching process.
	 *
	 * @param \TYPO3\Flow\Aop\Builder\ClassNameIndex $classNameIndex
	 * @return \TYPO3\Flow\Aop\Builder\ClassNameIndex
	 */
	public function reduceTargetClassNames(\TYPO3\Flow\Aop\Builder\ClassNameIndex $classNameIndex) {
		return $classNameIndex;
	}
}

?>