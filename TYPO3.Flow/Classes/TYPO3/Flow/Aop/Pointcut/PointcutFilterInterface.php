<?php
namespace TYPO3\Flow\Aop\Pointcut;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The contract for an AOP Pointcut Filter class
 *
 * @Flow\Proxy(false)
 */
interface PointcutFilterInterface {

	/**
	 * Checks if the specified class and method matches against the filter
	 *
	 * @param string $className Name of the class to check against
	 * @param string $methodName Name of the method to check against
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class / method match, otherwise FALSE
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);

	/**
	 * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
	 *
	 * @return boolean TRUE if this filter has runtime evaluations
	 */
	public function hasRuntimeEvaluationsDefinition();

	/**
	 * Returns runtime evaluations for a previously matched pointcut
	 *
	 * @return array Runtime evaluations
	 */
	public function getRuntimeEvaluationsDefinition();


	/**
	 * This method is used to optimize the matching process.
	 *
	 * @param \TYPO3\Flow\Aop\Builder\ClassNameIndex $classNameIndex An index of class names
	 * @return array The filtered pointcut array, with pointcuts possibly covered by this filter
	 */
	public function reduceTargetClassNames(\TYPO3\Flow\Aop\Builder\ClassNameIndex $classNameIndex);
}

?>