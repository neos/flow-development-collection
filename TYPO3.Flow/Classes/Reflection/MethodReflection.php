<?php
namespace TYPO3\FLOW3\Reflection;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Extended version of the ReflectionMethod
 *
 * @scope prototype
 * @proxy disable
 */
class MethodReflection extends \ReflectionMethod {

	/**
	 * @var \TYPO3\FLOW3\Reflection\DocCommentParser: An instance of the doc comment parser
	 */
	protected $docCommentParser;

	/**
	 * Returns the declaring class
	 *
	 * @return \TYPO3\FLOW3\Reflection\ClassReflection The declaring class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDeclaringClass() {
		return new ClassReflection(parent::getDeclaringClass()->getName());
	}

	/**
	 * Replacement for the original getParameters() method which makes sure
	 * that \TYPO3\FLOW3\Reflection\ParameterReflection objects are returned instead of the
	 * orginal ReflectionParameter instances.
	 *
	 * @return array of \TYPO3\FLOW3\Reflection\ParameterReflection Parameter reflection objects of the parameters of this method
	 * @author Robert Lemke <robert@typo3.org>
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
	 * Returns the description part of the doc comment
	 *
	 * @return string Doc comment description
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getDescription() {
		return $this->getDocCommentParser()->getDescription();
	}

	/**
	 * Returns an instance of the doc comment parser and
	 * runs the parse() method.
	 *
	 * @return \TYPO3\FLOW3\Reflection\DocCommentParser
	 * @author Robert Lemke <robert@typo3.org>
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