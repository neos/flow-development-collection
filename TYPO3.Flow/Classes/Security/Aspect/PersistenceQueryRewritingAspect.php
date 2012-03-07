<?php
namespace TYPO3\FLOW3\Security\Aspect;

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
 * An aspect which rewrites persistence query to filter objects one should not be able to retrieve.
 *
 * @FLOW3\Scope("singleton")
 * @FLOW3\Aspect
 */
class PersistenceQueryRewritingAspect {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Policy\PolicyService
	 */
	protected $policyService;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Session\SessionInterface
	 */
	protected $session;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @FLOW3\Inject
	 * @var TYPO3\FLOW3\Object\ObjectManagerInterface
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
	 * @param array $settings The current FLOW3 settings
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
	 * @FLOW3\Around("setting(TYPO3.FLOW3.security.enable) && within(TYPO3\FLOW3\Persistence\QueryInterface) && method(.*->(execute|count)())")
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return void
	 */
	public function rewriteQomQuery(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		if ($this->securityContext->isInitialized() === FALSE) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		}

		$query = $joinPoint->getProxy();

		if ($this->alreadyRewrittenQueries->contains($query)) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		} else {
			$this->alreadyRewrittenQueries->attach($query);
		}

		$entityType = $query->getType();
		$authenticatedRoles = $this->securityContext->getRoles();

		if ($this->policyService->hasPolicyEntryForEntityType($entityType, $authenticatedRoles)) {
			if ($this->policyService->isGeneralAccessForEntityTypeGranted($entityType, $authenticatedRoles) === FALSE) return new \TYPO3\FLOW3\Persistence\EmptyQueryResult($query);
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
	 * @FLOW3\Around("within(TYPO3\FLOW3\Persistence\PersistenceManagerInterface) && method(.*->getObjectByIdentifier()) && setting(TYPO3.FLOW3.security.enable)")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return array The object data of the original object, or NULL if access is not permitted
	 */
	public function checkAccessAfterFetchingAnObjectByIdentifier(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);

		if ($this->securityContext->isInitialized() === FALSE) {
			return $result;
		}

		$authenticatedRoles = $this->securityContext->getRoles();

		if ($result instanceof \Doctrine\ORM\Proxy\Proxy) {
			$entityType = get_parent_class($result);
		} else {
			$entityType = get_class($result);
		}

		if ($this->policyService->hasPolicyEntryForEntityType($entityType, $authenticatedRoles)) {
			if ($this->policyService->isGeneralAccessForEntityTypeGranted($entityType, $authenticatedRoles) === FALSE) return NULL;
			$policyConstraintsDefinition = $this->policyService->getResourcesConstraintsForEntityTypeAndRoles($entityType, $authenticatedRoles);
			if ($this->checkConstraintDefinitionsOnResultObject($policyConstraintsDefinition, $result) === FALSE) return NULL;
		}

		return $result;
	}

	/**
	 * Builds a QOM constraint object for an array of constraint expressions
	 *
	 * @param array $constraintDefinitions The constraint expressions
	 * @param \TYPO3\FLOW3\Persistence\QueryInterface $query The query object to build the constraint with
	 * @return \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint The build constraint object
	 */
	protected function getQomConstraintForConstraintDefinitions(array $constraintDefinitions, \TYPO3\FLOW3\Persistence\QueryInterface $query) {
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
		} else {
			return NULL;
		}
	}

	/**
	 * Builds a QOM constraint object for one single constraint expression
	 *
	 * @param array $constraintDefinition The constraint expression
	 * @param \TYPO3\FLOW3\Persistence\QueryInterface $query The query object to build the constraint with
	 * @return \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint The build constraint object
	 * @throws \TYPO3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException
	 */
	protected function getQomConstraintForSingleConstraintDefinition(array $constraintDefinition, \TYPO3\FLOW3\Persistence\QueryInterface $query) {
		if (!is_array($constraintDefinition['leftValue']) && strpos($constraintDefinition['leftValue'], 'this.') === 0) {
			$propertyName = substr($constraintDefinition['leftValue'], 5);
			$operand = $this->getValueForOperand($constraintDefinition['rightValue']);
		} else if (!is_array($constraintDefinition['rightValue']) && strpos($constraintDefinition['rightValue'], 'this.') === 0) {
			$propertyName = substr($constraintDefinition['rightValue'], 5);
			$operand = $this->getValueForOperand($constraintDefinition['leftValue']);
		} else {
			throw new \TYPO3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException('An entity constraint has to have one operand that references to "this.". Got: "' . $constraintDefinition['leftValue'] . '" and "' . $constraintDefinition['rightValue'] . '"', 1267881842);
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

		throw new \TYPO3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException('The configured operator of the entity constraint is not valid. Got: ' . $constraintDefinition['operator'], 1270483540);
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
	 * @throws \TYPO3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException
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

		if ($referenceToThisFound === FALSE) throw new \TYPO3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException('An entity security constraint must have at least one operand that references to "this.". Got: "' . $constraintDefinition['leftValue'] . '" and "' . $constraintDefinition['rightValue'] . '"', 1277218400);

		if (is_object($leftOperand)
			&& (
				$this->reflectionService->isClassAnnotatedWith($this->reflectionService->getClassNameByObject($leftOperand), 'TYPO3\FLOW3\Annotations\Entity')
					|| $this->reflectionService->isClassAnnotatedWith($this->reflectionService->getClassNameByObject($leftOperand), 'Doctrine\ORM\Mapping\Entity')
			)
		) {
			$leftOperand = $this->persistenceManager->getIdentifierByObject($leftOperand);
		}

		if (is_object($rightOperand)
			&& (
				$this->reflectionService->isClassAnnotatedWith($this->reflectionService->getClassNameByObject($rightOperand), 'TYPO3\FLOW3\Annotations\Entity')
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

		throw new \TYPO3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException('The configured operator of the entity constraint is not valid. Got: ' . $constraintDefinition['operator'], 1277222521);
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
		} else if (is_numeric($expression)) {
			return $expression;
		} else if ($expression === 'TRUE') {
			return TRUE;
		} else if ($expression === 'FALSE') {
			return FALSE;
		} else if ($expression === 'NULL') {
			return NULL;
		} else if (strpos($expression, 'current.') === 0) {
			$objectAccess = explode('.', $expression, 3);
			$globalObjectsRegisteredClassName = $this->globalObjects[$objectAccess[1]];
			$globalObject = $this->objectManager->get($globalObjectsRegisteredClassName);
			return $this->getObjectValueByPath($globalObject, $objectAccess[2]);
		} else {
			return trim($expression, '"\'');
		}
	}

	/**
	 * Redirects directly to \TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($result, $propertyPath)
	 * This is only needed for unit tests!
	 *
	 * @param mixed $object The object to fetch the property from
	 * @param string $path The path to the property to be fetched
	 * @return mixed The property value
	 */
	protected function getObjectValueByPath($object, $path) {
		return \TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($object, $path);
	}
}

?>
