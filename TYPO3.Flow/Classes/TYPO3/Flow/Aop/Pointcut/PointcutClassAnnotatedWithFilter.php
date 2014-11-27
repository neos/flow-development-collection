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
 * A class filter which fires on classes annotated with a certain annotation
 *
 * @Flow\Proxy(false)
 */
class PointcutClassAnnotatedWithFilter implements \TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface {

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var string A regular expression to match annotations
	 */
	protected $annotation;

	/**
	 * @var array
	 */
	protected $annotationValueConstraints;

	/**
	 * The constructor - initializes the class annotation filter with the expected annotation class
	 *
	 * @param string $annotation An annotation class (for example "@TYPO3\Flow\Annotations\Aspect") which defines which class annotations should match
	 * @param array $annotationValueConstraints
	 */
	public function __construct($annotation, array $annotationValueConstraints = array()) {
		$this->annotation = $annotation;
		$this->annotationValueConstraints = $annotationValueConstraints;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService The reflection service
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\Flow\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\Flow\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
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
		$designatedAnnotations = $this->reflectionService->getClassAnnotations($className, $this->annotation);
		if ($designatedAnnotations !== array() || $this->annotationValueConstraints === array()) {
			$matches = ($designatedAnnotations !== array());
		} else {
			// It makes no sense to check property values for an annotation that is used multiple times, we shortcut and check the value against the first annotation found.
			$firstFoundAnnotation = $designatedAnnotations;
			$annotationProperties = $this->reflectionService->getClassPropertyNames($this->annotation);
			foreach ($this->annotationValueConstraints as $propertyName => $expectedValue) {
				if (!array_key_exists($propertyName, $annotationProperties)) {
					$this->systemLogger->log('The property "' . $propertyName . '" declared in pointcut does not exist in annotation ' . $this->annotation, LOG_NOTICE);
					return FALSE;
				}

				if ($firstFoundAnnotation->$propertyName === $expectedValue) {
					$matches = TRUE;
				} else {
					return FALSE;
				}
			}
		}

		return $matches;
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
	 * @param \TYPO3\Flow\Aop\Builder\ClassNameIndex $classNameIndex
	 * @return \TYPO3\Flow\Aop\Builder\ClassNameIndex
	 */
	public function reduceTargetClassNames(\TYPO3\Flow\Aop\Builder\ClassNameIndex $classNameIndex) {
		$classNames = $this->reflectionService->getClassNamesByAnnotation($this->annotation);
		$annotatedIndex = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
		$annotatedIndex->setClassNames($classNames);
		return $classNameIndex->intersect($annotatedIndex);
	}
}
