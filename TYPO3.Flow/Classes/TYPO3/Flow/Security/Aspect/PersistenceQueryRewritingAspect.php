<?php
namespace TYPO3\Flow\Security\Aspect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Proxy\Proxy;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Persistence\EmptyQueryResult;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Security\Exception\InvalidQueryRewritingConstraintException;

/**
 * An aspect which rewrites persistence query to filter objects one should not be able to retrieve.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class PersistenceQueryRewritingAspect {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 */
	protected $policyService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Session\SessionInterface
	 */
	protected $session;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Array of registered global objects that can be accessed as operands
	 * @var array
	 */
	protected $globalObjects = array();

	/**
	 * @var \SplObjectStorage
	 */
	protected $alreadyRewrittenQueries;

	/**
	 * Inject global settings, retrieves the registered global objects that might be used as operands
	 *
	 * @param array $settings The current Flow settings
	 * @return void
	 */
	public function injectSettings($settings) {
		$this->globalObjects = $settings['aop']['globalObjects'];
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->alreadyRewrittenQueries = new \SplObjectStorage();
	}

	/**
	 * Rewrites the QOM query, by adding appropriate constraints according to the policy
	 *
	 * @Flow\Around("setting(TYPO3.Flow.security.enable) && within(TYPO3\Flow\Persistence\QueryInterface) && method(.*->(execute|count)())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed
	 */
	public function rewriteQomQuery(JoinPointInterface $joinPoint) {
		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($this->securityContext->areAuthorizationChecksDisabled() === TRUE || $this->policyService->hasPolicyEntriesForEntities() === FALSE) {
			return $result;
		}
		if ($this->securityContext->isInitialized() === FALSE) {
			if ($this->securityContext->canBeInitialized() === TRUE) {
				$this->securityContext->initialize();
			} else {
				return $result;
			}
		}

		/** @var $query QueryInterface */
		$query = $joinPoint->getProxy();

		if ($this->alreadyRewrittenQueries->contains($query)) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		} else {
			$this->alreadyRewrittenQueries->attach($query);
		}

		$entityType = $query->getType();
		$authenticatedRoles = $this->securityContext->getRoles();

		if ($this->policyService->hasPolicyEntryForEntityType($entityType, $authenticatedRoles)) {
			if ($this->policyService->isGeneralAccessForEntityTypeGranted($entityType, $authenticatedRoles) === FALSE) {
				return ($joinPoint->getMethodName() === 'count') ? 0 : new EmptyQueryResult($query);
			}
			$policyConstraintsDefinition = $this->policyService->getResourcesConstraintsForEntityTypeAndRoles($entityType, $authenticatedRoles);
			$additionalCalculatedConstraints = $this->getQomConstraintForConstraintDefinitions($policyConstraintsDefinition, $query);

			if ($query->getConstraint() !== NULL && $additionalCalculatedConstraints !== NULL) {
				$query->matching($query->logicalAnd($query->getConstraint(), $additionalCalculatedConstraints));
			} elseif ($additionalCalculatedConstraints !== NULL) {
				$query->matching($additionalCalculatedConstraints);
			}
		}

		return $joinPoint->getAdviceChain()->proceed($joinPoint);
	}

	/**
	 * Checks, if the current policy allows the retrieval of the object fetched by getObjectDataByIdentifier()
	 *
	 * @Flow\Around("within(TYPO3\Flow\Persistence\PersistenceManagerInterface) && method(.*->getObjectByIdentifier()) && setting(TYPO3.Flow.security.enable)")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return array The object data of the original object, or NULL if access is not permitted
	 */
	public function checkAccessAfterFetchingAnObjectByIdentifier(JoinPointInterface $joinPoint) {
		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($this->securityContext->areAuthorizationChecksDisabled() === TRUE || $this->policyService->hasPolicyEntriesForEntities() === FALSE) {
			return $result;
		}
		if ($this->securityContext->isInitialized() === FALSE) {
			if ($this->securityContext->canBeInitialized() === TRUE) {
				$this->securityContext->initialize();
			} else {
				return $result;
			}
		}

		$authenticatedRoles = $this->securityContext->getRoles();

		if ($result instanceof Proxy) {
			$entityType = get_parent_class($result);
		} else {
			$entityType = get_class($result);
		}

		if ($this->policyService->hasPolicyEntryForEntityType($entityType, $authenticatedRoles)) {
			if ($this->policyService->isGeneralAccessForEntityTypeGranted($entityType, $authenticatedRoles) === FALSE) {
				return NULL;
			}
			$policyConstraintsDefinition = $this->policyService->getResourcesConstraintsForEntityTypeAndRoles($entityType, $authenticatedRoles);
			if ($this->checkConstraintDefinitionsOnResultObject($policyConstraintsDefinition, $result) === FALSE) {
				return NULL;
			}
		}

		return $result;
	}

	/**
	 * Builds a QOM constraint object for an array of constraint expressions
	 *
	 * @param array $constraintDefinitions The constraint expressions
	 * @param \TYPO3\Flow\Persistence\QueryInterface $query The query object to build the constraint with
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\Constraint The build constraint object
	 */
	protected function getQomConstraintForConstraintDefinitions(array $constraintDefinitions, QueryInterface $query) {
		$resourceConstraintObjects = array();
		foreach ($constraintDefinitions as $resourceConstraintsDefinition) {
			$resourceConstraintObject = NULL;
			foreach ($resourceConstraintsDefinition as $operator => $policyConstraintsDefinition) {
				foreach ($policyConstraintsDefinition as $key => $singlePolicyConstraintDefinition) {
					if ($key === 'subConstraints') {
						$currentConstraint = $this->getQomConstraintForConstraintDefinitions(array($singlePolicyConstraintDefinition), $query);
					} else {
						$currentConstraint = $this->getQomConstraintForSingleConstraintDefinition($singlePolicyConstraintDefinition, $query);
					}

					if ($resourceConstraintObject === NULL) {
						$resourceConstraintObject = $currentConstraint;
						continue;
					}

					switch ($operator) {
						case '&&':
							$resourceConstraintObject = $query->logicalAnd($resourceConstraintObject, $currentConstraint);
							break;
						case '&&!':
							$resourceConstraintObject = $query->logicalAnd($resourceConstraintObject, $query->logicalNot($currentConstraint));
							break;
						case '||':
							$resourceConstraintObject = $query->logicalOr($resourceConstraintObject, $currentConstraint);
							break;
						case '||!':
							$resourceConstraintObject = $query->logicalOr($resourceConstraintObject, $query->logicalNot($currentConstraint));
							break;
					}
				}
			}
			$resourceConstraintObjects[] = $query->logicalNot($resourceConstraintObject);
		}

		if (count($resourceConstraintObjects) > 1) {
			return $query->logicalAnd($resourceConstraintObjects);
		} elseif (count($resourceConstraintObjects) === 1) {
			return current($resourceConstraintObjects);
		} else {
			return NULL;
		}
	}

	/**
	 * Builds a QOM constraint object for one single constraint expression
	 *
	 * @param array $constraintDefinition The constraint expression
	 * @param \TYPO3\Flow\Persistence\QueryInterface $query The query object to build the constraint with
	 * @return \TYPO3\Flow\Persistence\Generic\Qom\Constraint The build constraint object
	 * @throws \TYPO3\Flow\Security\Exception\InvalidQueryRewritingConstraintException
	 */
	protected function getQomConstraintForSingleConstraintDefinition(array $constraintDefinition, QueryInterface $query) {
		if (!is_array($constraintDefinition['leftValue']) && strpos($constraintDefinition['leftValue'], 'this.') === 0) {
			$propertyName = substr($constraintDefinition['leftValue'], 5);
			$operand = $this->getValueForOperand($constraintDefinition['rightValue']);
		} elseif (!is_array($constraintDefinition['rightValue']) && strpos($constraintDefinition['rightValue'], 'this.') === 0) {
			$propertyName = substr($constraintDefinition['rightValue'], 5);
			$operand = $this->getValueForOperand($constraintDefinition['leftValue']);
		} else {
			throw new InvalidQueryRewritingConstraintException('An entity constraint has to have one operand that references to "this.". Got: "' . $constraintDefinition['leftValue'] . '" and "' . $constraintDefinition['rightValue'] . '"', 1267881842);
		}

		switch ($constraintDefinition['operator']) {
			case '==':
				return $query->equals($propertyName, $operand);
				break;
			case '!=':
				return $query->logicalNot($query->equals($propertyName, $operand));
				break;
			case '<':
				return $query->lessThan($propertyName, $operand);
				break;
			case '>':
				return $query->greaterThan($propertyName, $operand);
				break;
			case '<=':
				return $query->lessThanOrEqual($propertyName, $operand);
				break;
			case '>=':
				return $query->greaterThanOrEqual($propertyName, $operand);
				break;
			case 'in':
				return $query->in($propertyName, $operand);
				break;
			case 'contains':
				return $query->contains($propertyName, $operand);
				break;
			case 'matches':
				$compositeConstraint = NULL;
				foreach ($operand as $operandEntry) {
					$currentConstraint = $query->contains($propertyName, $operandEntry);

					if ($compositeConstraint === NULL) {
						$compositeConstraint = $currentConstraint;
						continue;
					}

					$compositeConstraint = $query->logicalAnd($currentConstraint, $compositeConstraint);
				}

				return $compositeConstraint;
				break;
		}

		throw new InvalidQueryRewritingConstraintException('The configured operator of the entity constraint is not valid. Got: ' . $constraintDefinition['operator'], 1270483540);
	}

	/**
	 * Checks, if the given constraints hold for the passed result.
	 *
	 * @param array $constraintDefinitions The constraint definitions array
	 * @param object $result The result object returned by the persistence manager
	 * @return boolean TRUE if the query result is valid for the given constraint
	 */
	protected function checkConstraintDefinitionsOnResultObject(array $constraintDefinitions, $result) {
		foreach ($constraintDefinitions as $resourceConstraintsDefinition) {
			$resourceResult = TRUE;
			foreach ($resourceConstraintsDefinition as $operator => $policyConstraintsDefinition) {
				foreach ($policyConstraintsDefinition as $key => $singlePolicyConstraintDefinition) {
					if ($key === 'subConstraints') {
						$currentResult = $this->checkConstraintDefinitionsOnResultObject(array($singlePolicyConstraintDefinition), $result);
					} else {
						$currentResult = $this->checkSingleConstraintDefinitionOnResultObject($singlePolicyConstraintDefinition, $result);
					}

					switch ($operator) {
						case '&&':
							$resourceResult = $currentResult && $resourceResult;
							break;
						case '&&!':
							$resourceResult = (!$currentResult) && $resourceResult;
							break;
						case '||':
							$resourceResult = $currentResult || $resourceResult;
							break;
						case '||!':
							$resourceResult = (!$currentResult) && $resourceResult;
							break;
					}
				}
			}

			if ($resourceResult === TRUE) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Checks, if the given constraint holds for the passed result.
	 *
	 * @param array $constraintDefinition The constraint definition array
	 * @param object $result The result object returned by the persistence manager
	 * @return boolean TRUE if the query result is valid for the given constraint
	 * @throws \TYPO3\Flow\Security\Exception\InvalidQueryRewritingConstraintException
	 */
	protected function checkSingleConstraintDefinitionOnResultObject(array $constraintDefinition, $result) {
		$referenceToThisFound = FALSE;

		if (!is_array($constraintDefinition['leftValue']) && strpos($constraintDefinition['leftValue'], 'this.') === 0) {
			$referenceToThisFound = TRUE;
			$propertyPath = substr($constraintDefinition['leftValue'], 5);
			$leftOperand = $this->getObjectValueByPath($result, $propertyPath);
		} else {
			$leftOperand = $this->getValueForOperand($constraintDefinition['leftValue']);
		}

		if (!is_array($constraintDefinition['rightValue']) && strpos($constraintDefinition['rightValue'], 'this.') === 0) {
			$referenceToThisFound = TRUE;
			$propertyPath = substr($constraintDefinition['rightValue'], 5);
			$rightOperand = $this->getObjectValueByPath($result, $propertyPath);
		} else {
			$rightOperand = $this->getValueForOperand($constraintDefinition['rightValue']);
		}

		if ($referenceToThisFound === FALSE) {
			throw new InvalidQueryRewritingConstraintException('An entity security constraint must have at least one operand that references to "this.". Got: "' . $constraintDefinition['leftValue'] . '" and "' . $constraintDefinition['rightValue'] . '"', 1277218400);
		}

		if (is_object($leftOperand)
			&& (
				$this->reflectionService->isClassAnnotatedWith($this->reflectionService->getClassNameByObject($leftOperand), 'TYPO3\Flow\Annotations\Entity')
					|| $this->reflectionService->isClassAnnotatedWith($this->reflectionService->getClassNameByObject($leftOperand), 'Doctrine\ORM\Mapping\Entity')
			)
		) {
			$leftOperand = $this->persistenceManager->getIdentifierByObject($leftOperand);
		}

		if (is_object($rightOperand)
			&& (
				$this->reflectionService->isClassAnnotatedWith($this->reflectionService->getClassNameByObject($rightOperand), 'TYPO3\Flow\Annotations\Entity')
					|| $this->reflectionService->isClassAnnotatedWith($this->reflectionService->getClassNameByObject($rightOperand), 'Doctrine\ORM\Mapping\Entity')
			)
		) {
			$rightOperand = $this->persistenceManager->getIdentifierByObject($rightOperand);
		}

		switch ($constraintDefinition['operator']) {
			case '!=':
				return ($leftOperand !== $rightOperand);
				break;
			case '==':
				return ($leftOperand === $rightOperand);
				break;
			case '<':
				return ($leftOperand < $rightOperand);
				break;
			case '>':
				return ($leftOperand > $rightOperand);
				break;
			case '<=':
				return ($leftOperand <= $rightOperand);
				break;
			case '>=':
				return ($leftOperand >= $rightOperand);
				break;
			case 'in':
				return in_array($leftOperand, $rightOperand);
				break;
			case 'contains':
				return in_array($rightOperand, $leftOperand);
				break;
			case 'matches':
				return (count(array_intersect($leftOperand, $rightOperand)) !== 0);
				break;
		}

		throw new InvalidQueryRewritingConstraintException('The configured operator of the entity constraint is not valid. Got: ' . $constraintDefinition['operator'], 1277222521);
	}

	/**
	 * Returns the static value of the given operand, this might be also a global object
	 *
	 * @param mixed $expression The expression string representing the operand
	 * @return mixed The calculated value
	 */
	protected function getValueForOperand($expression) {
		if (is_array($expression)) {
			$result = array();
			foreach ($expression as $expressionEntry) {
				$result[] = $this->getValueForOperand($expressionEntry);
			}
			return $result;
		} elseif (is_numeric($expression)) {
			return $expression;
		} elseif ($expression === 'TRUE') {
			return TRUE;
		} elseif ($expression === 'FALSE') {
			return FALSE;
		} elseif ($expression === 'NULL') {
			return NULL;
		} elseif (strpos($expression, 'current.') === 0) {
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
	protected function getObjectValueByPath($object, $path) {
		return ObjectAccess::getPropertyPath($object, $path);
	}
}
