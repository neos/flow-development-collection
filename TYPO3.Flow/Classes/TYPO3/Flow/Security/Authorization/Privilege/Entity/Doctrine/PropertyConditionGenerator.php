<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Query\Filter\SQLFilter as DoctrineSqlFilter;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Exception\InvalidPolicyException;
use TYPO3\Flow\Security\Exception\InvalidQueryRewritingConstraintException;
use TYPO3\Flow\Security\Policy\PolicyService;

/**
 * A sql generator to create a sql condition for an entity property.
 */
class PropertyConditionGenerator implements SqlGeneratorInterface {


	/**
	 * Property path the currently parsed expression relates to
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * @var string
	 */
	protected $operator;

	/**
	 * @var string|array
	 */
	protected $operandDefinition;

	/**
	 * @var mixed
	 */
	protected $operand;

	/**
	 * Array of registered global objects that can be accessed as operands
	 *
	 * @Flow\InjectConfiguration("aop.globalObjects")
	 * @var array
	 */
	protected $globalObjects = array();

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var PolicyService
	 */
	protected $policyService;

	/**
	 * @Flow\Inject
	 * @var ObjectManager
	 */
	protected $entityManager;

	/**
	 * @Flow\Inject
	 * @var PersistenceManager
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @param string $path Property path the currently parsed expression relates to
	 */
	public function __construct($path) {
		$this->path = $path;
	}

	/**
	 * @param string|array $operandDefinition
	 * @return PropertyConditionGenerator the current instance to allow for method chaining
	 */
	public function equals($operandDefinition) {
		$this->operator = '==';
		$this->operandDefinition = $operandDefinition;
		$this->operand = $this->getValueForOperand($operandDefinition);
		return $this;
	}

	/**
	 * @param string|array $operandDefinition
	 * @return PropertyConditionGenerator the current instance to allow for method chaining
	 */
	public function notEquals($operandDefinition) {
		$this->operator = '!=';
		$this->operandDefinition = $operandDefinition;
		$this->operand = $this->getValueForOperand($operandDefinition);
		return $this;
	}

	/**
	 * @param mixed $operandDefinition
	 * @return PropertyConditionGenerator the current instance to allow for method chaining
	 */
	public function lessThan($operandDefinition) {
		$this->operator = '<';
		$this->operandDefinition = $operandDefinition;
		$this->operand = $this->getValueForOperand($operandDefinition);
		return $this;
	}

	/**
	 * @param mixed $operandDefinition
	 * @return PropertyConditionGenerator the current instance to allow for method chaining
	 */
	public function lessOrEqual($operandDefinition) {
		$this->operator = '<=';
		$this->operandDefinition = $operandDefinition;
		$this->operand = $this->getValueForOperand($operandDefinition);
		return $this;
	}

	/**
	 * @param mixed $operandDefinition
	 * @return PropertyConditionGenerator the current instance to allow for method chaining
	 */
	public function greaterThan($operandDefinition) {
		$this->operator = '>';
		$this->operandDefinition = $operandDefinition;
		$this->operand = $this->getValueForOperand($operandDefinition);
		return $this;
	}

	/**
	 * @param mixed $operandDefinition
	 * @return PropertyConditionGenerator the current instance to allow for method chaining
	 */
	public function greaterOrEqual($operandDefinition) {
		$this->operator = '>=';
		$this->operandDefinition = $operandDefinition;
		$this->operand = $this->getValueForOperand($operandDefinition);
		return $this;
	}

	/**
	 * @param mixed $operandDefinition
	 * @return PropertyConditionGenerator the current instance to allow for method chaining
	 */
	public function like($operandDefinition) {
		$this->operator = 'like';
		$this->operandDefinition = $operandDefinition;
		$this->operand = $this->getValueForOperand($operandDefinition);
		return $this;
	}

	/**
	 * @param mixed $operandDefinition
	 * @return PropertyConditionGenerator the current instance to allow for method chaining
	 * @throws InvalidPolicyException
	 */
	public function in($operandDefinition) {
		$this->operator = 'in';
		$this->operand = $this->getValueForOperand($operandDefinition);

		if (is_array($this->operand) === FALSE && ($this->operand instanceof \Traversable) === FALSE) {
			throw new InvalidPolicyException(sprintf('The "in" operator needs an array as operand! Got: "%s"', $this->operand), 1416313526);
		}
		foreach ($this->operand as $iterator => $singleOperandValueDefinition) {
			$this->operandDefinition['inOperandValue' . $iterator] = $singleOperandValueDefinition;
		}
		return $this;
	}

