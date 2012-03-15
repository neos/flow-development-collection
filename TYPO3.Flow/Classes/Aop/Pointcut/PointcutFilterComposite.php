<?php
namespace TYPO3\FLOW3\Aop\Pointcut;

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
 * This composite allows to check for match against a row pointcut filters
 * by only one method call. All registered filters will be invoked and if one filter
 * doesn't match, the overall result is "no".
 *
 * @see \TYPO3\FLOW3\Aop\Pointcut\PointcutExpressionParser, \TYPO3\FLOW3\Aop\Pointcut\PointcutClassNameFilter, \TYPO3\FLOW3\Aop\Pointcut\PointcutMethodFilter
 * @FLOW3\Proxy(false)
 */
class PointcutFilterComposite implements \TYPO3\FLOW3\Aop\Pointcut\PointcutFilterInterface {

	/**
	 * @var array An array of \TYPO3\FLOW3\Aop\Pointcut\Pointcut*Filter objects
	 */
	protected $filters = array();

	/**
	 * @var boolean
	 */
	protected $earlyReturn = TRUE;

	/**
	 * @var array An array of runtime evaluations
	 */
	protected $runtimeEvaluationsDefinition = array();

	/**
	 * @var array An array of global runtime evaluations
	 */
	protected $globalRuntimeEvaluationsDefinition = array();

	/**
	 * Checks if the specified class and method match the registered class-
	 * and method filter patterns.
	 *
	 * @param string $className Name of the class to check against
	 * @param string $methodName Name of the method to check against
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if class and method match the pattern, otherwise FALSE
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		$this->runtimeEvaluationsDefinition = array();
		$matches = TRUE;
		foreach ($this->filters as &$operatorAndFilter) {
			list($operator, $filter) = $operatorAndFilter;

			$currentFilterMatches = $filter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
			$currentRuntimeEvaluationsDefintion = $filter->getRuntimeEvaluationsDefinition();

			switch ($operator) {
				case '&&' :
					if ($currentFilterMatches === TRUE && $filter->hasRuntimeEvaluationsDefinition()) {
						if (!isset($this->runtimeEvaluationsDefinition[$operator])) {
							$this->runtimeEvaluationsDefinition[$operator] = array();
						}
						$this->runtimeEvaluationsDefinition[$operator] = array_merge_recursive($this->runtimeEvaluationsDefinition[$operator], $currentRuntimeEvaluationsDefintion);
					}
					if ($this->earlyReturn && !$currentFilterMatches) {
						return FALSE;
					}
					$matches = $matches && $currentFilterMatches;
				break;
				case '&&!' :
					if ($currentFilterMatches === TRUE && $filter->hasRuntimeEvaluationsDefinition()) {
						if (!isset($this->runtimeEvaluationsDefinition[$operator])) {
							$this->runtimeEvaluationsDefinition[$operator] = array();
						}
						$this->runtimeEvaluationsDefinition[$operator] = array_merge_recursive($this->runtimeEvaluationsDefinition[$operator], $currentRuntimeEvaluationsDefintion);
						$currentFilterMatches = FALSE;
					}
					if ($this->earlyReturn && $currentFilterMatches) {
						return FALSE;
					}
					$matches = $matches && (!$currentFilterMatches);
				break;
				case '||' :
					if ($currentFilterMatches === TRUE && $filter->hasRuntimeEvaluationsDefinition()) {
						if (!isset($this->runtimeEvaluationsDefinition[$operator])) {
							$this->runtimeEvaluationsDefinition[$operator] = array();
						}
						$this->runtimeEvaluationsDefinition[$operator] = array_merge_recursive($this->runtimeEvaluationsDefinition[$operator], $currentRuntimeEvaluationsDefintion);
					}
					$matches = $matches || $currentFilterMatches;
				break;
				case '||!' :
					if ($currentFilterMatches === TRUE && $filter->hasRuntimeEvaluationsDefinition()) {
						if (!isset($this->runtimeEvaluationsDefinition[$operator])) {
							$this->runtimeEvaluationsDefinition[$operator] = array();
						}
						$this->runtimeEvaluationsDefinition[$operator] = array_merge_recursive($this->runtimeEvaluationsDefinition[$operator], $currentRuntimeEvaluationsDefintion);
						$currentFilterMatches = FALSE;
					}
					$matches = $matches || (!$currentFilterMatches);
				break;
			}
		}

		return $matches;
	}

	/**
	 * Adds a class filter to the composite
	 *
	 * @param string $operator The operator for this filter
	 * @param \TYPO3\FLOW3\Aop\Pointcut\PointcutFilterInterface $filter A configured class filter
	 * @return void
	 */
	public function addFilter($operator, \TYPO3\FLOW3\Aop\Pointcut\PointcutFilterInterface $filter) {
		$this->filters[] = array($operator, $filter);
		if ($operator !== '&&' && $operator !== '&&!') {
			$this->earlyReturn = FALSE;
		}
	}

