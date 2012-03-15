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
 * A little filter which filters for method names
 *
 * @FLOW3\Proxy(false)
 */
class PointcutMethodNameFilter implements \TYPO3\FLOW3\Aop\Pointcut\PointcutFilterInterface {

	const PATTERN_MATCHVISIBILITYMODIFIER = '/^(|public|protected)$/';

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var string The method name filter expression
	 */
	protected $methodNameFilterExpression;

	/**
	 * @var string The method visibility
	 */
	protected $methodVisibility = NULL;

	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var array Array with constraints for method arguments
	 */
	protected $methodArgumentConstraints = array();

	/**
	 * Constructor - initializes the filter with the name filter pattern
	 *
	 * @param string $methodNameFilterExpression A regular expression which filters method names
	 * @param string $methodVisibility The method visibility modifier (public, protected or private). Specifiy NULL if you don't care.
	 * @param array $methodArgumentConstraints array of method constraints
	 * @throws \TYPO3\FLOW3\Aop\Exception\InvalidPointcutExpressionException
	 */
	public function __construct($methodNameFilterExpression, $methodVisibility = NULL, array $methodArgumentConstraints = array()) {
		$this->methodNameFilterExpression = $methodNameFilterExpression;
		if (preg_match(self::PATTERN_MATCHVISIBILITYMODIFIER, $methodVisibility) !== 1) {
			throw new \TYPO3\FLOW3\Aop\Exception\InvalidPointcutExpressionException('Invalid method visibility modifier "' . $methodVisibility . '".', 1172494794);
		}
		$this->methodVisibility = $methodVisibility;
		$this->methodArgumentConstraints = $methodArgumentConstraints;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param \TYPO3\FLOW3\Reflection\ReflectionService $reflectionService The reflection service
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Checks if the specified method matches against the method name
	 * expression.
	 *
	 * Returns TRUE if method name, visibility and arguments constraints match and the target
	 * method is not final.
	 *
	 * @param string $className Ignored in this pointcut filter
	 * @param string $methodName Name of the method to match agains
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class matches, otherwise FALSE
	 * @throws \TYPO3\FLOW3\Aop\Exception
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		$matchResult = preg_match('/^' . $this->methodNameFilterExpression . '$/', $methodName);

		if ($matchResult === FALSE) {
			throw new \TYPO3\FLOW3\Aop\Exception('Error in regular expression', 1168876915);
		} elseif ($matchResult !== 1) {
			return FALSE;
		}

		switch ($this->methodVisibility) {
			case 'public' :
				if (!($methodDeclaringClassName !== NULL && $this->reflectionService->isMethodPublic($methodDeclaringClassName, $methodName))) {
					return FALSE;
				}
				break;
			case 'protected' :
				if (!($methodDeclaringClassName !== NULL && $this->reflectionService->isMethodProtected($methodDeclaringClassName, $methodName))) {
					return FALSE;
				}
				break;
		}

		if ($methodDeclaringClassName !== NULL && $this->reflectionService->isMethodFinal($methodDeclaringClassName, $methodName)) {
			return FALSE;
		}

		$methodArguments = ($methodDeclaringClassName === NULL ? array() : $this->reflectionService->getMethodParameters($methodDeclaringClassName, $methodName));
		foreach (array_keys($this->methodArgumentConstraints) as $argumentName) {
			$objectAccess = explode('.', $argumentName, 2);
			$argumentName = $objectAccess[0];
			if (!array_key_exists($argumentName, $methodArguments)) {
				$this->systemLogger->log('The argument "' . $argumentName . '" declared in pointcut does not exist in method ' . $methodDeclaringClassName . '->' . $methodName, LOG_NOTICE);
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
		return (count($this->methodArgumentConstraints) > 0);
	}

	/**
	 * Returns runtime evaluations for a previously matched pointcut
	 *
	 * @return array Runtime evaluations
	 */
	public function getRuntimeEvaluationsDefinition() {
		return array(
			'methodArgumentConstraints' => $this->methodArgumentConstraints
		);
	}

	/**
	 * Returns the method name filter expression
	 *
	 * @return string
	 */
	public function getMethodNameFilterExpression() {
		return $this->methodNameFilterExpression;
	}

	/**
	 * Returns the method visibility
	 *
	 * @return string
	 */
	public function getMethodVisibility() {
		return $this->methodVisibility;
	}

	/**
	 * Returns the method argument constraints
	 *
	 * @return array
	 */
	public function getMethodArgumentConstraints() {
		return $this->methodArgumentConstraints;
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