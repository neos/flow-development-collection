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
 * The contract for an AOP Pointcut Filter class
 *
 * @Flow\Proxy(false)
 */
interface PointcutFilterInterface
{
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
     * @param ClassNameIndex $classNameIndex An index of class names
     * @return ClassNameIndex The filtered result, with pointcuts possibly covered by this filter
     */
    public function reduceTargetClassNames(ClassNameIndex $classNameIndex);
}
