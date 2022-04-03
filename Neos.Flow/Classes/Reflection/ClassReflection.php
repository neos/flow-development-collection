<?php
namespace Neos\Flow\Reflection;

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
 * Extended version of the ReflectionClass
 *
 * @Flow\Proxy(false)
 */
class ClassReflection extends \ReflectionClass
{
    /**
     * @param mixed $classNameOrObject the name of the class or the object to be reflected.
     * @throws Exception\ClassLoadingForReflectionFailedException
     */
    public function __construct($classNameOrObject)
    {
        $throwExceptionOnUnloadedClasses =
            static function ($className) use ($classNameOrObject) {
                throw Exception\ClassLoadingForReflectionFailedException::forClassName($className, $classNameOrObject);
            };
        spl_autoload_register($throwExceptionOnUnloadedClasses);
        try {
            parent::__construct($classNameOrObject);
        } finally {
            spl_autoload_unregister($throwExceptionOnUnloadedClasses);
        }
    }

    /**
     * @var DocCommentParser Holds an instance of the doc comment parser for this class
     */
    protected $docCommentParser;

    /**
     * Replacement for the original getConstructor() method which makes sure
     * that MethodReflection objects are returned instead of the
     * original ReflectionMethod instances.
     *
     * @return MethodReflection Method reflection object of the constructor method
     */
    public function getConstructor(): MethodReflection
    {
        $parentConstructor = parent::getConstructor();
        return (!is_object($parentConstructor)) ? $parentConstructor : new MethodReflection($this->getName(), $parentConstructor->getName());
    }

    /**
     * Replacement for the original getInterfaces() method which makes sure
     * that ClassReflection objects are returned instead of the
     * original ReflectionClass instances.
     *
     * @return array<ClassReflection> Class reflection objects of the properties in this class
     */
    public function getInterfaces(): array
    {
        $extendedInterfaces = [];
        $interfaces = parent::getInterfaces();
        foreach ($interfaces as $interface) {
            $extendedInterfaces[] = new ClassReflection($interface->getName());
        }
        return $extendedInterfaces;
    }

    /**
     * Replacement for the original getMethod() method which makes sure
     * that MethodReflection objects are returned instead of the
     * orginal ReflectionMethod instances.
     *
     * @param string $name
     * @return MethodReflection Method reflection object of the named method
     */
    public function getMethod($name): MethodReflection
    {
        return new MethodReflection($this->getName(), $name);
    }

    /**
     * Replacement for the original getMethods() method which makes sure
     * that MethodReflection objects are returned instead of the
     * original ReflectionMethod instances.
     *
     * @param integer|null $filter A filter mask
     * @return array<MethodReflection> Method reflection objects of the methods in this class
     */
    public function getMethods($filter = null): array
    {
        $extendedMethods = [];

        $methods = ($filter === null ? parent::getMethods() : parent::getMethods($filter));
        foreach ($methods as $method) {
            $extendedMethods[] = new MethodReflection($this->getName(), $method->getName());
        }
        return $extendedMethods;
    }

    /**
     * Replacement for the original getParentClass() method which makes sure
     * that a ClassReflection object is returned instead of the
     * orginal ReflectionClass instance.
     *
     * @return ClassReflection|false Reflection of the parent class - if any
     */
    #[\ReturnTypeWillChange]
    public function getParentClass(): ClassReflection|false
    {
        $parentClass = parent::getParentClass();
        return ($parentClass === false) ? false : new ClassReflection($parentClass->getName());
    }

    /**
     * Replacement for the original getProperties() method which makes sure
     * that PropertyReflection objects are returned instead of the
     * orginal ReflectionProperty instances.
     *
     * @param integer $filter A filter mask
     * @return array<PropertyReflection> Property reflection objects of the properties in this class
     */
    public function getProperties($filter = null): array
    {
        $extendedProperties = [];
        $properties = ($filter === null ? parent::getProperties() : parent::getProperties($filter));
        foreach ($properties as $property) {
            $extendedProperties[] = new PropertyReflection($this->getName(), $property->getName());
        }
        return $extendedProperties;
    }

    /**
     * Replacement for the original getProperty() method which makes sure
     * that a PropertyReflection object is returned instead of the
     * orginal ReflectionProperty instance.
     *
     * @param string $name Name of the property
     * @return PropertyReflection Property reflection object of the specified property in this class
     */
    public function getProperty($name): PropertyReflection
    {
        return new PropertyReflection($this->getName(), $name);
    }

    /**
     * Checks if the doc comment of this method is tagged with
     * the specified tag
     *
     * @param string $tag Tag name to check for
     * @return boolean true if such a tag has been defined, otherwise false
     */
    public function isTaggedWith($tag)
    {
        return $this->getDocCommentParser()->isTaggedWith($tag);
    }

    /**
     * Returns an array of tags and their values
     *
     * @return array Tags and values
     */
    public function getTagsValues()
    {
        return $this->getDocCommentParser()->getTagsValues();
    }

    /**
     * Returns the values of the specified tag
     * @param string $tag
     * @return array Values of the given tag
     */
    public function getTagValues($tag)
    {
        return $this->getDocCommentParser()->getTagValues($tag);
    }

    /**
     * Returns the description part of the doc comment
     *
     * @return string Doc comment description
     */
    public function getDescription()
    {
        return $this->getDocCommentParser()->getDescription();
    }

    /**
     * Creates a new class instance without invoking the constructor.
     *
     * Overridden to make sure DI works even when instances are created using
     * newInstanceWithoutConstructor()
     *
     * @see https://github.com/doctrine/doctrine2/commit/530c01b5e3ed7345cde564bd511794ac72f49b65
     * @return object
     */
    public function newInstanceWithoutConstructor(): object
    {
        $instance = parent::newInstanceWithoutConstructor();

        if (method_exists($instance, '__wakeup') && is_callable([$instance, '__wakeup'])) {
            $instance->__wakeup();
        }

        return $instance;
    }

    /**
     * Returns an instance of the doc comment parser and
     * runs the parse() method.
     *
     * @return DocCommentParser
     */
    protected function getDocCommentParser()
    {
        if (!is_object($this->docCommentParser)) {
            $this->docCommentParser = new DocCommentParser;
            $this->docCommentParser->parseDocComment($this->getDocComment());
        }
        return $this->docCommentParser;
    }
}
