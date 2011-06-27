<?php
namespace TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver;

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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class Flow3AnnotationDriver implements \Doctrine\ORM\Mapping\Driver\Driver, \TYPO3\FLOW3\AOP\Pointcut\PointcutFilterInterface {

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
	 * @var array
	 */
	protected $classNames;

	/**
	 * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
	 * docblock annotations.
	 */
	public function __construct() {
		$this->reader = new \Doctrine\Common\Annotations\AnnotationReader();
		$this->reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
	}

	/**
	 * @param \TYPO3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
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
			throw new \RuntimeException('No class schema found for "' . $className . '"', 1295973082);
		}
		return $classSchema;
	}

	/**
	 * Loads the metadata for the specified class into the provided container.
	 *
	 * @param string $className
	 * @param \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata
	 * @todo adjust when Doctrine 2 supports value objects
	 * @return void
	 */
	public function loadMetadataForClass($className, \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata) {
		$class = $metadata->getReflectionClass();
		$classSchema = $this->getClassSchema($class->getName());
		$classAnnotations = $this->reader->getClassAnnotations($class);

			// Evaluate Entity annotation
		if (isset($classAnnotations['Doctrine\ORM\Mapping\MappedSuperclass'])) {
			$metadata->isMappedSuperclass = TRUE;
		} elseif (isset($classAnnotations['Doctrine\ORM\Mapping\Entity'])) {
			$entityAnnotation = $classAnnotations['Doctrine\ORM\Mapping\Entity'];
			if ($entityAnnotation->repositoryClass) {
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
					$primaryTable['indexes'][$indexAnnotation->name] = array(
						'columns' => $indexAnnotation->columns
					);
				}
			}

			if ($tableAnnotation->uniqueConstraints !== NULL) {
				foreach ($tableAnnotation->uniqueConstraints as $uniqueConstraint) {
					$primaryTable['uniqueConstraints'][$uniqueConstraint->name] = array(
						'columns' => $uniqueConstraint->columns
					);
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

			// Evaluate InheritanceType annotation
		if (isset($classAnnotations['Doctrine\ORM\Mapping\InheritanceType'])) {
			$inheritanceTypeAnnotation = $classAnnotations['Doctrine\ORM\Mapping\InheritanceType'];
			$metadata->setInheritanceType(constant('Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_' . $inheritanceTypeAnnotation->value));

			if ($metadata->inheritanceType != \Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_NONE) {
					// Evaluate DiscriminatorColumn annotation
				if (isset($classAnnotations['Doctrine\ORM\Mapping\DiscriminatorColumn'])) {
					$discrColumnAnnotation = $classAnnotations['Doctrine\ORM\Mapping\DiscriminatorColumn'];
					$metadata->setDiscriminatorColumn(array(
						'name' => $discrColumnAnnotation->name,
						'type' => $discrColumnAnnotation->type,
						'length' => $discrColumnAnnotation->length
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
			$metadata->setChangeTrackingPolicy(constant('Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_' . $changeTrackingAnnotation->value));
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
	 * @return string
	 */
	protected function inferTableNameFromClassName($className) {
		$classNameParts = explode('\\', $className);
		$packageKey = $classNameParts[1];
		$modelName = array_pop($classNameParts);
		$modelNamePrefix = array_pop($classNameParts);
		return strtolower($packageKey . '_' . ($modelNamePrefix === 'Model' ? '' : $modelNamePrefix . '_') . $modelName);
	}

	/**
	 * Given a class and property name a table name is returned. That name should be reasonably unique.
	 *
	 * @param string $className
	 * @param string $propertyName
	 * @return string
	 */
	protected function inferJoinTableNameFromClassAndPropertyName($className, $propertyName) {
		$classNameParts = explode('\\', $className);
		$packageKey = $classNameParts[1];
		$modelName = array_pop($classNameParts);
		$modelNamePrefix = array_pop($classNameParts);
		return strtolower($packageKey . '_' . ($modelNamePrefix === 'Model' ? '' : $modelNamePrefix . '_') . $modelName . '_' . $propertyName . '_join');
	}

	/**
	 * Build a name for a column in a jointable.
	 *
	 * @param string $className
	 * @return string
	 */
	protected function buildJoinTableColumnName($className) {
		$classNameParts = explode('\\', $className);
		$packageKey = $classNameParts[1];
		$modelName = array_pop($classNameParts);
		$modelNamePrefix = array_pop($classNameParts);
		return strtolower($packageKey . '_' . ($modelNamePrefix === 'Model' ? '' : $modelNamePrefix . '_') . $modelName);
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
	 * @todo make this do some "real" autodetection
	 */
	protected function buildJoinColumnsIfNeeded(array $joinColumns, array $mapping, \ReflectionProperty $property, $direction = self::MAPPING_REGULAR) {
		if ($joinColumns === array()) {
			$joinColumns[] = array(
				'name' => NULL,
				'referencedColumnName' => NULL,
				'unique' => FALSE,
				'nullable' => TRUE,
				'onDelete' => NULL,
				'onUpdate' => NULL,
				'columnDefinition' => NULL,
			);
		}
		foreach ($joinColumns as &$joinColumn) {
			if ($joinColumn['referencedColumnName'] === NULL || $joinColumn['referencedColumnName'] === 'id') {
				if ($direction === self::MAPPING_REGULAR) {
					$idProperties = $this->reflectionService->getPropertyNamesByTag($mapping['targetEntity'], 'Id');
					$joinColumnName = $this->buildJoinTableColumnName($mapping['targetEntity']);
				} else {
					$className = preg_replace('/' . \TYPO3\FLOW3\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX . '$/', '', $property->getDeclaringClass()->getName());
					$idProperties = $this->reflectionService->getPropertyNamesByTag($className, 'Id');
					$joinColumnName = $this->buildJoinTableColumnName($className);
				}
				if (count($idProperties) === 0) {
					$joinColumn['name'] = $joinColumn['name'] === NULL ? $joinColumnName : $joinColumn['name'];
					$joinColumn['referencedColumnName'] = strtolower('FLOW3_Persistence_Identifier');
				} elseif(count($idProperties) === 1) {
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

				// Check for JoinColummn/JoinColumns annotations
			$joinColumns = array();
			if ($joinColumnAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumn')) {
				$joinColumns[] = array(
					'name' => $joinColumnAnnotation->name,
					'referencedColumnName' => $joinColumnAnnotation->referencedColumnName,
					'unique' => $joinColumnAnnotation->unique,
					'nullable' => $joinColumnAnnotation->nullable,
					'onDelete' => $joinColumnAnnotation->onDelete,
					'onUpdate' => $joinColumnAnnotation->onUpdate,
					'columnDefinition' => $joinColumnAnnotation->columnDefinition,
				);
			} else if ($joinColumnsAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumns')) {
				foreach ($joinColumnsAnnotation->value as $joinColumn) {
					$joinColumns[] = array(
						'name' => $joinColumn->name,
						'referencedColumnName' => $joinColumn->referencedColumnName,
						'unique' => $joinColumn->unique,
						'nullable' => $joinColumn->nullable,
						'onDelete' => $joinColumn->onDelete,
						'onUpdate' => $joinColumn->onUpdate,
						'columnDefinition' => $joinColumn->columnDefinition,
					);
				}
			}

				// Field can only be annotated with one of:
				// @OneToOne, @OneToMany, @ManyToOne, @ManyToMany, @Column (optional)
			if ($oneToOneAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OneToOne')) {
				if ($oneToOneAnnotation->targetEntity) {
					$mapping['targetEntity'] = $oneToOneAnnotation->targetEntity;
				}
				$mapping['joinColumns'] = $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property);
				$mapping['mappedBy'] = $oneToOneAnnotation->mappedBy;
				$mapping['inversedBy'] = $oneToOneAnnotation->inversedBy;
				$mapping['cascade'] = $oneToOneAnnotation->cascade;
				$mapping['orphanRemoval'] = $oneToOneAnnotation->orphanRemoval;
				$mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $oneToOneAnnotation->fetch);
				$metadata->mapOneToOne($mapping);
			} elseif ($oneToManyAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OneToMany')) {
				$mapping['mappedBy'] = $oneToManyAnnotation->mappedBy;
				if ($oneToManyAnnotation->targetEntity) {
					$mapping['targetEntity'] = $oneToManyAnnotation->targetEntity;
				} elseif (isset($propertyMetaData['elementType'])) {
					$mapping['targetEntity'] = $propertyMetaData['elementType'];
				}
				$mapping['cascade'] = $oneToManyAnnotation->cascade;
				$mapping['orphanRemoval'] = $oneToManyAnnotation->orphanRemoval;
				$mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $oneToManyAnnotation->fetch);

				if ($orderByAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OrderBy')) {
					$mapping['orderBy'] = $orderByAnnotation->value;
				}

				$metadata->mapOneToMany($mapping);
			} elseif ($manyToOneAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToOne')) {
				if ($manyToOneAnnotation->targetEntity) {
					$mapping['targetEntity'] = $manyToOneAnnotation->targetEntity;
				}
				$mapping['joinColumns'] = $this->buildJoinColumnsIfNeeded($joinColumns, $mapping, $property);
				$mapping['cascade'] = $manyToOneAnnotation->cascade;
				$mapping['inversedBy'] = $manyToOneAnnotation->inversedBy;
				$mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $manyToOneAnnotation->fetch);
				$metadata->mapManyToOne($mapping);
			} elseif ($manyToManyAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToMany')) {
				if ($manyToManyAnnotation->targetEntity) {
					$mapping['targetEntity'] = $manyToManyAnnotation->targetEntity;
				} elseif (isset($propertyMetaData['elementType'])) {
					$mapping['targetEntity'] = $propertyMetaData['elementType'];
				}
				if ($joinTableAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinTable')) {
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
							'onUpdate' => $joinColumn->onUpdate,
							'columnDefinition' => $joinColumn->columnDefinition,
						);
					}
					if (array_key_exists('joinColumns', $joinTable)) {
						$joinTable['joinColumns'] = $this->buildJoinColumnsIfNeeded($joinTable['joinColumns'], $mapping, $property);
					} else {
						$joinTable['joinColumns'] = $this->buildJoinColumnsIfNeeded(array(), $mapping, $property);
					}

					foreach ($joinTableAnnotation->inverseJoinColumns as $joinColumn) {
						$joinTable['inverseJoinColumns'][] = array(
							'name' => $joinColumn->name,
							'referencedColumnName' => $joinColumn->referencedColumnName,
							'unique' => $joinColumn->unique,
							'nullable' => $joinColumn->nullable,
							'onDelete' => $joinColumn->onDelete,
							'onUpdate' => $joinColumn->onUpdate,
							'columnDefinition' => $joinColumn->columnDefinition,
						);
					}
					if (array_key_exists('inverseJoinColumns', $joinTable)) {
						$joinTable['inverseJoinColumns'] = $this->buildJoinColumnsIfNeeded($joinTable['inverseJoinColumns'], $mapping, $property, self::MAPPING_INVERSE);
					} else {
						$joinTable['inverseJoinColumns'] = $this->buildJoinColumnsIfNeeded(array(), $mapping, $property, self::MAPPING_INVERSE);
					}
				} else {
					$joinTable = array(
						'name' => $this->inferJoinTableNameFromClassAndPropertyName($className, $property->getName()),
						'joinColumns' => $this->buildJoinColumnsIfNeeded(array(), $mapping, $property, self::MAPPING_INVERSE),
						'inverseJoinColumns' => $this->buildJoinColumnsIfNeeded(array(), $mapping, $property)
					);
				}

				$mapping['joinTable'] = $joinTable;
				$mapping['mappedBy'] = $manyToManyAnnotation->mappedBy;
				$mapping['inversedBy'] = $manyToManyAnnotation->inversedBy;
				$mapping['cascade'] = $manyToManyAnnotation->cascade;
				$mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $manyToManyAnnotation->fetch);

				if ($orderByAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OrderBy')) {
					$mapping['orderBy'] = $orderByAnnotation->value;
				}

				$metadata->mapManyToMany($mapping);
			} else {
				$mapping['nullable'] = TRUE;

				if ($columnAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Column')) {
					$mapping['type'] = $columnAnnotation->type;
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
								if ($this->reflectionService->isClassTaggedWith($propertyMetaData['type'], 'valueobject')) {
									$mapping['type'] = 'object';
								}
							} else {
								\Doctrine\ORM\Mapping\MappingException::propertyTypeIsRequired($className, $property->getName());
							}
					}

				}

				if ($this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Id') !== NULL) {
					$mapping['id'] = TRUE;
				}

				if ($generatedValueAnnotation = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\GeneratedValue')) {
					$metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_' . $generatedValueAnnotation->strategy));
				}

				if ($this->reflectionService->isPropertyTaggedWith($className, $property->getName(), 'version')
						|| $this->reflectionService->isPropertyTaggedWith($className, $property->getName(), 'Version')) {
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
				}
			}
		}
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
			$this->reflectionService->getClassNamesByTag('valueobject'),
			$this->reflectionService->getClassNamesByTag('entity'),
			$this->reflectionService->getClassNamesByTag('Entity'),
			$this->reflectionService->getClassNamesByTag('MappedSuperclass')
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
					!$this->reflectionService->isClassTaggedWith($className, 'valueobject') &&
					!$this->reflectionService->isClassTaggedWith($className, 'entity') &&
					!$this->reflectionService->isClassTaggedWith($className, 'Entity') &&
					!$this->reflectionService->isClassTaggedWith($className, 'MappedSuperclass')
				);
	}

	/**
	 * Checks if the specified class and method matches against the filter
	 *
	 * @param string $className Name of the class to check against
	 * @param string $methodName Name of the method to check against
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class / method match, otherwise FALSE
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		$tags = $this->reflectionService->getPropertyNamesByTag($className, 'Id');
		return $tags === array();
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
}

?>