	/**
	 * @param DoctrineSqlFilter $sqlFilter
	 * @param ClassMetadata $targetEntity
	 * @param string $targetTableAlias
	 * @return string
	 * @throws InvalidQueryRewritingConstraintException
	 * @throws \Exception
	 */
	public function getSql(DoctrineSqlFilter $sqlFilter, ClassMetadata $targetEntity, $targetTableAlias) {
		$targetEntityPropertyName = (strpos($this->path, '.') ? substr($this->path, 0, strpos($this->path, '.')) : $this->path);
		$quoteStrategy = $this->entityManager->getConfiguration()->getQuoteStrategy();

		if ($targetEntity->hasAssociation($targetEntityPropertyName) === FALSE) {
			return $this->getSqlForSimpleProperty($sqlFilter, $quoteStrategy, $targetEntity, $targetTableAlias, $targetEntityPropertyName);
		}

		elseif (strstr($this->path, '.') === FALSE && $targetEntity->isSingleValuedAssociation($targetEntityPropertyName) === TRUE && $targetEntity->isAssociationInverseSide($targetEntityPropertyName) === FALSE) {
			return $this->getSqlForManyToOneAndOneToOneRelationsWithoutPropertyPath($sqlFilter, $quoteStrategy, $targetEntity, $targetTableAlias, $targetEntityPropertyName);
		}

		elseif ($targetEntity->isSingleValuedAssociation($targetEntityPropertyName) === TRUE && $targetEntity->isAssociationInverseSide($targetEntityPropertyName) === FALSE) {
			return $this->getSqlForManyToOneAndOneToOneRelationsWithPropertyPath($sqlFilter, $quoteStrategy, $targetEntity, $targetTableAlias, $targetEntityPropertyName);
		}

		elseif ($targetEntity->isSingleValuedAssociation($targetEntityPropertyName) === TRUE && $targetEntity->isAssociationInverseSide($targetEntityPropertyName) === TRUE) {
			throw new InvalidQueryRewritingConstraintException('Single valued properties from the inverse side are not supported in a content security constraint path! Got: "' . $this->path . ' ' . $this->operator . ' ' . $this->operandDefinition .  '"', 1416397754);
		}

		elseif ($targetEntity->isCollectionValuedAssociation($targetEntityPropertyName) === TRUE) {
			throw new InvalidQueryRewritingConstraintException('Multivalued properties are not supported in a content security constraint path! Got: "' . $this->path . ' ' . $this->operator . ' ' . $this->operandDefinition .  '"', 1416397655);
		}

		throw new InvalidQueryRewritingConstraintException('The configured operator of the entity constraint is not valid/supported. Got: ' . $this->operator, 1270483540);
	}

	/**
	 * @param DoctrineSqlFilter $sqlFilter
	 * @param QuoteStrategy $quoteStrategy
	 * @param ClassMetadata $targetEntity
	 * @param string $targetTableAlias
	 * @param string $targetEntityPropertyName
	 * @return string
	 * @throws InvalidQueryRewritingConstraintException
	 * @throws \Exception
	 */
	protected function getSqlForSimpleProperty(DoctrineSqlFilter $sqlFilter, QuoteStrategy $quoteStrategy, ClassMetadata $targetEntity, $targetTableAlias, $targetEntityPropertyName) {
		$quotedColumnName = $quoteStrategy->getColumnName($targetEntityPropertyName, $targetEntity, $this->entityManager->getConnection()->getDatabasePlatform());
		$propertyPointer = $targetTableAlias . '.' . $quotedColumnName;
		if (is_array($this->operandDefinition)) {
			foreach ($this->operandDefinition as $operandIterator => $singleOperandValue) {
				$sqlFilter->setParameter($operandIterator, $singleOperandValue);
			}
		} else {
			$sqlFilter->setParameter($this->operandDefinition, $this->operand);
		}
		return $this->getConstraintStringForSimpleProperty($sqlFilter, $propertyPointer);
	}

