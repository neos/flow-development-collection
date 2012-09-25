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
 * Extended version of the ReflectionMethod
 *
 * @Flow\Proxy(false)
 */
class MethodReflection extends \ReflectionMethod {

	/**
	 * @var \TYPO3\Flow\Reflection\DocCommentParser: An instance of the doc comment parser
	 */
	protected $docCommentParser;

	/**
	 * Returns the declaring class
	 *
	 * @return \TYPO3\Flow\Reflection\ClassReflection The declaring class
	 */
	public function getDeclaringClass() {
		return new ClassReflection(parent::getDeclaringClass()->getName());
	}

	/**
	 * Replacement for the original getParameters() method which makes sure
	 * that \TYPO3\Flow\Reflection\ParameterReflection objects are returned instead of the
	 * orginal ReflectionParameter instances.
	 *
	 * @return array of \TYPO3\Flow\Reflection\ParameterReflection objects of the parameters of this method
	 */
	public function getParameters() {
		$extendedParameters = array();
		foreach (parent::getParameters() as $parameter) {
			$extendedParameters[] = new ParameterReflection(array($this->getDeclaringClass()->getName(), $this->getName()), $parameter->getName());
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
	 *
	 * @param string $tag Tag name to check for
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

?>