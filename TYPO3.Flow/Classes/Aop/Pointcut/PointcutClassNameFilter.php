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
 * A simple class filter which fires on class names defined by a regular expression
 *
 * @FLOW3\Proxy(false)
 */
class PointcutClassNameFilter implements \TYPO3\FLOW3\Aop\Pointcut\PointcutFilterInterface {

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * A regular expression to match class names
	 * @var string
	 */
	protected $classFilterExpression;

	/**
	 * @var string
	 */
	protected $originalExpressionString;

	/**
	 * The constructor - initializes the class filter with the class filter expression
	 *
	 * @param string $classFilterExpression A regular expression which defines which class names should match
	 */
	public function __construct($classFilterExpression) {
		$this->classFilterExpression = '/^' . str_replace('\\', '\\\\', $classFilterExpression) . '$/';
		$this->originalExpressionString = $classFilterExpression;
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
	 * Checks if the specified class matches with the class filter pattern
	 *
	 * @param string $className Name of the class to check against
	 * @param string $methodName Name of the method - not used here
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in - not used here
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class matches, otherwise FALSE
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		try {
			$matchResult = preg_match($this->classFilterExpression, $className);
		}
		catch (\Exception $exception) {
			throw new \TYPO3\FLOW3\Aop\Exception('Error in regular expression "' . $this->classFilterExpression . '" in pointcut class filter', 1292324509, $exception);
		}
		if ($matchResult === FALSE) {
			throw new \TYPO3\FLOW3\Aop\Exception('Error in regular expression "' . $this->classFilterExpression . '" in pointcut class filter', 1168876955);
		}
		return ($matchResult === 1);
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
	 * @param \TYPO3\FLOW3\Aop\Builder\ClassNameIndex $classNameIndex
	 * @return \TYPO3\FLOW3\Aop\Builder\ClassNameIndex
	 */
	public function reduceTargetClassNames(\TYPO3\FLOW3\Aop\Builder\ClassNameIndex $classNameIndex) {
		if (!preg_match('/^([^\.\(\)\{\}\[\]\?\+\$\!\|]+)/', $this->originalExpressionString, $matches)) {
			return $classNameIndex;
		}
		$prefixFilter = $matches[1];

			// We sort here to make sure the index is okay
		$classNameIndex->sort();

		return $classNameIndex->filterByPrefix($prefixFilter);
	}
}

?>