<?php
namespace Neos\Flow\Persistence\Aspect;

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

/**
 * Pointcut filter matching embeddable value objects
 *
 * @Flow\Scope("singleton")
 */
class EmbeddedValueObjectPointcutFilter implements \Neos\Flow\Aop\Pointcut\PointcutFilterInterface
{
    /**
     * @var \Neos\Flow\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * Injects the reflection service
     *
     * @param \Neos\Flow\Reflection\ReflectionService $reflectionService The reflection service
     * @return void
     */
    public function injectReflectionService(\Neos\Flow\Reflection\ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Checks if the specified class and method matches against the filter
     *
     * @param string $className Name of the class to check against
     * @param string $methodName Name of the method to check against
     * @param string $methodDeclaringClassName Name of the class the method was originally declared in
     * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
     * @return boolean true if the class / method match, otherwise false
     */
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)
    {
        $valueObjectAnnotation = $this->reflectionService->getClassAnnotation($className, Flow\ValueObject::class);

        if ($valueObjectAnnotation !== null && $valueObjectAnnotation->embedded === true) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if this filter holds runtime evaluations for a previously matched pointcut
     *
     * @return boolean true if this filter has runtime evaluations
     */
    public function hasRuntimeEvaluationsDefinition()
    {
        return false;
    }

    /**
     * Returns runtime evaluations for a previously matched pointcut
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
     * @param \Neos\Flow\Aop\Builder\ClassNameIndex $classNameIndex
     * @return \Neos\Flow\Aop\Builder\ClassNameIndex
     */
    public function reduceTargetClassNames(\Neos\Flow\Aop\Builder\ClassNameIndex $classNameIndex)
    {
        $classNames = $this->reflectionService->getClassNamesByAnnotation(Flow\ValueObject::class);
        $annotatedIndex = new \Neos\Flow\Aop\Builder\ClassNameIndex();
        $annotatedIndex->setClassNames($classNames);
        return $classNameIndex->intersect($annotatedIndex);
    }
}
