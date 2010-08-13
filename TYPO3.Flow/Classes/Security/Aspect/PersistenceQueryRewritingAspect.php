<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Aspect;

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
 * An aspect which rewrites persistence query to filter object one should not be able to retrieve.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @aspect
 */
class PersistenceQueryRewritingAspect {

	/**
	 * @var \F3\FLOW3\Security\Policy\PolicyService
	 */
	protected $policyService;

	/**
	 * @var \F3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * The reflection service
	 * @var F3\FLOW3\Reflection\ServiceInterface
	 */
	protected $reflectionService;

	/**
	 * The persistence manager
	 * @var F3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Array of registered global objects that can be accessed as operands
	 * @var array
	 */
	protected $globalObjects = array();

	/**
	 * Inject global settings, retrieves the registered global objects that might be used as operands
	 *
	 * @param array $settings The current FLOW3 settings
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSettings($settings) {
		$this->globalObjects = $settings['aop']['globalObjects'];
	}

	/**
	 * Injects the security context
	 *
	 * @param \F3\FLOW3\Security\Policy\PolicyService $policyService The policy service
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectPolicyService(\F3\FLOW3\Security\Policy\PolicyService $policyService) {
		$this->policyService = $policyService;
	}

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager The object manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param F3\FLOW3\Reflection\ReflectionService $reflectionService The reflection service
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Inject the persistence manager
	 *
	 * @param F3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager The persistence manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Rewrites the QOM query, by adding appropriate constraints according to the policy
	 *
	 * @before within(F3\FLOW3\Persistence\Backend\BackendInterface) && method(.*->(getObjectDataByQuery|getObjectCountByQuery)()) && setting(FLOW3.security.enable)
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewriteQomQuery(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		if ($this->objectManager->isSessionInitialized() === FALSE) return;
		if ($this->securityContext === NULL) $this->securityContext = $this->objectManager->get('F3\FLOW3\Security\Context');

		$query = $joinPoint->getMethodArgument('query');
		$entityType = $query->getType();
        $authenticatedRoles = $this->securityContext->getRoles();

		if ($this->policyService->hasPolicyEntryForEntityType($entityType, $authenticatedRoles)) {
			$policyConstraintsDefinition = $this->policyService->getResourcesConstraintsForEntityTypeAndRoles($entityType, $authenticatedRoles);
			$additionalCalculatedConstraints = $this->getQomConstraintForConstraintDefinitions($policyConstraintsDefinition, $query);

			$newConstraints = $query->logicalAnd($query->getConstraint(), $query->logicalNot($additionalCalculatedConstraints));
			$query->matching($newConstraints);
		}
	}

	/**
	 * Checks, if the current policy allows the retrieval of the object fetched by getObjectDataByIdentifier()
	 *
	 * @around within(F3\FLOW3\Persistence\Backend\BackendInterface) && method(.*->getObjectDataByIdentifier()) && setting(FLOW3.security.enable)
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return array The object data of the original object, or NULL if access is not permitted
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkAccessAfterFetchingAnObjectByIdentifier(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$queryResult = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($this->objectManager->isSessionInitialized() === FALSE) return $queryResult;

		if ($this->securityContext === NULL) $this->securityContext = $this->objectManager->get('F3\FLOW3\Security\Context');

        $authenticatedRoles = $this->securityContext->getRoles();

		if ($this->policyService->hasPolicyEntryForEntityType($queryResult['classname'], $authenticatedRoles)) {
			$policyConstraintsDefinition = $this->policyService->getResourcesConstraintsForEntityTypeAndRoles($queryResult['classname'], $authenticatedRoles);
			if ($this->checkConstraintDefinitionsOnResultArray($policyConstraintsDefinition, $queryResult) === FALSE) return array();
		}

		return $queryResult;
	}

	/**
	 * Builds a QOM constraint object for an array of constraint expressions
	 *
	 * @param array $constraintDefinitions The constraint expressions
	 * @param \F3\FLOW3\Persistence\Query $query The query object to build the constraint with
	 * @return \F3\FLOW3\Persistence\Qom\Constraint The build constraint object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function getQomConstraintForConstraintDefinitions(array $constraintDefinitions, \F3\FLOW3\Persistence\Query $query) {
		$compositeConstraint = NULL;
		foreach ($constraintDefinitions as $resource => $resourceConstraints) {
			foreach ($resourceConstraints as $operator => $policyConstraints) {
				foreach ($policyConstraints as $key => $singlePolicyConstraint) {
					if ($key === 'subConstraints') {
						$currentConstraint = $this->getQomConstraintForConstraintDefinitions(array($singlePolicyConstraint), $query);
					} else {
						$currentConstraint = $this->getQomConstraintForSingleConstraintDefinition($singlePolicyConstraint, $query);
					}

					if ($compositeConstraint === NULL) {
						$compositeConstraint = $currentConstraint;
						continue;
					}

					switch ($operator) {
						case '&&':
							$compositeConstraint = $query->logicalAnd($compositeConstraint, $currentConstraint);
							break;
						case '&&!':
							$compositeConstraint = $query->logicalAnd($compositeConstraint, $query->logicalNot($currentConstraint));
							break;
						case '||':
							$compositeConstraint = $query->logicalOr($compositeConstraint, $currentConstraint);
							break;
						case '||!':
							$compositeConstraint = $query->logicalOr($compositeConstraint, $query->logicalNot($currentConstraint));
							break;
					}
				}
			}
		}

		return $compositeConstraint;
	}

	/**
	 * Builds a QOM constraint object for one single constraint expression
	 *
	 * @param array $constraintDefinition The constraint expression
	 * @param \F3\FLOW3\Persistence\Query $query The query object to build the constraint with
	 * @return \F3\FLOW3\Persistence\Qom\Constraint The build constraint object
	 * @throws \F3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function getQomConstraintForSingleConstraintDefinition(array $constraintDefinition, \F3\FLOW3\Persistence\Query $query) {
		if (!is_array($constraintDefinition['leftValue']) && strpos($constraintDefinition['leftValue'], 'this.') === 0) {
			$propertyName = substr($constraintDefinition['leftValue'], 5);
			$operand = $this->getValueForOperand($constraintDefinition['rightValue']);
		} else if (!is_array($constraintDefinition['rightValue']) && strpos($constraintDefinition['rightValue'], 'this.') === 0) {
			$propertyName = substr($constraintDefinition['rightValue'], 5);
			$operand = $this->getValueForOperand($constraintDefinition['leftValue']);
		} else {
			throw new \F3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException('An entity constraint has to have one operand that references to "this.". Got: "' . $constraintDefinition['leftValue'] . '" and "' . $constraintDefinition['rightValue'] . '"', 1267881842);
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

		throw new \F3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException('The configured operator of the entity constraint is not valid. Got: ' . $constraintDefinition['operator'], 1270483540);
	}

	/**
	 * Checks, if the given constraints hold for the passed query result.
	 *
	 * @param array $constraintDefinitions The constraint definitions array
	 * @param array $queryResult The result array returned by the persistence backend
	 * @return boolean TRUE if the query result is valid for the given constraint
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function checkConstraintDefinitionsOnResultArray(array $constraintDefinitions, array $queryResult) {
		$overallResult = TRUE;
		
		foreach ($constraintDefinitions as $resource => $resourceConstraints) {
			foreach ($resourceConstraints as $operator => $policyConstraints) {
				foreach ($policyConstraints as $key => $singlePolicyConstraint) {
					if ($key === 'subConstraints') {
						$currentResult = $this->checkConstraintDefinitionsOnResultArray(array($singlePolicyConstraint), $queryResult);
					} else {
						$currentResult = $this->checkSingleConstraintDefinitionOnResultArray($singlePolicyConstraint, $queryResult);
					}

					switch ($operator) {
						case '&&':
							$overallResult = $currentResult && $overallResult;
							break;
						case '&&!':
							$overallResult = (!$currentResult) && $overallResult;
							break;
						case '||':
							$overallResult = $currentResult || $overallResult;
							break;
						case '||!':
							$overallResult = (!$currentResult) && $overallResult;
							break;
					}
				}
			}
		}

		return $overallResult;
	}

	/**
	 * Checks, if the given constraint holds for the passed query result.
	 *
	 * @param array $constraintDefinition The constraint definition array
	 * @param array $queryResult The result array returned by the persistence backend
	 * @return boolean TRUE if the query result is valid for the given constraint
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function checkSingleConstraintDefinitionOnResultArray(array $constraintDefinition, array $queryResult) {
		$referenceToThisFound = FALSE;
		$leftOperand = NULL;
		$rightOperand = NULL;

		if (!is_array($constraintDefinition['leftValue']) && strpos($constraintDefinition['leftValue'], 'this.') === 0) {
			$referenceToThisFound = TRUE;
			$propertyPath = substr($constraintDefinition['leftValue'], 5);
			$leftOperand = $this->getResultValueForObjectAccessExpression($propertyPath, $queryResult);
		} else {
			$leftOperand = $this->getValueForOperand($constraintDefinition['leftValue']);
		}

		if (!is_array($constraintDefinition['rightValue']) && strpos($constraintDefinition['rightValue'], 'this.') === 0) {
			$referenceToThisFound = TRUE;
			$propertyPath = substr($constraintDefinition['rightValue'], 5);
			$rightOperand = $this->getResultValueForObjectAccessExpression($propertyPath, $queryResult);
		} else {
			$rightOperand = $this->getValueForOperand($constraintDefinition['rightValue']);
		}

		if ($referenceToThisFound === FALSE) throw new \F3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException('An entity constraint has to have at least one operand that references to "this.". Got: "' . $constraintDefinition['leftValue'] . '" and "' . $constraintDefinition['rightValue'] . '"', 1277218400);

		if (is_object($leftOperand)
					&& $leftOperand instanceof \F3\FLOW3\AOP\ProxyInterface
					&& $leftOperand instanceof \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface
					&& $this->persistenceManager->isNewObject($leftOperand) === FALSE
					&& $this->reflectionService->isClassTaggedWith($leftOperand, 'entity')) {

			$leftOperand = $this->persistenceManager->getIdentifierByObject($leftOperand);

		} elseif (is_object($rightOperand)
					&& $rightOperand instanceof \F3\FLOW3\AOP\ProxyInterface
					&& $rightOperand instanceof \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface
					&& $this->persistenceManager->isNewObject($rightOperand) === FALSE
					&& $this->reflectionService->isClassTaggedWith($rightOperand, 'entity')) {

			$rightOperand = $this->persistenceManager->getIdentifierByObject($rightOperand);
		}

		switch ($constraintDefinition['operator']) {
			case '!=':
				return ($leftOperand === $rightOperand);
				break;
			case '==':
				return ($leftOperand !== $rightOperand);
				break;
			case '<':
				return ($leftOperand >= $rightOperand);
				break;
			case '>':
				return ($leftOperand <= $rightOperand);
				break;
			case '<=':
				return ($leftOperand > $rightOperand);
				break;
			case '>=':
				return ($leftOperand < $rightOperand);
				break;
			case 'in':
				return !in_array($leftOperand, $rightOperand);
				break;
			case 'contains':
				return !in_array($rightOperand, $leftOperand);
				break;
			case 'matches':
				return (count(array_intersect($leftOperand, $rightOperand)) === 0);
				break;
		}

		throw new \F3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException('The configured operator of the entity constraint is not valid. Got: ' . $constraintDefinition['operator'], 1277222521);
	}

	/**
	 * Returns the value found by the given path expression on the array representation of an object.
	 * Note: If the result is an object itself, its identifier (UUID) is returned.
	 *
	 * @param string $pathExpression The path expression to navigate to a property value
	 * @param array $queryResult The result array of an object tree as returned by the persistence backend
	 * @return mixed The value found at the given path in the objec tree
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function getResultValueForObjectAccessExpression($pathExpression, array $queryResult) {
		$pathParts = explode('.', $pathExpression);
		$resultValue = $queryResult['properties'][$pathParts[0]];
		array_shift($pathParts);

		foreach ($pathParts as $part) {
			if (in_array($resultValue['type'], array('string', 'integer', 'float', 'boolean', 'DateTime', 'SplObjectStorage'))) throw new \F3\FLOW3\Security\Exception\InvalidQueryRewritingConstraintException('The configured query constraint in the security policy did not match the object structure of the returned object.', 1277216979);
			$resultValue = $resultValue['value']['properties'][$part];
		}

		if (!in_array($resultValue['type'], array('string', 'integer', 'float', 'boolean', 'DateTime', 'SplObjectStorage'))) return $resultValue['value']['identifier'];

		return $resultValue['value'];
	}

	/**
	 * Returns the static value of the given operand, this might be also a global object
	 *
	 * @param string $expression The expression string representing the operand
	 * @return mixed The calculated value
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
			eval('$globalObject = ' . $this->globalObjects[$objectAccess[1]]);
			return \F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($globalObject, $objectAccess[2]);
		} else {
			return trim($expression, '"\'');
		}
	}
}

?>
