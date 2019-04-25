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
use Neos\Flow\Reflection\ReflectionService;
use Psr\Log\LoggerInterface;

/**
 * A method filter which fires on methods annotated with a certain annotation
 *
 * @Flow\Proxy(false)
 */
class PointcutMethodAnnotatedWithFilter implements PointcutFilterInterface
{
    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var LoggerInterface
     */
    protected $logger;


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
     * @param string $annotation An annotation class (for example "Neos\Flow\Annotations\Lazy") which defines which method annotations should match
     * @param array $annotationValueConstraints
     */
    public function __construct(string $annotation, array $annotationValueConstraints = [])
    {
        $this->annotation = $annotation;
        $this->annotationValueConstraints = $annotationValueConstraints;
    }

    /**
     * Injects the reflection service
     *
     * @param ReflectionService $reflectionService The reflection service
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService): void
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Injects the (system) logger based on PSR-3.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function injectLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Checks if the specified method matches with the method annotation filter pattern
     *
     * @param string $className Name of the class to check against - not used here
     * @param string $methodName Name of the method
     * @param string $methodDeclaringClassName Name of the class the method was originally declared in
     * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection - not used here
     * @return boolean true if the class matches, otherwise false
     */
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier): bool
    {
        if ($methodDeclaringClassName === null || !method_exists($methodDeclaringClassName, $methodName)) {
            return false;
        }

        $designatedAnnotations = $this->reflectionService->getMethodAnnotations($methodDeclaringClassName, $methodName, $this->annotation);
        if ($designatedAnnotations !== [] || $this->annotationValueConstraints === []) {
            return ($designatedAnnotations !== []);
        }

        // It makes no sense to check property values for an annotation that is used multiple times, we shortcut and check the value against the first annotation found.
        $firstFoundAnnotation = $designatedAnnotations;
        $annotationProperties = $this->reflectionService->getClassPropertyNames($this->annotation);
        foreach ($this->annotationValueConstraints as $propertyName => $expectedValue) {
            if (!array_key_exists($propertyName, $annotationProperties)) {
                $this->logger->notice('The property "' . $propertyName . '" declared in pointcut does not exist in annotation ' . $this->annotation);
                return false;
            }

            if ($firstFoundAnnotation->$propertyName !== $expectedValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if this filter holds runtime evaluations for a previously matched pointcut
     *
     * @return boolean true if this filter has runtime evaluations
     */
    public function hasRuntimeEvaluationsDefinition(): bool
    {
        return false;
    }

    /**
     * Returns runtime evaluations for the pointcut.
     *
     * @return array Runtime evaluations
     */
    public function getRuntimeEvaluationsDefinition(): array
    {
        return [];
    }

    /**
     * This method is used to optimize the matching process.
     *
     * @param ClassNameIndex $classNameIndex
     * @return ClassNameIndex
     */
    public function reduceTargetClassNames(ClassNameIndex $classNameIndex): ClassNameIndex
    {
        $classNames = $this->reflectionService->getClassesContainingMethodsAnnotatedWith($this->annotation);
        $annotatedIndex = new ClassNameIndex();
        $annotatedIndex->setClassNames($classNames);
        return $classNameIndex->intersect($annotatedIndex);
    }
}
