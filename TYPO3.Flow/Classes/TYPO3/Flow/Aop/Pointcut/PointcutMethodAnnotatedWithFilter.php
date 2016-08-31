<?php
namespace TYPO3\Flow\Aop\Pointcut;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A method filter which fires on methods annotated with a certain annotation
 *
 * @Flow\Proxy(false)
 */
class PointcutMethodAnnotatedWithFilter implements \TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface
{
    /**
     * @var \TYPO3\Flow\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @var \TYPO3\Flow\Log\SystemLoggerInterface
     */
    protected $systemLogger;


    /**
     * @var string The tag of an annotation to match against
     */
    protected $annotation;

    /**
     * @var array
     */
    protected $annotationValueConstraints;

    /**
     * The constructor - initializes the method annotation filter with the expected annotation class
     *
     * @param string $annotation An annotation class (for example "TYPO3\Flow\Annotations\Lazy") which defines which method annotations should match
     * @param array $annotationValueConstraints
     */
    public function __construct($annotation, array $annotationValueConstraints = array())
    {
        $this->annotation = $annotation;
        $this->annotationValueConstraints = $annotationValueConstraints;
    }

    /**
     * Injects the reflection service
     *
     * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService The reflection service
     * @return void
     */
    public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param \TYPO3\Flow\Log\SystemLoggerInterface $systemLogger
     * @return void
     */
    public function injectSystemLogger(\TYPO3\Flow\Log\SystemLoggerInterface $systemLogger)
    {
        $this->systemLogger = $systemLogger;
    }

    /**
     * Checks if the specified method matches with the method annotation filter pattern
     *
     * @param string $className Name of the class to check against - not used here
     * @param string $methodName Name of the method
     * @param string $methodDeclaringClassName Name of the class the method was originally declared in
     * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection - not used here
     * @return boolean TRUE if the class matches, otherwise FALSE
     */
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)
    {
        if ($methodDeclaringClassName === null || !method_exists($methodDeclaringClassName, $methodName)) {
            return false;
        }

        $designatedAnnotations = $this->reflectionService->getMethodAnnotations($methodDeclaringClassName, $methodName, $this->annotation);
        if ($designatedAnnotations !== array() || $this->annotationValueConstraints === array()) {
            return ($designatedAnnotations !== array());
        }
        // It makes no sense to check property values for an annotation that is used multiple times, we shortcut and check the value against the first annotation found.
        $firstFoundAnnotation = $designatedAnnotations;
        $annotationProperties = $this->reflectionService->getClassPropertyNames($this->annotation);
        foreach ($this->annotationValueConstraints as $propertyName => $expectedValue) {
            if (!array_key_exists($propertyName, $annotationProperties)) {
                $this->systemLogger->log('The property "' . $propertyName . '" declared in pointcut does not exist in annotation ' . $this->annotation, LOG_NOTICE);
                return false;
            }

            if ($firstFoundAnnotation->$propertyName !== $expectedValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
     *
     * @return boolean TRUE if this filter has runtime evaluations
     */
    public function hasRuntimeEvaluationsDefinition()
    {
        return false;
    }

    /**
     * Returns runtime evaluations for the pointcut.
     *
     * @return array Runtime evaluations
     */
    public function getRuntimeEvaluationsDefinition()
    {
        return array();
    }

    /**
     * This method is used to optimize the matching process.
     *
     * @param \TYPO3\Flow\Aop\Builder\ClassNameIndex $classNameIndex
     * @return \TYPO3\Flow\Aop\Builder\ClassNameIndex
     */
    public function reduceTargetClassNames(\TYPO3\Flow\Aop\Builder\ClassNameIndex $classNameIndex)
    {
        $classNames = $this->reflectionService->getClassesContainingMethodsAnnotatedWith($this->annotation);
        $annotatedIndex = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $annotatedIndex->setClassNames($classNames);
        return $classNameIndex->intersect($annotatedIndex);
    }
}
