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
use Neos\Flow\Reflection\ReflectionService;

/**
 * A simple class filter which fires on class names defined by a regular expression
 *
 * @Flow\Proxy(false)
 */
class PointcutClassNameFilter implements PointcutFilterInterface
{
    /**
     * @var ReflectionService
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
    public function __construct($classFilterExpression)
    {
        $this->classFilterExpression = '/^' . str_replace('\\', '\\\\', $classFilterExpression) . '$/';
        $this->originalExpressionString = $classFilterExpression;
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
     * Checks if the specified class matches with the class filter pattern
     *
     * @param string $className Name of the class to check against
     * @param string $methodName Name of the method - not used here
     * @param string $methodDeclaringClassName Name of the class the method was originally declared in - not used here
     * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
     * @return boolean TRUE if the class matches, otherwise FALSE
     * @throws Exception
     */
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)
    {
        try {
            $matchResult = preg_match($this->classFilterExpression, $className);
        } catch (\Exception $exception) {
            throw new Exception('Error in regular expression "' . $this->classFilterExpression . '" in pointcut class filter', 1292324509, $exception);
        }
        if ($matchResult === false) {
            throw new Exception('Error in regular expression "' . $this->classFilterExpression . '" in pointcut class filter', 1168876955);
        }
        return ($matchResult === 1);
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
        if (!preg_match('/^([^\.\(\)\{\}\[\]\?\+\$\!\|]+)/', $this->originalExpressionString, $matches)) {
            return $classNameIndex;
        }
        $prefixFilter = $matches[1];

        // We sort here to make sure the index is okay
        $classNameIndex->sort();

        return $classNameIndex->filterByPrefix($prefixFilter);
    }
}
