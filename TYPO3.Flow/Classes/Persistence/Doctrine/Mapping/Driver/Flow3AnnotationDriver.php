<?php
namespace TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

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
 * @FLOW3\Scope("singleton")
 */
class Flow3AnnotationDriver implements \Doctrine\ORM\Mapping\Driver\Driver, \TYPO3\FLOW3\Aop\Pointcut\PointcutFilterInterface {

	const MAPPING_REGULAR = 0;
	const MAPPING_INVERSE = 1;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
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
	 * @param \TYPO3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\FLOW3\Reflection\ReflectionService $reflectionService) {
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
	 * @return \TYPO3\FLOW3\Reflection\ClassSchema
	 * @throws \RuntimeException
	 */
	protected function getClassSchema($className) {
		$className = preg_replace('/' . \TYPO3\FLOW3\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX . '$/', '', $className);

		$classSchema = $this->reflectionService->getClassSchema($className);
		if (!$classSchema) {
			throw new \RuntimeException('No class schema found for "' . $className . '". The class should probably marked as entity or value object!', 1295973082);
		}
		return $classSchema;
	}

	/**
	 * Loads the metadata for the specified class into the provided container.
	 *
	 * @param string $className
	 * @param \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata
	 * @return void
	 * @throws \Doctrine\ORM\Mapping\MappingException
	 * @throws \UnexpectedValueException
	 * @todo adjust when Doctrine 2 supports value objects, see http://www.doctrine-project.org/jira/browse/DDC-93
	 */
	public function loadMetadataForClass($className, \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata) {
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
		} elseif (isset($classAnnotations['TYPO3\FLOW3\Annotations\Entity']) || isset($classAnnotations['Doctrine\ORM\Mapping\Entity'])) {
			$entityAnnotation = isset($classAnnotations['TYPO3\FLOW3\Annotations\Entity']) ? $classAnnotations['TYPO3\FLOW3\Annotations\Entity'] : $classAnnotations['Doctrine\ORM\Mapping\Entity'];
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
		} elseif ($classSchema->getModelType() === \TYPO3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT) {
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
		}

		if (!isset($primaryTable['name'])) {
			$className = $classSchema->getClassName();
			$primaryTable['name'] = $this->inferTableNameFromClassName($className);
#			$idProperties = array_keys($classSchema->getIdentityProperties());
#			$primaryTable['uniqueConstraints']['flow3_identifier'] = array(
#				'columns' => $idProperties
#			);
		}
		$metadata->setPrimaryTable($primaryTable);

			// Evaluate NamedQueries annotation
		if (isset($classAnnotations['Doctrine\ORM\Mapping\NamedQueries'])) {
			$namedQueriesAnnotation = $classAnnotations['Doctrine\ORM\Mapping\NamedQueries'];

			if (!is_array($namedQueriesAnnotation->value)) {
				throw new \UnexpectedValueException("@NamedQueries should contain an array of @NamedQuery annotations.");
			}

			foreach ($namedQueriesAnnotation->value as $namedQuery) {
				if (!($namedQuery instanceof \Doctrine\ORM\Mapping\NamedQuery)) {
					throw new \UnexpectedValueException("@NamedQueries should contain an array of @NamedQuery annotations.");
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
			$metadata->setInheritanceType(constant('Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_' . strtoupper($inheritanceTypeAnnotation->value)));

			if ($metadata->inheritanceType !== \Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_NONE) {
					// Evaluate DiscriminatorColumn annotation
				if (isset($classAnnotations['Doctrine\ORM\Mapping\DiscriminatorColumn'])) {
					$discriminatorColumnAnnotation = $classAnnotations['Doctrine\ORM\Mapping\DiscriminatorColumn'];
					$metadata->setDiscriminatorColumn(array(
						'name' => $discriminatorColumnAnnotation->name,
						'type' => $discriminatorColumnAnnotation->type,
						'length' => $discriminatorColumnAnnotation->length
					));
				} else {
					$metadata->setDiscriminatorColumn(array('name' => 'dtype', 'type' => 'string', 'length' => 255));
				}

					// Evaluate DiscriminatorMap annotation
				if (isset($classAnnotations['Doctrine\ORM\Mapping\DiscriminatorMap'])) {
					$discriminatorMapAnnotation = $classAnnotations['Doctrine\ORM\Mapping\DiscriminatorMap'];
					$metadata->setDiscriminatorMap($discriminatorMapAnnotation->value);
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
					$metadata->setDiscriminatorMap($discriminatorMap);
				}
			}
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

			// Evaluate @HasLifecycleCallbacks annotation
		$this->evaluateLifeCycleAnnotations($classAnnotations, $class, $metadata);
	}

	/**
	 * Given a class name a table name is returned. That name should be reasonably unique.
	 *
	 * @param string $className
	 * @param integer $lengthLimit
	 * @return string
	 */
	public function inferTableNameFromClassName($className, $lengthLimit = NULL) {
		if ($lengthLimit === NULL) {
			$lengthLimit = $this->getMaxIdentifierLength();
		}
		$tableName = str_replace('\\', '_', $className);
		if (strlen($tableName) > $lengthLimit) {
			$tableName = substr($tableName, 0, $lengthLimit - 6) . '_' . substr(sha1($className), 0, 5);
		}
		return strtolower($tableName);
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

		if (strlen($tableName) > $this->getMaxIdentifierLength()) {
			$tableName = substr($tableName, 0, $this->getMaxIdentifierLength() - 6) . '_' . substr(sha1($className), 0, 5);
		}

		return $tableName;
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
					$className = preg_replace('/' . \TYPO3\FLOW3\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX . '$/', '', $property->getDeclaringClass()->getName());
					$idProperties = $this->reflectionService->getPropertyNamesByTag($className, 'id');
					$joinColumnName = $this->buildJoinTableColumnName($className);
				}
				if (count($idProperties) === 0) {
					$joinColumn['name'] = $joinColumn['name'] === NULL ? $joinColumnName : $joinColumn['name'];
					$joinColumn['referencedColumnName'] = strtolower('FLOW3_Persistence_Identifier');
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
				} elseif ($this->getClassSchema($mapping['targetEntity'])->isAggregateRoot() === FALSE) {
					$mapping['cascade'] = array('all');
				}
				if ($oneToOneAnnotation->orphanRemoval) {
					$mapping['orphanRemoval'] = $oneToOneAnnotation->orphanRemoval;
				} elseif ($this->getClassSchema($mapping['targetEntity'])->isAggregateRoot() === FALSE) {
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
				} elseif ($this->getClassSchema($mapping['targetEntity'])->isAggregateRoot() === FALSE) {
					$mapping['cascade'] = array('all');
				}
				if ($oneToManyAnnotation->orphanRemoval) {
					$mapping['orphanRemoval'] = $oneToManyAnnotation->orphanRemoval;
				} elseif ($this->getClassSchema($mapping['targetEntity'])->isAggregateRoot() === FALSE) {
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
				} elseif ($this->getClassSchema($mapping['targetEntity'])->isAggregateRoot() === FALSE) {
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
						'joinColumns' => $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property, self::MAPPING_INVERSE),
						'inverseJoinColumns' => $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property)
					);
				}

				$mapping['joinTable'] = $joinTable;
				$mapping['mappedBy'] = $manyToManyAnnotation->mappedBy;
				$mapping['inversedBy'] = $manyToManyAnnotation->inversedBy;
				if ($manyToManyAnnotation->cascade) {
					$mapping['cascade'] = $manyToManyAnnotation->cascade;
				} elseif ($this->getClassSchema($mapping['targetEntity'])->isAggregateRoot() === FALSE) {
					$mapping['cascade'] = array('all');
				}
				if ($manyToManyAnnotation->orphanRemoval) {
					$mapping['orphanRemoval'] = $manyToManyAnnotation->orphanRemoval;
				} elseif ($this->getClassSchema($mapping['targetEntity'])->isAggregateRoot() === FALSE) {
					$mapping['orphanRemoval'] = TRUE;
				}
				$mapping['fetch'] = $this->getFetchMode($className, $manyToManyAnnotation->fetch);

				if ($orderByAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OrderBy')) {
					$mapping['orderBy'] = $orderByAnnotation->value;
				}

				$metadata->mapManyToMany($mapping);
			} else {
				$mapping['nullable'] = FALSE;

				if ($columnAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Column')) {
					$mapping['type'] = ($columnAnnotation->type === 'string') ? NULL : $columnAnnotation->type;
					$mapping['length'] = $columnAnnotation->length;
					$mapping['precision'] = $columnAnnotation->precision;
					$mapping['scale'] = $columnAnnotation->scale;
					$mapping['nullable'] = $columnAnnotation->nullable;
					$mapping['unique'] = $columnAnnotation->unique;
					if ($columnAnnotation->options) {
						$mapping['options'] = $columnAnnotation->options;
					}

					if (isset($columnAnnotation->name)) {
						$mapping['columnName'] = $columnAnnotation->name;
					}

					if (isset($columnAnnotation->columnDefinition)) {
						$mapping['columnDefinition'] = $columnAnnotation->columnDefinition;
					}
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
								if ($this->reflectionService->isClassAnnotatedWith($propertyMetaData['type'], 'TYPO3\FLOW3\Annotations\ValueObject')) {
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
			$joinTable['joinColumns'][] = array(
				'name' => $joinColumn->name,
				'referencedColumnName' => $joinColumn->referencedColumnName,
				'unique' => $joinColumn->unique,
				'nullable' => $joinColumn->nullable,
				'onDelete' => $joinColumn->onDelete,
				'columnDefinition' => $joinColumn->columnDefinition,
			);
		}
		if (array_key_exists('joinColumns', $joinTable)) {
			$joinTable['joinColumns'] = $this->buildJoinColumnsIfNeeded($joinTable['joinColumns'], $mapping, $property);
		} else {
			$joinColumns = array(
				array(
					'name' => NULL,
					'referencedColumnName' => NULL,
				)
			);
			$joinTable['joinColumns'] = $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property);
		}

		foreach ($joinTableAnnotation->inverseJoinColumns as $joinColumn) {
			$joinTable['inverseJoinColumns'][] = array(
				'name' => $joinColumn->name,
				'referencedColumnName' => $joinColumn->referencedColumnName,
				'unique' => $joinColumn->unique,
				'nullable' => $joinColumn->nullable,
				'onDelete' => $joinColumn->onDelete,
				'columnDefinition' => $joinColumn->columnDefinition,
			);
		}
		if (array_key_exists('inverseJoinColumns', $joinTable)) {
			$joinTable['inverseJoinColumns'] = $this->buildJoinColumnsIfNeeded($joinTable['inverseJoinColumns'], $mapping, $property, self::MAPPING_INVERSE);
		} else {
			$joinColumns = array(
				array(
					'name' => NULL,
					'referencedColumnName' => NULL,
				)
			);
			$joinTable['inverseJoinColumns'] = $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property, self::MAPPING_INVERSE);
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
			$joinColumns[] = array(
				'name' => $joinColumnAnnotation->name === NULL ? strtolower($property->getName()) : $joinColumnAnnotation->name,
				'referencedColumnName' => $joinColumnAnnotation->referencedColumnName,
				'unique' => $joinColumnAnnotation->unique,
				'nullable' => $joinColumnAnnotation->nullable,
				'onDelete' => $joinColumnAnnotation->onDelete,
				'columnDefinition' => $joinColumnAnnotation->columnDefinition,
			);
		} else if ($joinColumnsAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumns')) {
			foreach ($joinColumnsAnnotation->value as $joinColumnAnnotation) {
				$joinColumns[] = array(
					'name' => $joinColumnAnnotation->name === NULL ? strtolower($property->getName()) : $joinColumnAnnotation->name,
					'referencedColumnName' => $joinColumnAnnotation->referencedColumnName,
					'unique' => $joinColumnAnnotation->unique,
					'nullable' => $joinColumnAnnotation->nullable,
					'onDelete' => $joinColumnAnnotation->onDelete,
					'columnDefinition' => $joinColumnAnnotation->columnDefinition,
				);
			}
		}

		return $joinColumns;
	}

	/**
	 * Evaluate the lifecycle annotations and amend the metadata accordingly.
	 *
	 * @param array $classAnnotations
	 * @param \ReflectionClass $class
	 * @param \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata
	 * @return void
	 */
	protected function evaluateLifeCycleAnnotations(array $classAnnotations, \ReflectionClass $class, \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata) {
		if (isset($classAnnotations['Doctrine\ORM\Mapping\HasLifecycleCallbacks'])) {
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
		}

			// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
		$metadata->addLifecycleCallback('FLOW3_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies', \Doctrine\ORM\Events::postLoad);
			// FIXME this can be removed again once Doctrine is fixed (see fixInjectedPropertiesForDoctrineProxiesCode())
		$metadata->addLifecycleCallback('FLOW3_Aop_Proxy_fixInjectedPropertiesForDoctrineProxies', \Doctrine\ORM\Events::postLoad);
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
		return strpos($className, \TYPO3\FLOW3\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX) !== FALSE ||
			(
				!$this->reflectionService->isClassAnnotatedWith($className, 'TYPO3\FLOW3\Annotations\Entity') &&
					!$this->reflectionService->isClassAnnotatedWith($className, 'TYPO3\FLOW3\Annotations\ValueObject') &&
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
			$this->reflectionService->getClassNamesByAnnotation('TYPO3\FLOW3\Annotations\ValueObject'),
			$this->reflectionService->getClassNamesByAnnotation('TYPO3\FLOW3\Annotations\Entity'),
			$this->reflectionService->getClassNamesByAnnotation('Doctrine\ORM\Mapping\Entity'),
			$this->reflectionService->getClassNamesByAnnotation('Doctrine\ORM\Mapping\MappedSuperclass')
		);
		$this->classNames = array_filter($this->classNames,
			function ($className) {
				return !interface_exists($className, FALSE)
						&& strpos($className, \TYPO3\FLOW3\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX) === FALSE;
			}
		);

		return $this->classNames;
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
	 * @param \TYPO3\FLOW3\Aop\Builder\ClassNameIndex $classNameIndex
	 * @return \TYPO3\FLOW3\Aop\Builder\ClassNameIndex
	 */
	public function reduceTargetClassNames(\TYPO3\FLOW3\Aop\Builder\ClassNameIndex $classNameIndex) {
		return $classNameIndex;
	}
}

?>