	/**
	 * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
	 *
	 * @return boolean TRUE if this filter has runtime evaluations
	 */
	public function hasRuntimeEvaluationsDefinition() {
		return count($this->globalRuntimeEvaluationsDefinition) > 0 || count($this->runtimeEvaluationsDefinition) > 0;
	}

	/**
	 * Returns runtime evaluations for the pointcut.
	 *
	 * @return array Runtime evaluations
	 */
	public function getRuntimeEvaluationsDefinition() {
		return array_merge_recursive($this->globalRuntimeEvaluationsDefinition, $this->runtimeEvaluationsDefinition);
	}

	/**
	 * Sets static runtime evaluations for to pointcut, that will be used for every
	 * method this compsite matches
	 *
	 * @param array $runtimeEvaluations Runtime evaluations to be added
	 * @return void
	 */
	public function setGlobalRuntimeEvaluationsDefinition(array $runtimeEvaluations) {
		$this->globalRuntimeEvaluationsDefinition = $runtimeEvaluations;
	}

	/**
	 * Returns the PHP code (closure) that can evaluate the runtime evaluations
	 *
	 * @return string The closure code
	 */
	public function getRuntimeEvaluationsClosureCode() {
		$useGlobalObjects = FALSE;
		$conditionCode = $this->buildRuntimeEvaluationsConditionCode('', $this->getRuntimeEvaluationsDefinition(), $useGlobalObjects);

		if ($conditionCode !== '') {
			$code = "\n\t\t\t\t\t\tfunction(\\TYPO3\\FLOW3\\Aop\\JoinPointInterface \$joinPoint) use (\$objectManager) {\n" .
					"\t\t\t\t\t\t\t\$currentObject = \$joinPoint->getProxy();\n";
			if ($useGlobalObjects) {
				$code .= "\t\t\t\t\t\t\t\$globalObjectNames = \$objectManager->getSettingsByPath(array('TYPO3', 'FLOW3', 'aop', 'globalObjects'));\n";
				$code .= "\t\t\t\t\t\t\t\$globalObjects = array_map(function(\$objectName) use (\$objectManager) { return \$objectManager->get(\$objectName); }, \$globalObjectNames);\n";
			}
			$code .= "\t\t\t\t\t\t\treturn " . $conditionCode . ';' .
					"\n\t\t\t\t\t\t}";
			return $code;
		} else {
			return 'NULL';
		}
	}

	/**
	 * This method is used to optimize the matching process.
	 *
	 * @param \TYPO3\FLOW3\Aop\Builder\ClassNameIndex $classNameIndex
	 * @return \TYPO3\FLOW3\Aop\Builder\ClassNameIndex
	 */
	public function reduceTargetClassNames(\TYPO3\FLOW3\Aop\Builder\ClassNameIndex $classNameIndex) {
		$result = clone $classNameIndex;
		foreach ($this->filters as &$operatorAndFilter) {
			list($operator, $filter) = $operatorAndFilter;

			switch ($operator) {
				case '&&':
					$result->applyIntersect($filter->reduceTargetClassNames($result));
					break;
				case '||':
					$result->applyUnion($filter->reduceTargetClassNames($classNameIndex));
					break;
			}
		}

		return $result;
	}

