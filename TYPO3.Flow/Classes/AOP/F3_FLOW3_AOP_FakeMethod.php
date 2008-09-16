<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::AOP;

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
 * @subpackage AOP
 * @version $Id:F3::FLOW3::Reflection::MethodReflection.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * A ReflectionMethod specifically for faking a __construct method
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3::FLOW3::Reflection::MethodReflection.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class FakeMethod extends F3::FLOW3::Reflection::MethodReflection {

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var string
	 */
	protected $methodName;

	/**
	 * @var F3::FLOW3::Reflection::DocCommentParser: An instance of the doc comment parser
	 */
	protected $docCommentParser;

	/**
	 * The constructor, initializes the reflection class
	 *
	 * @param string $className Name of the method's class
	 * @param string $methodName name of the method
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($className, $methodName) {
		$this->className = $className;
		$this->methodName = $methodName;
	}

	/**
	 * Returns the name, '__construct'
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getName() {
		return $this->methodName;
	}

	/**
	 * Returns the declaring class
	 *
	 * @return F3::FLOW3::Reflection::ClassReflection The declaring class
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDeclaringClass() {
		return new F3::FLOW3::Reflection::ClassReflection($this->className);
	}

	/**
	 * Replacement for the original getParameters() method which makes sure
	 * that F3::FLOW3::Reflection::ParameterReflection objects are returned instead of the
	 * orginal ReflectionParameter instances.
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getParameters() {
		return array();
	}

	/**
	 * Checks if the doc comment of this method is tagged with
	 * the specified tag
	 *
	 * @param string $tag ignored
	 * @return boolean FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isTaggedWith($tag) {
		return FALSE;
	}

	/**
	 * Returns an array of tags and their values
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getTagsValues() {
		return array();
	}

	/**
	 * Returns the values of the specified tag
	 *
	 * @param string $tag ignored
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getTagValues($tag) {
		return array();
	}

	/**
	 * Whether the method is final
	 *
	 * @return boolean FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isFinal() {
		return FALSE;
	}

	/**
	 * Whether the method is abstract
	 *
	 * @return boolean FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isAbstract() {
		return FALSE;
	}

	/**
	 * Returns an instance of the doc comment parser and
	 * runs the parse() method.
	 *
	 * @return F3::FLOW3::Reflection::DocCommentParser
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getDocCommentParser() {
		if (!is_object($this->docCommentParser)) {
			$this->docCommentParser = new F3::FLOW3::Reflection::DocCommentParser;
			$this->docCommentParser->parseDocComment('');
		}
		return $this->docCommentParser;
	}
}

?>