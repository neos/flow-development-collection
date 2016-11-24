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
use Neos\Flow\Aop\Exception;
use Neos\Flow\Aop\Pointcut\PointcutFilterInterface;
use Neos\Flow\Reflection\ReflectionService;

/**
 * A class type filter which fires on class or interface names
 *
 * @Flow\Proxy(false)
 */
class PointcutClassTypeFilter implements PointcutFilterInterface
{
    /**
     * @var ReflectionService
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
    protected $isInterface = true;

    /**
     * The constructor - initializes the class type filter with the class or interface name
     *
     * @param string $interfaceOrClassName Interface or a class name to match against
     * @throws Exception
     */
    public function __construct($interfaceOrClassName)
    {
        $this->interfaceOrClassName = $interfaceOrClassName;
        if (!interface_exists($this->interfaceOrClassName)) {
            if (!class_exists($this->interfaceOrClassName)) {
                throw new Exception('The specified interface / class "' . $this->interfaceOrClassName . '" for the pointcut class type filter does not exist.', 1172483343);
            }
            $this->isInterface = false;
        }
    }

    /**
     * Injects the reflection service
     *
     * @param ReflectionService $reflectionService The reflection service
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
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
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)
    {
        if ($this->isInterface === true) {
            return (array_search($this->interfaceOrClassName, class_implements($className)) !== false);
        } else {
            return ($className === $this->interfaceOrClassName || is_subclass_of($className, $this->interfaceOrClassName));
        }
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
        return [];
    }

    /**
     * This method is used to optimize the matching process.
     *
     * @param ClassNameIndex $classNameIndex
     * @return ClassNameIndex
     */
    public function reduceTargetClassNames(ClassNameIndex $classNameIndex)
    {
        if (interface_exists($this->interfaceOrClassName)) {
            $classNames = $this->reflectionService->getAllImplementationClassNamesForInterface($this->interfaceOrClassName);
        } elseif (class_exists($this->interfaceOrClassName)) {
            $classNames = $this->reflectionService->getAllSubClassNamesForClass($this->interfaceOrClassName);
            $classNames[] = $this->interfaceOrClassName;
        } else {
            $classNames = [];
        }
        $filteredIndex = new ClassNameIndex();
        $filteredIndex->setClassNames($classNames);

        return $classNameIndex->intersect($filteredIndex);
    }
}
