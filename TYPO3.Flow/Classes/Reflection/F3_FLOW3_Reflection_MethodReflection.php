<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Reflection;

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
 * @version $Id:F3::FLOW3::Reflection::MethodReflection.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Extended version of the ReflectionMethod
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id:F3::FLOW3::Reflection::MethodReflection.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class MethodReflection extends ::ReflectionMethod {

	/**
	 * @var F3::FLOW3::Reflection::DocCommentParser: An instance of the doc comment parser
	 */
	protected $docCommentParser;

	/**
	 * The constructor, initializes the reflection class
	 *
	 * @param  string $className Name of the method's class
	 * @param  string $methodName Name of the method to reflect
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($className, $methodName) {
		parent::__construct($className, $methodName);
	}

	/**
	 * Returns the declaring class
	 *
	 * @return F3::FLOW3::Reflection::ClassReflection The declaring class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDeclaringClass() {
		return new F3::FLOW3::Reflection::ClassReflection(parent::getDeclaringClass()->getName());
	}

	/**
	 * Replacement for the original getParameters() method which makes sure
	 * that F3::FLOW3::Reflection::ParameterReflection objects are returned instead of the
	 * orginal ReflectionParameter instances.
	 *
	 * @return array of F3::FLOW3::Reflection::ParameterReflection Parameter reflection objects of the parameters of this method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParameters() {
		$extendedParameters = array();
		foreach (parent::getParameters() as $parameter) {
			$extendedParameters[] = new F3::FLOW3::Reflection::ParameterReflection(array($this->getDeclaringClass()->getName(), $this->getName()), $parameter->getName());
		}
		return $extendedParameters;
	}

	/**
	 * Checks if the doc comment of this method is tagged with
	 * the specified tag
	 *
	 * @param string $tag Tag name to check for
	 * @return boolean TRUE if such a tag has been defined, otherwise FALSE
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
	 *
	 * @param string $tag Tag name to check for
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
	 * @return F3::FLOW3::Reflection::DocCommentParser
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getDocCommentParser() {
		if (!is_object($this->docCommentParser)) {
			$this->docCommentParser = new F3::FLOW3::Reflection::DocCommentParser;
			$this->docCommentParser->parseDocComment($this->getDocComment());
		}
		return $this->docCommentParser;
	}
}

?>