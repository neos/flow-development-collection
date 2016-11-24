<?php
namespace Neos\Flow\Aop\Pointcut;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\Builder\ClassNameIndex;

/**
 * This composite allows to check for match against a row pointcut filters
 * by only one method call. All registered filters will be invoked and if one filter
 * doesn't match, the overall result is "no".
 *
 * @see \Neos\Flow\Aop\Pointcut\PointcutExpressionParser, \Neos\Flow\Aop\Pointcut\PointcutClassNameFilter, \Neos\Flow\Aop\Pointcut\PointcutMethodFilter
 * @Flow\Proxy(false)
 */
class PointcutFilterComposite implements PointcutFilterInterface
{
    /**
     * @var array An array of \Neos\Flow\Aop\Pointcut\Pointcut*Filter objects
     */
    protected $filters = [];

    /**
     * @var boolean
     */
    protected $earlyReturn = true;

    /**
     * @var array An array of runtime evaluations
     */
    protected $runtimeEvaluationsDefinition = [];

    /**
     * @var array An array of global runtime evaluations
     */
    protected $globalRuntimeEvaluationsDefinition = [];

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
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)
    {
        $this->runtimeEvaluationsDefinition = [];
        $matches = true;
        foreach ($this->filters as &$operatorAndFilter) {
            list($operator, $filter) = $operatorAndFilter;

            $currentFilterMatches = $filter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
            $currentRuntimeEvaluationsDefinition = $filter->getRuntimeEvaluationsDefinition();

            switch ($operator) {
                case '&&':
                    if ($currentFilterMatches === true && $filter->hasRuntimeEvaluationsDefinition()) {
                        if (!isset($this->runtimeEvaluationsDefinition[$operator])) {
                            $this->runtimeEvaluationsDefinition[$operator] = [];
                        }
                        $this->runtimeEvaluationsDefinition[$operator] = array_merge_recursive($this->runtimeEvaluationsDefinition[$operator], $currentRuntimeEvaluationsDefinition);
                    }
                    if ($this->earlyReturn && !$currentFilterMatches) {
                        return false;
                    }
                    $matches = $matches && $currentFilterMatches;
                break;
                case '&&!':
                    if ($currentFilterMatches === true && $filter->hasRuntimeEvaluationsDefinition()) {
                        if (!isset($this->runtimeEvaluationsDefinition[$operator])) {
                            $this->runtimeEvaluationsDefinition[$operator] = [];
                        }
                        $this->runtimeEvaluationsDefinition[$operator] = array_merge_recursive($this->runtimeEvaluationsDefinition[$operator], $currentRuntimeEvaluationsDefinition);
                        $currentFilterMatches = false;
                    }
                    if ($this->earlyReturn && $currentFilterMatches) {
                        return false;
                    }
                    $matches = $matches && (!$currentFilterMatches);
                break;
                case '||':
                    if ($currentFilterMatches === true && $filter->hasRuntimeEvaluationsDefinition()) {
                        if (!isset($this->runtimeEvaluationsDefinition[$operator])) {
                            $this->runtimeEvaluationsDefinition[$operator] = [];
                        }
                        $this->runtimeEvaluationsDefinition[$operator] = array_merge_recursive($this->runtimeEvaluationsDefinition[$operator], $currentRuntimeEvaluationsDefinition);
                    }
                    $matches = $matches || $currentFilterMatches;
                break;
                case '||!':
                    if ($currentFilterMatches === true && $filter->hasRuntimeEvaluationsDefinition()) {
                        if (!isset($this->runtimeEvaluationsDefinition[$operator])) {
                            $this->runtimeEvaluationsDefinition[$operator] = [];
                        }
                        $this->runtimeEvaluationsDefinition[$operator] = array_merge_recursive($this->runtimeEvaluationsDefinition[$operator], $currentRuntimeEvaluationsDefinition);
                        $currentFilterMatches = false;
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
     * @param PointcutFilterInterface $filter A configured class filter
     * @return void
     */
    public function addFilter($operator, PointcutFilterInterface $filter)
    {
        $this->filters[] = [$operator, $filter];
        if ($operator !== '&&' && $operator !== '&&!') {
            $this->earlyReturn = false;
        }
    }

    /**
     * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
     *
     * @return boolean TRUE if this filter has runtime evaluations
     */
    public function hasRuntimeEvaluationsDefinition()
    {
        return count($this->globalRuntimeEvaluationsDefinition) > 0 || count($this->runtimeEvaluationsDefinition) > 0;
    }

    /**
     * Returns runtime evaluations for the pointcut.
     *
     * @return array Runtime evaluations
     */
    public function getRuntimeEvaluationsDefinition()
    {
        return array_merge_recursive($this->globalRuntimeEvaluationsDefinition, $this->runtimeEvaluationsDefinition);
    }

    /**
     * Sets static runtime evaluations for to pointcut, that will be used for every
     * method this composite matches
     *
     * @param array $runtimeEvaluations Runtime evaluations to be added
     * @return void
     */
    public function setGlobalRuntimeEvaluationsDefinition(array $runtimeEvaluations)
    {
        $this->globalRuntimeEvaluationsDefinition = $runtimeEvaluations;
    }

    /**
     * Returns the PHP code (closure) that can evaluate the runtime evaluations
     *
     * @return string The closure code
     */
    public function getRuntimeEvaluationsClosureCode()
    {
        $useGlobalObjects = false;
        $conditionCode = $this->buildRuntimeEvaluationsConditionCode('', $this->getRuntimeEvaluationsDefinition(), $useGlobalObjects);

        if ($conditionCode !== '') {
            $code = "\n\t\t\t\t\t\tfunction(\\Neos\\Flow\\Aop\\JoinPointInterface \$joinPoint, \$objectManager) {\n" .
                    "\t\t\t\t\t\t\t\$currentObject = \$joinPoint->getProxy();\n";
            if ($useGlobalObjects) {
                $code .= "\t\t\t\t\t\t\t\$globalObjectNames = \$objectManager->getSettingsByPath(array('Neos', 'Flow', 'aop', 'globalObjects'));\n";
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
     * @param ClassNameIndex $classNameIndex
     * @return ClassNameIndex
     */
    public function reduceTargetClassNames(ClassNameIndex $classNameIndex)
    {
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
    protected function buildRuntimeEvaluationsConditionCode($operator, array $conditions, &$useGlobalObjects = false)
    {
        $conditionsCode = [];

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
            $isFirst = true;
            foreach ($conditions as $subOperator => $subCondition) {
                $negateCurrentSubCondition = false;
                if ($subOperator === '&&!') {
                    $subOperator = '&&';
                    $negateCurrentSubCondition = true;
                } elseif ($subOperator === '||!') {
                    $subOperator = '||';
                    $negateCurrentSubCondition = true;
                }

                $currentSubConditionsCode = $this->buildRuntimeEvaluationsConditionCode($subOperator, $subCondition, $useGlobalObjects);
                if ($negateCurrentSubCondition === true) {
                    $currentSubConditionsCode = '(!' . $currentSubConditionsCode . ')';
                }

                $subConditionsCode .= ($isFirst === true ? '(' : ' ' . $subOperator . ' ') . $currentSubConditionsCode;
                $isFirst = false;
            }
            $subConditionsCode .= ')';

            $conditionsCode[] = $subConditionsCode;
        } elseif (count($conditions) === 1) {
            $subOperator = key($conditions);
            $conditionsCode[] = $this->buildRuntimeEvaluationsConditionCode($subOperator, current($conditions), $useGlobalObjects);
        }

        $negateCondition = false;
        if ($operator === '&&!') {
            $operator = '&&';
            $negateCondition = true;
        } elseif ($operator === '||!') {
            $operator = '||';
            $negateCondition = true;
        }

        $resultCode = implode(' ' . $operator . ' ', $conditionsCode);

        if (count($conditionsCode) > 1) {
            $resultCode = '(' . $resultCode . ')';
        }
        if ($negateCondition === true) {
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
    protected function buildMethodArgumentsEvaluationConditionCode(array $conditions, &$useGlobalObjects = false)
    {
        $argumentConstraintsConditionsCode = '';

        $isFirst = true;
        foreach ($conditions as $argumentName => $argumentConstraint) {
            $objectAccess = explode('.', $argumentName, 2);
            if (count($objectAccess) === 2) {
                $leftValue = '\Neos\Utility\ObjectAccess::getPropertyPath($joinPoint->getMethodArgument(\'' . $objectAccess[0] . '\'), \'' . $objectAccess[1] . '\')';
            } else {
                $leftValue = '$joinPoint->getMethodArgument(\'' . $argumentName . '\')';
            }

            for ($i = 0; $i < count($argumentConstraint['operator']); $i++) {
                $rightValue = $this->buildArgumentEvaluationAccessCode($argumentConstraint['value'][$i], $useGlobalObjects);

                if ($argumentConstraint['operator'][$i] === 'in') {
                    $argumentConstraintsConditionsCode .= ($isFirst === true ? '(' : ' && ') . '(' . $rightValue . ' instanceof \SplObjectStorage || ' . $rightValue . ' instanceof \Doctrine\Common\Collections\Collection ? ' . $leftValue . ' !== NULL && ' . $rightValue . '->contains(' . $leftValue  . ') : in_array(' . $leftValue . ', ' . $rightValue . '))';
                } elseif ($argumentConstraint['operator'][$i] === 'contains') {
                    $argumentConstraintsConditionsCode .= ($isFirst === true ? '(' : ' && ') . '(' . $leftValue . ' instanceof \SplObjectStorage || ' . $leftValue . ' instanceof \Doctrine\Common\Collections\Collection ? ' . $rightValue . ' !== NULL && ' . $leftValue . '->contains(' . $rightValue  . ') : in_array(' . $rightValue . ', ' . $leftValue . '))';
                } elseif ($argumentConstraint['operator'][$i] === 'matches') {
                    $argumentConstraintsConditionsCode .= ($isFirst === true ? '(' : ' && ') . '(!empty(array_intersect(' . $leftValue . ', ' . $rightValue . ')))';
                } else {
                    $argumentConstraintsConditionsCode .= ($isFirst === true ? '(' : ' && ') . $leftValue . ' ' . $argumentConstraint['operator'][$i] . ' ' . $rightValue;
                }

                $isFirst = false;
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
    protected function buildGlobalRuntimeEvaluationsConditionCode(array $conditions, &$useGlobalObjects = false)
    {
        $evaluateConditionsCode = '';

        $isFirst = true;
        foreach ($conditions as $constraint) {
            $leftValue = $this->buildArgumentEvaluationAccessCode($constraint['leftValue'], $useGlobalObjects);
            $rightValue = $this->buildArgumentEvaluationAccessCode($constraint['rightValue'], $useGlobalObjects);

            if ($constraint['operator'] === 'in') {
                $evaluateConditionsCode .= ($isFirst === true ? '(' : ' && ') . '(' . $rightValue . ' instanceof \SplObjectStorage || ' . $rightValue . ' instanceof \Doctrine\Common\Collections\Collection ? ' . $leftValue . ' !== NULL && ' . $rightValue . '->contains(' . $leftValue  . ') : in_array(' . $leftValue . ', ' . $rightValue . '))';
            } elseif ($constraint['operator'] === 'contains') {
                $evaluateConditionsCode .= ($isFirst === true ? '(' : ' && ') . '(' . $leftValue . ' instanceof \SplObjectStorage || ' . $leftValue . ' instanceof \Doctrine\Common\Collections\Collection ? ' . $rightValue . ' !== NULL && ' . $leftValue . '->contains(' . $rightValue  . ') : in_array(' . $rightValue . ', ' . $leftValue . '))';
            } elseif ($constraint['operator'] === 'matches') {
                $evaluateConditionsCode .= ($isFirst === true ? '(' : ' && ') . '(!empty(array_intersect(' . $leftValue . ', ' . $rightValue . ')))';
            } else {
                $evaluateConditionsCode .= ($isFirst === true ? '(' : ' && ') . $leftValue . ' ' . $constraint['operator'] . ' ' . $rightValue;
            }

            $isFirst = false;
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
    protected function buildArgumentEvaluationAccessCode($argumentAccess, &$useGlobalObjects = false)
    {
        if (is_array($argumentAccess)) {
            $valuesAccessCodes = [];
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
                    $argumentAccessCode = '\Neos\Utility\ObjectAccess::getPropertyPath($globalObjects[\'' . $objectAccess[0] . '\'], \'' . $objectAccess[1] . '\')';
                }

                $useGlobalObjects = true;
            } elseif (count($objectAccess) === 2 && $objectAccess[0] === 'this') {
                $argumentAccessCode = '\Neos\Utility\ObjectAccess::getPropertyPath($currentObject, \'' . $objectAccess[1] . '\')';
            } else {
                $argumentAccessCode = $argumentAccess;
            }
        }

        return $argumentAccessCode;
    }
}
