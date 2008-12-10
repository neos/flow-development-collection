<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Reflection;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id:\F3\FLOW3\Reflection\ClassReflection.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Extended version of the ReflectionClass
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id:\F3\FLOW3\Reflection\ClassReflection.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ClassReflection extends \ReflectionClass {

	/**
	 * @var \F3\FLOW3\Reflection\DocCommentParser Holds an instance of the doc comment parser for this class
	 */
	protected $docCommentParser;

	/**
	 * The constructor - initializes the class reflector
	 *
	 * @param  string $className: Name of the class to reflect
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($className) {
		parent::__construct($className);
	}

	/**
	 * Replacement for the original getMethods() method which makes sure
	 * that \F3\FLOW3\Reflection\MethodReflection objects are returned instead of the
	 * orginal ReflectionMethod instances.
	 *
	 * @param  long $filter: A filter mask
	 * @return \F3\FLOW3\Reflection\MethodReflection Method reflection objects of the methods in this class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethods($filter = NULL) {
		$extendedMethods = array();

		$methods = ($filter === NULL ? parent::getMethods() : parent::getMethods($filter));
		foreach ($methods as $method) {
			$extendedMethods[] = new \F3\FLOW3\Reflection\MethodReflection($this->getName(), $method->getName());
		}
		return $extendedMethods;
	}

	/**
	 * Replacement for the original getMethod() method which makes sure
	 * that \F3\FLOW3\Reflection\MethodReflection objects are returned instead of the
	 * orginal ReflectionMethod instances.
	 *
	 * @return \F3\FLOW3\Reflection\MethodReflection Method reflection object of the named method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethod($name) {
		$parentMethod = parent::getMethod($name);
		if (!is_object($parentMethod)) return $parentMethod;
		return new \F3\FLOW3\Reflection\MethodReflection($this->getName(), $parentMethod->getName());
	}

	/**
	 * Replacement for the original getConstructor() method which makes sure
	 * that \F3\FLOW3\Reflection\MethodReflection objects are returned instead of the
	 * orginal ReflectionMethod instances.
	 *
	 * @return \F3\FLOW3\Reflection\MethodReflection Method reflection object of the constructor method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConstructor() {
		$parentConstructor = parent::getConstructor();
		if (!is_object($parentConstructor)) return $parentConstructor;
		return new \F3\FLOW3\Reflection\MethodReflection($this->getName(), $parentConstructor->getName());
	}

	/**
	 * Replacement for the original getProperties() method which makes sure
	 * that \F3\FLOW3\Reflection\PropertyReflection objects are returned instead of the
	 * orginal ReflectionProperty instances.
	 *
	 * @param  long $filter: A filter mask
	 * @return array of \F3\FLOW3\Reflection\PropertyReflection Property reflection objects of the properties in this class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProperties($filter = NULL) {
		$extendedProperties = array();
		$properties = ($filter === NULL ? parent::getProperties() : parent::getProperties($filter));
		foreach ($properties as $property) {
			$extendedProperties[] = new \F3\FLOW3\Reflection\PropertyReflection($this->getName(), $property->getName());
		}
		return $extendedProperties;
	}

	/**
	 * Replacement for the original getProperty() method which makes sure
	 * that a \F3\FLOW3\Reflection\PropertyReflection object is returned instead of the
	 * orginal ReflectionProperty instance.
	 *
	 * @param  string $name: Name of the property
	 * @return \F3\FLOW3\Reflection\PropertyReflection Property reflection object of the specified property in this class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProperty($name) {
		return new \F3\FLOW3\Reflection\PropertyReflection($this->getName(), $name);
	}

	/**
	 * Replacement for the original getInterfaces() method which makes sure
	 * that \F3\FLOW3\Reflection\ClassReflection objects are returned instead of the
	 * orginal ReflectionClass instances.
	 *
	 * @return array of \F3\FLOW3\Reflection\ClassReflection Class reflection objects of the properties in this class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getInterfaces() {
		$extendedInterfaces = array();
		$interfaces = parent::getInterfaces();
		foreach ($interfaces as $interface) {
			$extendedInterfaces[] = new \F3\FLOW3\Reflection\ClassReflection($interface->getName());
		}
		return $extendedInterfaces;
	}

	/**
	 * Replacement for the original getParentClass() method which makes sure
	 * that a \F3\FLOW3\Reflection\ClassReflection object is returned instead of the
	 * orginal ReflectionClass instance.
	 *
	 * @return \F3\FLOW3\Reflection\ClassReflection Reflection of the parent class - if any
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParentClass() {
		$parentClass = parent::getParentClass();
		return ($parentClass === NULL) ? NULL : new \F3\FLOW3\Reflection\ClassReflection($parentClass->getName());
	}

	/**
	 * Checks if the doc comment of this method is tagged with
	 * the specified tag
	 *
	 * @param  string $tag: Tag name to check for
	 * @return boolean TRUE if such a tag has been defined, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isTaggedWith($tag) {
		$result = $this->getDocCommentParser()->isTaggedWith($tag);
		return $result;
	}

	/**
	 * Returns an array of tags and their values
	 *
	 * @return array Tags and values
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagsValues() {
		return $this->getDocCommentParser()->getTagsValues();
	}

	/**
	 * Returns the values of the specified tag
	 * @return array Values of the given tag
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagValues($tag) {
		return $this->getDocCommentParser()->getTagValues($tag);
	}

	/**
	 * Returns an instance of the doc comment parser and
	 * runs the parse() method.
	 *
	 * @return \F3\FLOW3\Reflection\DocCommentParser
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getDocCommentParser() {
		if (!is_object($this->docCommentParser)) {
			$this->docCommentParser = new \F3\FLOW3\Reflection\DocCommentParser;
			$this->docCommentParser->parseDocComment($this->getDocComment());
		}
		return $this->docCommentParser;
	}
}

?>