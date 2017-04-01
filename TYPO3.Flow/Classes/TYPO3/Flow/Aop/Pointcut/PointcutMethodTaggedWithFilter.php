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
 * A method filter which fires on methods tagged with a certain annotation
 *
 * @Flow\Proxy(false)
 * @deprecated since 1.0
 */
class PointcutMethodTaggedWithFilter implements \TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface
{
    /**
     * @var \TYPO3\Flow\Reflection\ReflectionService
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
    public function __construct($methodTagFilterExpression)
    {
        $this->methodTagFilterExpression = $methodTagFilterExpression;
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
     * Checks if the specified method matches with the method tag filter pattern
     *
     * @param string $className Name of the class to check against - not used here
     * @param string $methodName Name of the method
     * @param string $methodDeclaringClassName Name of the class the method was originally declared in
     * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection - not used here
     * @return boolean TRUE if the class matches, otherwise FALSE
     * @throws \TYPO3\Flow\Aop\Exception
     */
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)
    {
        if ($methodDeclaringClassName === null || !method_exists($methodDeclaringClassName, $methodName)) {
            return false;
        }
        foreach ($this->reflectionService->getMethodTagsValues($methodDeclaringClassName, $methodName) as $tag => $values) {
            $matchResult = preg_match('/^' . $this->methodTagFilterExpression . '$/i', $tag);
            if ($matchResult === false) {
                throw new \TYPO3\Flow\Aop\Exception('Error in regular expression "' . $this->methodTagFilterExpression . '" in pointcut method tag filter', 1229343988);
            }
            if ($matchResult === 1) {
                return true;
            }
        }
        return false;
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
        return $classNameIndex;
    }
}
