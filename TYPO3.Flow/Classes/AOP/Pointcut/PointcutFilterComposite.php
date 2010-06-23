<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP\Pointcut;

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
 * This composite allows to check for match against a row pointcut filters
 * by only one method call. All registered filters will be invoked and if one filter
 * doesn't match, the overall result is "no".
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @see \F3\FLOW3\AOP\Pointcut\PointcutExpressionParser, \F3\FLOW3\AOP\Pointcut\PointcutClassNameFilter, \F3\FLOW3\AOP\Pointcut\PointcutMethodFilter
 * @scope prototype
 */
class PointcutFilterComposite implements \F3\FLOW3\AOP\Pointcut\PointcutFilterInterface {

	/**
	 * @var array An array of \F3\FLOW3\AOP\Pointcut\Pointcut*Filter objects
	 */
	protected $filters = array();

		/**
	 * @var array An array of runtime evaluations
	 */
	protected $runtimeEvaluationsDefinition = array();


	/**
	 * @var array An array of global runtime evaluations
	 */
	protected $globalRuntimeEvaluationsDefinition = array();

	/**
	 * An array of global objects, to be access for dynamich runtime evaluations
	 * @var array
	 */
	protected $globalObjects = array();

	/**
	 * Inject global settings, used to retrieve registered global objects
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSettings(array $settings) {
		$this->globalObjects = $settings['aop']['globalObjects'];
	}

	/**
	 * Checks if the specified class and method match the registered class-
	 * and method filter patterns.
	 *
	 * @param string $className Name of the class to check against
	 * @param string $methodName Name of the method to check against
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if class and method match the pattern, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		$this->runtimeEvaluationsDefinition = array();
		$matches = TRUE;
		foreach ($this->filters as $operatorAndFilter) {
			list($operator, $filter) = $operatorAndFilter;

			$currentFilterMatches = $filter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
			$currentRuntimeEvaluationsDefintion = $filter->getRuntimeEvaluationsDefinition();

			switch ($operator) {
				case '&&' :
					if ($currentFilterMatches === TRUE && $filter->hasRuntimeEvaluationsDefinition()) {
						if (!isset($this->runtimeEvaluationsDefinition[$operator])) $this->runtimeEvaluationsDefinition[$operator] = array();
						$this->runtimeEvaluationsDefinition[$operator] = array_merge_recursive($this->runtimeEvaluationsDefinition[$operator], $currentRuntimeEvaluationsDefintion);
					}
					$matches = $matches && $currentFilterMatches;
				break;
				case '&&!' :
					if ($currentFilterMatches === TRUE && $filter->hasRuntimeEvaluationsDefinition()) {
						if (!isset($this->runtimeEvaluationsDefinition[$operator])) $this->runtimeEvaluationsDefinition[$operator] = array();
						$this->runtimeEvaluationsDefinition[$operator] = array_merge_recursive($this->runtimeEvaluationsDefinition[$operator], $currentRuntimeEvaluationsDefintion);
						$currentFilterMatches = FALSE;
					}
					$matches = $matches && (!$currentFilterMatches);
				break;
				case '||' :
					if ($currentFilterMatches === TRUE && $filter->hasRuntimeEvaluationsDefinition()) {
						if (!isset($this->runtimeEvaluationsDefinition[$operator])) $this->runtimeEvaluationsDefinition[$operator] = array();
						$this->runtimeEvaluationsDefinition[$operator] = array_merge_recursive($this->runtimeEvaluationsDefinition[$operator], $currentRuntimeEvaluationsDefintion);
					}
					$matches = $matches || $currentFilterMatches;
				break;
				case '||!' :
					if ($currentFilterMatches === TRUE && $filter->hasRuntimeEvaluationsDefinition()) {
						if (!isset($this->runtimeEvaluationsDefinition[$operator])) $this->runtimeEvaluationsDefinition[$operator] = array();
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
	 * @param \F3\FLOW3\AOP\Pointcut\PointcutFilterInterface $filter A configured class filter
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addFilter($operator, \F3\FLOW3\AOP\Pointcut\PointcutFilterInterface $filter) {
		$this->filters[] = array($operator, $filter);
	}

	/**
	 * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
	 *
	 * @return boolean TRUE if this filter has runtime evaluations
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRuntimeEvaluationsDefinition() {
		return (count($this->runtimeEvaluationsDefinition) > 0);
	}

	/**
	 * Returns runtime evaluations for the pointcut.
	 *
	 * @return array Runtime evaluations
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setGlobalRuntimeEvaluationsDefinition(array $runtimeEvaluations) {
		$this->globalRuntimeEvaluationsDefinition = $runtimeEvaluations;
	}

	/**
	 * Returns the PHP code (closure) that can evaluate the runtime evaluations
	 *
	 * @return string The closure code
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRuntimeEvaluationsClosureCode() {
		$globalObjects = array();
		$conditionCode = $this->buildRuntimeEvaluationsConditionCode('', $this->getRuntimeEvaluationsDefinition(), $globalObjects);

		if ($conditionCode !== '') {
			return "\n\t\t\t\t\t\tfunction(\\F3\\FLOW3\\AOP\\JoinPointInterface \$joinPoint) use (\$objectManager) {\n" .
					"\t\t\t\t\t\t\t\$currentObject = \$joinPoint->getProxy();\n" .
					"\t\t\t\t\t\t\t" . implode("\n\t\t\t\t\t\t\t", $globalObjects) .
					"\n\t\t\t\t\t\t\treturn " . $conditionCode . ';' .
					"\n\t\t\t\t\t\t}";
		} else {
			return 'NULL';
		}
	}

	/**
	 * Returns the PHP code of the conditions used for runtime evaluations
	 *
	 * @param string $operator The operator for the given condition
	 * @param array $conditions Condition array
	 * @param array $globalObjects An array of code that instantiates all global objects needed in the condition code
	 * @return string The condition code
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function buildRuntimeEvaluationsConditionCode($operator, array $conditions, array &$globalObjects = array()) {
		$conditionsCode = array();

		if (count($conditions) === 0) return '';

		if (isset($conditions['evaluateConditions']) && is_array($conditions['evaluateConditions'])) {
			$conditionsCode[] = $this->buildGlobalRuntimeEvaluationsConditionCode($conditions['evaluateConditions'], $globalObjects);
			unset($conditions['evaluateConditions']);
		}

		if (isset($conditions['methodArgumentConstraints']) && is_array($conditions['methodArgumentConstraints'])) {
			$conditionsCode[] = $this->buildMethodArgumentsEvaluationConditionCode($conditions['methodArgumentConstraints'], $globalObjects);
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
				} else if ($subOperator === '||!') {
					$subOperator = '||';
					$negateCurrentSubCondition = TRUE;
				}

				$currentSubConditionsCode = $this->buildRuntimeEvaluationsConditionCode($subOperator, $subCondition, $globalObjects);
				if ($negateCurrentSubCondition === TRUE) $currentSubConditionsCode = '(!' . $currentSubConditionsCode . ')';

				$subConditionsCode .= ($isFirst === TRUE ? '(' : ' ' . $subOperator . ' ') . $currentSubConditionsCode;
				$isFirst = FALSE;
			}
			$subConditionsCode .= ')';

			$conditionsCode[] = $subConditionsCode;

		} else if (count($conditions) === 1) {
			$subOperator = key($conditions);
			$conditionsCode[] = $this->buildRuntimeEvaluationsConditionCode($subOperator, current($conditions), $globalObjects);
		}

		$negateCondition = FALSE;
		if ($operator === '&&!') {
			$operator = '&&';
			$negateCondition = TRUE;
		} else if ($operator === '||!') {
			$operator = '||';
			$negateCondition = TRUE;
		}

		$resultCode = implode(' ' . $operator . ' ', $conditionsCode);

		if (count($conditionsCode) > 1) $resultCode = '(' . $resultCode . ')';
		if ($negateCondition === TRUE) $resultCode = '(!' . $resultCode . ')';

		return $resultCode;
	}

	/**
	 * Returns the PHP code of the conditions used argument runtime evaluations
	 *
	 * @param array $conditions Condition array
	 * @param array $globalObjects An array of code that instantiates all global objects needed in the condition code
	 * @return string The arguments condition code
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function buildMethodArgumentsEvaluationConditionCode(array $conditions, array &$globalObjects = array()) {
		$argumentConstraintsConditionsCode = '';

		$isFirst = TRUE;
		foreach ($conditions as $argumentName => $argumentConstraint) {

			$objectAccess = explode('.', $argumentName, 2);
			if (count($objectAccess) === 2) {
				$leftValue = 'F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($joinPoint->getMethodArgument(\'' . $objectAccess[0] . '\'), \'' . $objectAccess[1] . '\')';
			} else {
				$leftValue = '$joinPoint->getMethodArgument(\'' . $argumentName . '\')';
			}

			for ($i = 0; $i < count($argumentConstraint['operator']); $i++) {
				$rightValue = $this->buildArgumentEvaluationAccessCode($argumentConstraint['value'][$i], $globalObjects);

				if ($argumentConstraint['operator'][$i] === 'in') {
					$argumentConstraintsConditionsCode .= ($isFirst === TRUE ? '(' : ' && ') . 'in_array(' . $leftValue . ', ' . $rightValue . ')';
				} else if ($argumentConstraint['operator'][$i] === 'matches') {
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
	 * @param array $globalObjects An array of code that instantiates all global objects needed in the condition code
	 * @return string The condition code
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function buildGlobalRuntimeEvaluationsConditionCode(array $conditions, array &$globalObjects = array()) {
		$evaluateConditionsCode = '';

		$isFirst = TRUE;
		foreach ($conditions as $constraint) {
			$leftValue = $this->buildArgumentEvaluationAccessCode($constraint['leftValue'], $globalObjects);
			$rightValue = $this->buildArgumentEvaluationAccessCode($constraint['rightValue'], $globalObjects);

			if ($constraint['operator'] === 'in') {
				$evaluateConditionsCode .= ($isFirst === TRUE ? '(' : ' && ') . 'in_array(' . $leftValue . ', ' . $rightValue . ')';
			} else if ($constraint['operator'] === 'matches') {
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
	 * @param array $globalObjects An array of code that instantiates all global objects needed in the condition code
	 * @return string The condition code
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function buildArgumentEvaluationAccessCode($argumentAccess, array &$globalObjects = array()) {
		$argumentAccessCode = '';

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
				$argumentAccessCode = 'F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($' . $objectAccess[0] . ', \'' . $objectAccess[1] . '\')';

				if (array_key_exists($objectAccess[0], $this->globalObjects)) $globalObjects[$objectAccess[0]] = $this->globalObjects[$objectAccess[0]];

			} else if (count($objectAccess) === 2 && $objectAccess[0] === 'this') {
				$argumentAccessCode = 'F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($currentObject, \'' . $objectAccess[1] . '\')';
			} else {
				$argumentAccessCode = $argumentAccess;
			}
		}

		return $argumentAccessCode;
	}
}
?>