	/**
	 * Returns the PHP code of the conditions used for runtime evaluations
	 *
	 * @param string $operator The operator for the given condition
	 * @param array $conditions Condition array
	 * @param boolean &$useGlobalObjects Set to TRUE if global objects are used by the condition
	 * @return string The condition code
	 */
	protected function buildRuntimeEvaluationsConditionCode($operator, array $conditions, &$useGlobalObjects = FALSE) {
		$conditionsCode = array();

		if (count($conditions) === 0) {
			return '';
		}

		if (isset($conditions['evaluateConditions']) && is_array($conditions['evaluateConditions'])) {
			$conditionsCode[] = $this->buildGlobalRuntimeEvaluationsConditionCode($conditions['evaluateConditions'], $useGlobalObjects);
			unset($conditions['evaluateConditions']);
		}

		if (isset($conditions['methodArgumentConstraints']) && is_array($conditions['methodArgumentConstraints'])) {
			$conditionsCode[] = $this->buildMethodArgumentsEvaluationConditionCode($conditions['methodArgumentConstraints'], $useGlobalObjects);
			unset($conditions['methodArgumentConstraints']);
		}

		$subConditionsCode = '';
		if (count($conditions) > 1) {
			$isFirst = TRUE;
			foreach ($conditions as $subOperator => $subCondition) {
				$negateCurrentSubCondition = FALSE;
				if ($subOperator === '&&!') {
					$subOperator = '&&';
					$negateCurrentSubCondition = TRUE;
				} elseif ($subOperator === '||!') {
					$subOperator = '||';
					$negateCurrentSubCondition = TRUE;
				}

				$currentSubConditionsCode = $this->buildRuntimeEvaluationsConditionCode($subOperator, $subCondition, $useGlobalObjects);
				if ($negateCurrentSubCondition === TRUE) {
					$currentSubConditionsCode = '(!' . $currentSubConditionsCode . ')';
				}

				$subConditionsCode .= ($isFirst === TRUE ? '(' : ' ' . $subOperator . ' ') . $currentSubConditionsCode;
				$isFirst = FALSE;
			}
			$subConditionsCode .= ')';

			$conditionsCode[] = $subConditionsCode;

		} elseif (count($conditions) === 1) {
			$subOperator = key($conditions);
			$conditionsCode[] = $this->buildRuntimeEvaluationsConditionCode($subOperator, current($conditions), $useGlobalObjects);
		}

		$negateCondition = FALSE;
		if ($operator === '&&!') {
			$operator = '&&';
			$negateCondition = TRUE;
		} elseif ($operator === '||!') {
			$operator = '||';
			$negateCondition = TRUE;
		}

		$resultCode = implode(' ' . $operator . ' ', $conditionsCode);

		if (count($conditionsCode) > 1) {
			$resultCode = '(' . $resultCode . ')';
		}
		if ($negateCondition === TRUE) {
			$resultCode = '(!' . $resultCode . ')';
		}

		return $resultCode;
	}

	/**
	 * Returns the PHP code of the conditions used argument runtime evaluations
	 *
	 * @param array $conditions Condition array
	 * @param boolean &$useGlobalObjects Set to TRUE if global objects are used by the condition
	 * @return string The arguments condition code
	 */
	protected function buildMethodArgumentsEvaluationConditionCode(array $conditions, &$useGlobalObjects = FALSE) {
		$argumentConstraintsConditionsCode = '';

		$isFirst = TRUE;
		foreach ($conditions as $argumentName => $argumentConstraint) {

			$objectAccess = explode('.', $argumentName, 2);
			if (count($objectAccess) === 2) {
				$leftValue = '\TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($joinPoint->getMethodArgument(\'' . $objectAccess[0] . '\'), \'' . $objectAccess[1] . '\')';
			} else {
				$leftValue = '$joinPoint->getMethodArgument(\'' . $argumentName . '\')';
			}

			for ($i = 0; $i < count($argumentConstraint['operator']); $i++) {
				$rightValue = $this->buildArgumentEvaluationAccessCode($argumentConstraint['value'][$i], $useGlobalObjects);

				if ($argumentConstraint['operator'][$i] === 'in') {
					$argumentConstraintsConditionsCode .= ($isFirst === TRUE ? '(' : ' && ') . '(' . $rightValue . ' instanceof \SplObjectStorage || ' . $rightValue . ' instanceof \Doctrine\Common\Collections\Collection ? ' . $leftValue . ' !== NULL && ' . $rightValue . '->contains(' . $leftValue  . ') : in_array(' . $leftValue . ', ' . $rightValue . '))';
				} elseif ($argumentConstraint['operator'][$i] === 'contains') {
					$argumentConstraintsConditionsCode .= ($isFirst === TRUE ? '(' : ' && ') . '(' . $leftValue . ' instanceof \SplObjectStorage || ' . $leftValue . ' instanceof \Doctrine\Common\Collections\Collection ? ' . $rightValue . ' !== NULL && ' . $leftValue . '->contains(' . $rightValue  . ') : in_array(' . $rightValue . ', ' . $leftValue . '))';
				} elseif ($argumentConstraint['operator'][$i] === 'matches') {
					$argumentConstraintsConditionsCode .= ($isFirst === TRUE ? '(' : ' && ') . '(!empty(array_intersect(' . $leftValue . ', ' . $rightValue . ')))';
				} else {
					$argumentConstraintsConditionsCode .= ($isFirst === TRUE ? '(' : ' && ') . $leftValue . ' ' . $argumentConstraint['operator'][$i] . ' ' . $rightValue;
				}

				$isFirst = FALSE;
			}
		}

		return $argumentConstraintsConditionsCode . ')';
	}