	/**
	 * @param DoctrineSqlFilter $sqlFilter
	 * @param QuoteStrategy $quoteStrategy
	 * @param ClassMetadata $targetEntity
	 * @param string $targetTableAlias
	 * @param string $targetEntityPropertyName
	 * @return string
	 * @throws InvalidQueryRewritingConstraintException
	 * @throws \Exception
	 */
	protected function getSqlForManyToOneAndOneToOneRelationsWithoutPropertyPath(DoctrineSqlFilter $sqlFilter, QuoteStrategy $quoteStrategy, ClassMetadata $targetEntity, $targetTableAlias, $targetEntityPropertyName) {
		$associationMapping = $targetEntity->getAssociationMapping($targetEntityPropertyName);

		$constraints = array();
		foreach ($associationMapping['joinColumns'] as $joinColumn) {
			$quotedColumnName = $quoteStrategy->getJoinColumnName($joinColumn, $targetEntity, $this->entityManager->getConnection()->getDatabasePlatform());
			$propertyPointer = $targetTableAlias . '.' . $quotedColumnName;

			$operandAlias = $this->operandDefinition;
			if (is_array($this->operandDefinition)) {
				$operandAlias = key($this->operandDefinition);
			}
			$currentReferencedOperandName = $operandAlias . $joinColumn['referencedColumnName'];
			if (is_object($this->operand)) {
				$operandMetadataInfo = $this->entityManager->getClassMetadata($this->reflectionService->getClassNameByObject($this->operand));
				$currentReferencedValueOfOperand = $operandMetadataInfo->getFieldValue($this->operand, $operandMetadataInfo->getFieldForColumn($joinColumn['referencedColumnName']));
				$sqlFilter->setParameter($currentReferencedOperandName, $currentReferencedValueOfOperand, $associationMapping['type']);

			} elseif (is_array($this->operandDefinition)) {
				foreach ($this->operandDefinition as $operandIterator => $singleOperandValue) {
					if (is_object($singleOperandValue)) {
						$operandMetadataInfo = $this->entityManager->getClassMetadata($this->reflectionService->getClassNameByObject($singleOperandValue));
						$currentReferencedValueOfOperand = $operandMetadataInfo->getFieldValue($singleOperandValue, $operandMetadataInfo->getFieldForColumn($joinColumn['referencedColumnName']));
						$sqlFilter->setParameter($operandIterator, $currentReferencedValueOfOperand, $associationMapping['type']);
					} elseif ($singleOperandValue === NULL) {
						$sqlFilter->setParameter($operandIterator, NULL, $associationMapping['type']);
					}
				}
			}

			$constraints[] = $this->getConstraintStringForSimpleProperty($sqlFilter, $propertyPointer, $currentReferencedOperandName);
		}
		return ' (' . implode(' ) AND ( ', $constraints) . ') ';
	}

	/**
	 * @param DoctrineSqlFilter $sqlFilter
	 * @param QuoteStrategy $quoteStrategy
	 * @param ClassMetadata $targetEntity
	 * @param string $targetTableAlias
	 * @param string $targetEntityPropertyName
	 * @return string
	 * @throws InvalidQueryRewritingConstraintException
	 * @throws \Exception
	 */
	protected function getSqlForManyToOneAndOneToOneRelationsWithPropertyPath(DoctrineSqlFilter $sqlFilter, QuoteStrategy $quoteStrategy, ClassMetadata $targetEntity, $targetTableAlias, $targetEntityPropertyName) {
		$subselectQuery = $this->getSubselectQuery($targetEntity, $targetEntityPropertyName);

		$associationMapping = $targetEntity->getAssociationMapping($targetEntityPropertyName);

		$subselectConstraintQueries = array();
		foreach ($associationMapping['joinColumns'] as $joinColumn) {
			$rootAliases = $subselectQuery->getQueryBuilder()->getRootAliases();
			$subselectQuery->getQueryBuilder()->select($rootAliases[0] . '.' . $targetEntity->getFieldForColumn($joinColumn['referencedColumnName']));
			$subselectSql = $subselectQuery->getSql();
			foreach ($subselectQuery->getParameters() as $parameter) {
				$parameterValue = $parameter->getValue();
				if (is_object($parameterValue)) {
					$parameterValue = $this->persistenceManager->getIdentifierByObject($parameter->getValue());
				}
				$subselectSql = preg_replace('/\?/', $this->entityManager->getConnection()->quote($parameterValue, $parameter->getType()), $subselectSql, 1);
			}
			$quotedColumnName = $quoteStrategy->getJoinColumnName($joinColumn, $targetEntity, $this->entityManager->getConnection()->getDatabasePlatform());
			$subselectIdentifier = 'subselect' . md5($subselectSql);
			$subselectConstraintQueries[] = $targetTableAlias . '.' . $quotedColumnName . ' IN (SELECT ' . $subselectIdentifier . '.' . $joinColumn['referencedColumnName'] . '0 FROM (' . $subselectSql . ') AS ' . $subselectIdentifier . ' ) ';
		}

		return ' (' . implode(' ) AND ( ', $subselectConstraintQueries) . ') ';
	}

