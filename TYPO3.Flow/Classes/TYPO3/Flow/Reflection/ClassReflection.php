<?php
namespace TYPO3\Flow\Reflection;

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
 * Extended version of the ReflectionClass
 *
 * @Flow\Proxy(false)
 */
class ClassReflection extends \ReflectionClass {

	/**
	 * Constructor
	 *
	 * @param mixed $classNameOrObject the name of the class or the object to be reflected.
	 * @throws \TYPO3\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException
	 */
	public function __construct($classNameOrObject) {
		$throwExceptionOnUnloadedClasses =
			function ($className) {
				throw new Exception\ClassLoadingForReflectionFailedException('Required class "' . $className . '" could not be loaded properly for reflection, possibly requiring non-existent classes or using non-supported annotations.');
			};
		spl_autoload_register($throwExceptionOnUnloadedClasses);
		try {
			parent::__construct($classNameOrObject);
		} catch (\TYPO3\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException $exception) {
			spl_autoload_unregister($throwExceptionOnUnloadedClasses);
			throw $exception;
		}
		spl_autoload_unregister($throwExceptionOnUnloadedClasses);
	}

	/**
	 * @var \TYPO3\Flow\Reflection\DocCommentParser Holds an instance of the doc comment parser for this class
	 */
	protected $docCommentParser;

	/**
	 * Replacement for the original getConstructor() method which makes sure
	 * that \TYPO3\Flow\Reflection\MethodReflection objects are returned instead of the
	 * original ReflectionMethod instances.
	 *
	 * @return \TYPO3\Flow\Reflection\MethodReflection Method reflection object of the constructor method
	 */
	public function getConstructor() {
		$parentConstructor = parent::getConstructor();
		return (!is_object($parentConstructor)) ? $parentConstructor : new MethodReflection($this->getName(), $parentConstructor->getName());
	}

	/**
	 * Replacement for the original getInterfaces() method which makes sure
	 * that \TYPO3\Flow\Reflection\ClassReflection objects are returned instead of the
	 * original ReflectionClass instances.
	 *
	 * @return array of \TYPO3\Flow\Reflection\ClassReflection Class reflection objects of the properties in this class
	 */
	public function getInterfaces() {
		$extendedInterfaces = array();
		$interfaces = parent::getInterfaces();
		foreach ($interfaces as $interface) {
			$extendedInterfaces[] = new ClassReflection($interface->getName());
		}
		return $extendedInterfaces;
	}

	/**
	 * Replacement for the original getMethod() method which makes sure
	 * that \TYPO3\Flow\Reflection\MethodReflection objects are returned instead of the
	 * orginal ReflectionMethod instances.
	 *
	 * @param string $name
	 * @return \TYPO3\Flow\Reflection\MethodReflection Method reflection object of the named method
	 */
	public function getMethod($name) {
		return new MethodReflection($this->getName(), $name);
	}

	/**
	 * Replacement for the original getMethods() method which makes sure
	 * that \TYPO3\Flow\Reflection\MethodReflection objects are returned instead of the
	 * original ReflectionMethod instances.
	 *
	 * @param integer $filter A filter mask
	 * @return \TYPO3\Flow\Reflection\MethodReflection Method reflection objects of the methods in this class
	 */
	public function getMethods($filter = NULL) {
		$extendedMethods = array();

		$methods = ($filter === NULL ? parent::getMethods() : parent::getMethods($filter));
		foreach ($methods as $method) {
			$extendedMethods[] = new MethodReflection($this->getName(), $method->getName());
		}
		return $extendedMethods;
	}

	/**
	 * Replacement for the original getParentClass() method which makes sure
	 * that a \TYPO3\Flow\Reflection\ClassReflection object is returned instead of the
	 * orginal ReflectionClass instance.
	 *
	 * @return \TYPO3\Flow\Reflection\ClassReflection Reflection of the parent class - if any
	 */
	public function getParentClass() {
		$parentClass = parent::getParentClass();
		return ($parentClass === FALSE) ? FALSE : new ClassReflection($parentClass->getName());
	}

	/**
	 * Replacement for the original getProperties() method which makes sure
	 * that \TYPO3\Flow\Reflection\PropertyReflection objects are returned instead of the
	 * orginal ReflectionProperty instances.
	 *
	 * @param integer $filter A filter mask
	 * @return array of \TYPO3\Flow\Reflection\PropertyReflection Property reflection objects of the properties in this class
	 */
	public function getProperties($filter = NULL) {
		$extendedProperties = array();
		$properties = ($filter === NULL ? parent::getProperties() : parent::getProperties($filter));
		foreach ($properties as $property) {
			$extendedProperties[] = new PropertyReflection($this->getName(), $property->getName());
		}
		return $extendedProperties;
	}

	/**
	 * Replacement for the original getProperty() method which makes sure
	 * that a \TYPO3\Flow\Reflection\PropertyReflection object is returned instead of the
	 * orginal ReflectionProperty instance.
	 *
	 * @param string $name Name of the property
	 * @return \TYPO3\Flow\Reflection\PropertyReflection Property reflection object of the specified property in this class
	 */
	public function getProperty($name) {
		return new PropertyReflection($this->getName(), $name);
	}

	/**
	 * Checks if the doc comment of this method is tagged with
	 * the specified tag
	 *
	 * @param string $tag Tag name to check for
	 * @return boolean TRUE if such a tag has been defined, otherwise FALSE
	 */
	public function isTaggedWith($tag) {
		return $this->getDocCommentParser()->isTaggedWith($tag);
	}

	/**
	 * Returns an array of tags and their values
	 *
	 * @return array Tags and values
	 */
	public function getTagsValues() {
		return $this->getDocCommentParser()->getTagsValues();
	}

	/**
	 * Returns the values of the specified tag
	 * @param string $tag
	 * @return array Values of the given tag
	 */
	public function getTagValues($tag) {
		return $this->getDocCommentParser()->getTagValues($tag);
	}

	/**
	 * Returns the description part of the doc comment
	 *
	 * @return string Doc comment description
	 */
	public function getDescription() {
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
	public function newInstanceWithoutConstructor() {
		$instance = parent::newInstanceWithoutConstructor();

		if (method_exists($instance, '__wakeup') && is_callable(array($instance, '__wakeup'))) {
			$instance->__wakeup();
		}

		return $instance;
	}

	/**
	 * Returns an instance of the doc comment parser and
	 * runs the parse() method.
	 *
	 * @return \TYPO3\Flow\Reflection\DocCommentParser
	 */
	protected function getDocCommentParser() {
		if (!is_object($this->docCommentParser)) {
			$this->docCommentParser = new DocCommentParser;
			$this->docCommentParser->parseDocComment($this->getDocComment());
		}
		return $this->docCommentParser;
	}
}
