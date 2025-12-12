<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Mapping\Driver;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Annotations\Reader;
use Neos\Flow\ObjectManagement\Proxy\Compiler;
use Neos\Flow\Reflection\ReflectionService;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class FlowAnotationReader implements Reader
{
    private ReflectionService $reflectionService;

    public function __construct(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Gets the annotations applied to a class.
     *
     * @param ReflectionClass $class The ReflectionClass of the class from which the class annotations should be read.
     * @return array<object> An array of Annotations.
     */
    public function getClassAnnotations(ReflectionClass $class)
    {
        $className = $this->getUnproxiedClassName($class->getName());
        $indexedAnnotations = [];
        foreach ($this->reflectionService->getClassAnnotations($className) as $annotation) {
            $indexedAnnotations[get_class($annotation)] = $annotation;
        }
        return $indexedAnnotations;
    }

    /**
     * Gets a class annotation.
     *
     * @param ReflectionClass $class The ReflectionClass of the class from which the class annotations should be read.
     * @param class-string<T> $annotationName The name of the annotation.
     * @return T|null The Annotation or NULL, if the requested annotation does not exist.
     * @template T
     */
    public function getClassAnnotation(ReflectionClass $class, $annotationName)
    {
        $className = $this->getUnproxiedClassName($class->getName());
        return $this->reflectionService->getClassAnnotation($className, $annotationName);
    }

    /**
     * Gets the annotations applied to a method.
     *
     * @param ReflectionMethod $method The ReflectionMethod of the method from which the annotations should be read.
     * @return array<object> An array of Annotations.
     */
    public function getMethodAnnotations(ReflectionMethod $method)
    {
        $className = $this->getUnproxiedClassName($method->class);
        $indexedAnnotations = [];
        foreach ($this->reflectionService->getMethodAnnotations($className, $method->getName()) as $annotation) {
            $indexedAnnotations[get_class($annotation)] = $annotation;
        }
        return $indexedAnnotations;
    }

    /**
     * Gets a method annotation.
     *
     * @param ReflectionMethod $method The ReflectionMethod to read the annotations from.
     * @param class-string<T> $annotationName The name of the annotation.
     * @return T|null The Annotation or NULL, if the requested annotation does not exist.
     * @template T
     */
    public function getMethodAnnotation(ReflectionMethod $method, $annotationName)
    {
        $className = $this->getUnproxiedClassName($method->class);
        return $this->reflectionService->getMethodAnnotation($className, $method->getName(), $annotationName);
    }

    /**
     * Gets the annotations applied to a property.
     *
     * @param ReflectionProperty $property The ReflectionProperty of the property from which the annotations should be read.
     * @return array<object> An array of Annotations.
     */
    public function getPropertyAnnotations(ReflectionProperty $property)
    {
        $className = $this->getUnproxiedClassName($property->class);
        $indexedAnnotations = [];
        foreach ($this->reflectionService->getPropertyAnnotations($className, $property->getName()) as $annotation) {
            $indexedAnnotations[get_class($annotation)] = $annotation;
        }
        return $indexedAnnotations;
    }

    /**
     * Gets a property annotation.
     *
     * @param ReflectionProperty $property The ReflectionProperty to read the annotations from.
     * @param class-string<T> $annotationName The name of the annotation.
     * @return T|null The Annotation or NULL, if the requested annotation does not exist.
     * @template T
     */
    public function getPropertyAnnotation(ReflectionProperty $property, $annotationName)
    {
        $className = $this->getUnproxiedClassName($property->class);
        return $this->reflectionService->getPropertyAnnotation($className, $property->getName(), $annotationName);
    }

    /**
     * Returns the classname after stripping a potentially present Compiler::ORIGINAL_CLASSNAME_SUFFIX.
     *
     * @param string $className
     * @return string
     */
    protected function getUnproxiedClassName($className)
    {
        return preg_replace('/' . Compiler::ORIGINAL_CLASSNAME_SUFFIX . '$/', '', $className);
    }
}
