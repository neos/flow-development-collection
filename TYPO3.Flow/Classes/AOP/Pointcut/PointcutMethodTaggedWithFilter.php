<?php
namespace TYPO3\FLOW3\AOP\Pointcut;

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
 * A method filter which fires on methods tagged with a certain annotation
 *
 * @FLOW3\Proxy(false)
 * @deprecated since 1.0
 */
class PointcutMethodTaggedWithFilter implements \TYPO3\FLOW3\AOP\Pointcut\PointcutFilterInterface {

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var string A regular expression to match annotations
	 */
	protected $methodTagFilterExpression;

	/**
	 * The constructor - initializes the method tag filter with the method tag filter expression
	 *
	 * @param string $methodTagFilterExpression A regular expression which defines which method tags should match
	 */
	public function __construct($methodTagFilterExpression) {
		$this->methodTagFilterExpression = $methodTagFilterExpression;
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
	 * Checks if the specified method matches with the method tag filter pattern
	 *
	 * @param string $className Name of the class to check against - not used here
	 * @param string $methodName Name of the method
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection - not used here
	 * @return boolean TRUE if the class matches, otherwise FALSE
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		if ($methodDeclaringClassName === NULL || !method_exists($methodDeclaringClassName, $methodName)) {
			return FALSE;
		}
		foreach ($this->reflectionService->getMethodTagsValues($methodDeclaringClassName, $methodName) as $tag => $values) {
			$matchResult = preg_match('/^' . $this->methodTagFilterExpression . '$/i', $tag);
			if ($matchResult === FALSE) {
				throw new \TYPO3\FLOW3\AOP\Exception('Error in regular expression "' . $this->methodTagFilterExpression . '" in pointcut method tag filter', 1229343988);
			}
			if ($matchResult === 1) {
				return TRUE;
			}
		}
		return FALSE;
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
	 * Returns runtime evaluations for the pointcut.
	 *
	 * @return array Runtime evaluations
	 */
	public function getRuntimeEvaluationsDefinition() {
		return array();
	}

	/**
	 * This method is used to optimize the matching process.
	 *
	 * @param \TYPO3\FLOW3\AOP\Builder\ClassNameIndex $classNameIndex
	 * @return \TYPO3\FLOW3\AOP\Builder\ClassNameIndex
	 */
	public function reduceTargetClassNames(\TYPO3\FLOW3\AOP\Builder\ClassNameIndex $classNameIndex) {
		return $classNameIndex;
	}
}
?>