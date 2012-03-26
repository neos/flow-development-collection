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
 * A class type filter which fires on class or interface names
 *
 * @FLOW3\Proxy(false)
 */
class PointcutClassTypeFilter implements \TYPO3\FLOW3\Aop\Pointcut\PointcutFilterInterface {

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * An interface name to match class types
	 * @var string
	 */
	protected $interfaceOrClassName;

	/**
	 * If the type specified by the expression is an interface (or class)
	 * @var boolean
	 */
	protected $isInterface = TRUE;

	/**
	 * The constructor - initializes the class type filter with the class or interface name
	 *
	 * @param string $interfaceOrClassName Interface or a class name to match agagins
	 */
	public function __construct($interfaceOrClassName) {
		$this->interfaceOrClassName = $interfaceOrClassName;
		if (!interface_exists($this->interfaceOrClassName)) {
			if (!class_exists($this->interfaceOrClassName)) {
				throw new \TYPO3\FLOW3\Aop\Exception('The specified interface / class "' . $this->interfaceOrClassName . '" for the pointcut class type filter does not exist.', 1172483343);
			}
			$this->isInterface = FALSE;
		}
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
	 * Checks if the specified class matches with the class type filter
	 *
	 * @param string $className Name of the class to check against
	 * @param string $methodName Name of the method - not used here
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in - not used here
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class matches, otherwise FALSE
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		if ($this->isInterface === TRUE) {
			return (array_search($this->interfaceOrClassName, class_implements($className)) !== FALSE);
		} else {
			return ($className === $this->interfaceOrClassName || is_subclass_of($className, $this->interfaceOrClassName));
		}
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
		if (interface_exists($this->interfaceOrClassName)) {
			$classNames = $this->reflectionService->getAllImplementationClassNamesForInterface($this->interfaceOrClassName);
		} elseif (class_exists($this->interfaceOrClassName)) {
			$classNames = $this->reflectionService->getAllSubClassNamesForClass($this->interfaceOrClassName);
			$classNames[] = $this->interfaceOrClassName;
		}
		$filteredIndex = new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex();
		$filteredIndex->setClassNames($classNames);

		return $classNameIndex->intersect($filteredIndex);
	}
}

?>