	/**
	 * Returns the PHP code of the conditions used for global runtime evaluations
	 *
	 * @param array $conditions Condition array
	 * @param boolean &$useGlobalObjects Set to TRUE if global objects are used by the condition
	 * @return string The condition code
	 */
	protected function buildGlobalRuntimeEvaluationsConditionCode(array $conditions, &$useGlobalObjects = FALSE) {
		$evaluateConditionsCode = '';

		$isFirst = TRUE;
		foreach ($conditions as $constraint) {
			$leftValue = $this->buildArgumentEvaluationAccessCode($constraint['leftValue'], $useGlobalObjects);
			$rightValue = $this->buildArgumentEvaluationAccessCode($constraint['rightValue'], $useGlobalObjects);

			if ($constraint['operator'] === 'in') {
				$evaluateConditionsCode .= ($isFirst === TRUE ? '(' : ' && ') . '(' . $rightValue . ' instanceof \SplObjectStorage || ' . $rightValue . ' instanceof \Doctrine\Common\Collections\Collection ? ' . $leftValue . ' !== NULL && ' . $rightValue . '->contains(' . $leftValue  . ') : in_array(' . $leftValue . ', ' . $rightValue . '))';
			} elseif ($constraint['operator'] === 'contains') {
				$evaluateConditionsCode .= ($isFirst === TRUE ? '(' : ' && ') . '(' . $leftValue . ' instanceof \SplObjectStorage || ' . $leftValue . ' instanceof \Doctrine\Common\Collections\Collection ? ' . $rightValue . ' !== NULL && ' . $leftValue . '->contains(' . $rightValue  . ') : in_array(' . $rightValue . ', ' . $leftValue . '))';
			} elseif ($constraint['operator'] === 'matches') {
				$evaluateConditionsCode .= ($isFirst === TRUE ? '(' : ' && ') . '(!empty(array_intersect(' . $leftValue . ', ' . $rightValue . ')))';
			} else {
				$evaluateConditionsCode .= ($isFirst === TRUE ? '(' : ' && ') . $leftValue . ' ' . $constraint['operator'] . ' ' . $rightValue;
			}

			$isFirst = FALSE;
		}

		return $evaluateConditionsCode . ')';
	}

	/**
	 * Returns the PHP code used to access one argument of a runtime evaluation
	 *
	 * @param mixed $argumentAccess The unparsed argument access, might be string or array
	 * @param boolean &$useGlobalObjects Set to TRUE if global objects are used by the condition
	 * @return string The condition code
	 */
	protected function buildArgumentEvaluationAccessCode($argumentAccess, &$useGlobalObjects = FALSE) {
		if (is_array($argumentAccess)) {
			$valuesAccessCodes = array();
			foreach ($argumentAccess as $singleValue) {
				$valuesAccessCodes[] = $this->buildArgumentEvaluationAccessCode($singleValue);
			}
			$argumentAccessCode = 'array(' . implode(', ', $valuesAccessCodes) . ')';

		} else {
			$objectAccess = explode('.', $argumentAccess, 2);
			if (count($objectAccess) === 2 && $objectAccess[0] === 'current') {
				$objectAccess = explode('.', $objectAccess[1], 2);
				if (count($objectAccess) === 1) {
					$argumentAccessCode = '$globalObjects[\'' . $objectAccess[0] . '\']';
				} else {
					$argumentAccessCode = '\TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($globalObjects[\'' . $objectAccess[0] . '\'], \'' . $objectAccess[1] . '\')';
				}

				$useGlobalObjects = TRUE;
			} elseif (count($objectAccess) === 2 && $objectAccess[0] === 'this') {
				$argumentAccessCode = '\TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($currentObject, \'' . $objectAccess[1] . '\')';
			} else {
				$argumentAccessCode = $argumentAccess;
			}
		}

		return $argumentAccessCode;
	}

}
?>