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
 * A class filter which fires on classes tagged with a certain annotation
 *
 * @FLOW3\Proxy(false)
 * @deprecated since 1.0
 */
class PointcutClassTaggedWithFilter implements \TYPO3\FLOW3\AOP\Pointcut\PointcutFilterInterface {

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var string A regular expression to match annotations
	 */
	protected $classTagFilterExpression;

	/**
	 * The constructor - initializes the class tag filter with the class tag filter expression
	 *
	 * @param string $classTagFilterExpression A regular expression which defines which class tags should match
	 */
	public function __construct($classTagFilterExpression) {
		$this->classTagFilterExpression = $classTagFilterExpression;
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
	 * Checks if the specified class matches with the class tag filter pattern
	 *
	 * @param string $className Name of the class to check against
	 * @param string $methodName Name of the method - not used here
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in - not used here
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class matches, otherwise FALSE
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		foreach ($this->reflectionService->getClassTagsValues($className) as $tag => $values) {
			$matchResult = preg_match('/^' . $this->classTagFilterExpression . '$/i', $tag);
			if ($matchResult === FALSE) {
				throw new \TYPO3\FLOW3\AOP\Exception('Error in regular expression "' . $this->classTagFilterExpression . '" in pointcut class tag filter', 1212576034);
			}
			if ($matchResult === 1) return TRUE;
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
}

?>