	/**
	 *
	 */
	protected function getSubselectQuery($targetEntity, $targetEntityPropertyName) {
		$subselectQuery = new \TYPO3\Flow\Persistence\Doctrine\Query($targetEntity->getAssociationTargetClass($targetEntityPropertyName));
		$propertyName = str_replace($targetEntityPropertyName.'.', '', $this->path);

		switch ($this->operator) {
			case '==':
				$subselectConstraint = $subselectQuery->equals($propertyName, $this->operand);
				break;
			case '!=':
				$subselectConstraint = $subselectQuery->logicalNot($subselectQuery->equals($propertyName, $this->operand));
				break;
			case '<':
				$subselectConstraint = $subselectQuery->lessThan($propertyName, $this->operand);
				break;
			case '>':
				$subselectConstraint = $subselectQuery->greaterThan($propertyName, $this->operand);
				break;
			case '<=':
				$subselectConstraint = $subselectQuery->lessThanOrEqual($propertyName, $this->operand);
				break;
			case '>=':
				$subselectConstraint = $subselectQuery->greaterThanOrEqual($propertyName, $this->operand);
				break;
			case 'like':
				$subselectConstraint = $subselectQuery->like($propertyName, $this->operand);
				break;
			case 'in':
				$subselectConstraint = $subselectQuery->in($propertyName, $this->operand);
				break;
		}

		$subselectQuery->matching($subselectConstraint);
		return $subselectQuery;
	}

	/**
	 * @param SQLFilter $sqlFilter
	 * @param string $propertyPointer
	 * @param string $operandDefinition
	 * @return string
	 */
	protected function getConstraintStringForSimpleProperty(SQLFilter $sqlFilter, $propertyPointer, $operandDefinition = NULL) {
		$operandDefinition = ($operandDefinition === NULL ? $this->operandDefinition : $operandDefinition);
		$parameter = NULL;
		$addNullExpression = FALSE;
		try {
			if (is_array($this->operandDefinition)) {
				$parameters = array();
				foreach ($this->operandDefinition as $operandIterator => $singleOperandValue) {
					if ($singleOperandValue !== NULL) {
						$parameters[] = $sqlFilter->getParameter($operandIterator);
					} else {
						$addNullExpression = TRUE;
					}
				}
				$parameter = implode(',', $parameters);
			} else {
				$parameter = $sqlFilter->getParameter($operandDefinition);
			}
		} catch (\InvalidArgumentException $e) {}

		if ($parameter === NULL || $parameter === '') {
			$addNullExpression = TRUE;
		}

		switch ($this->operator) {
			case '==':
				return ($parameter === NULL ? $propertyPointer . ' IS NULL' : $propertyPointer . ' = ' . $parameter);
				break;
			case '!=':
				return ($parameter === NULL ? $propertyPointer . ' IS NOT NULL' : $propertyPointer . ' <> ' . $parameter);
				break;
			case '<':
				return $propertyPointer . ' < ' . $parameter;
				break;
			case '>':
				return $propertyPointer . ' > ' . $parameter;
				break;
			case '<=':
				return $propertyPointer . ' <= ' . $parameter;
				break;
			case '>=':
				return $propertyPointer . ' >= ' . $parameter;
				break;
			case 'like':
				return $propertyPointer . ' LIKE ' . $parameter;
				break;
			case 'in':
				$inPart = $parameter !== NULL && $parameter !== '' ? $propertyPointer . ' IN (' . $parameter . ') ' : '';
				$nullPart = $addNullExpression ? $propertyPointer . ' IS NULL' : '';
				return $inPart . ($inPart !== '' && $nullPart !== '' ? ' OR ' : '') . $nullPart;
				break;
		}
	}

	/**
	 * Returns the static value of the given operand, this might be also a global object
	 *
	 * @param mixed $expression The expression string representing the operand
	 * @return mixed The calculated value
	 */
	public function getValueForOperand($expression) {
		if (is_array($expression)) {
			$result = array();
			foreach ($expression as $expressionEntry) {
				$result[] = $this->getValueForOperand($expressionEntry);
			}
			return $result;
		} else if (is_numeric($expression)) {
			return $expression;
		} else if ($expression === 'TRUE') {
			return TRUE;
		} else if ($expression === 'FALSE') {
			return FALSE;
		} else if ($expression === 'NULL') {
			return NULL;
		} else if (strpos($expression, 'context.') === 0) {
			$objectAccess = explode('.', $expression, 3);
			$globalObjectsRegisteredClassName = $this->globalObjects[$objectAccess[1]];
			$globalObject = $this->objectManager->get($globalObjectsRegisteredClassName);
			return $this->getObjectValueByPath($globalObject, $objectAccess[2]);
		} else {
			return trim($expression, '"\'');
		}
	}

	/**
	 * Redirects directly to \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($result, $propertyPath)
	 * This is only needed for unit tests!
	 *
	 * @param mixed $object The object to fetch the property from
	 * @param string $path The path to the property to be fetched
	 * @return mixed The property value
	 */
	public function getObjectValueByPath($object, $path) {
		return ObjectAccess::getPropertyPath($object, $path);
	}
}
