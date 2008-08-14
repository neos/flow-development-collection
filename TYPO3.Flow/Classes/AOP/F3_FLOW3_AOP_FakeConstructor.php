<?php
declare(ENCODING = 'utf-8');

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
 * @version $Id:F3_FLOW3_Reflection_Method.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * A ReflectionMethod specifically for faking a __construct method
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3_FLOW3_Reflection_Method.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_FakeConstructor extends F3_FLOW3_Reflection_Method {

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var F3_FLOW3_Reflection_DocCommentParser: An instance of the doc comment parser
	 */
	protected $docCommentParser;

	/**
	 * The constructor, initializes the reflection class
	 *
	 * @param string $className Name of the method's class
	 * @param string $methodName ignored
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($className) {
		$this->className = $className;
	}

	/**
	 * Returns the name, '__construct'
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getName() {
		return '__construct';
	}

	/**
	 * Returns the declaring class
	 *
	 * @return F3_FLOW3_Reflection_Class The declaring class
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDeclaringClass() {
		return new F3_FLOW3_Reflection_Class($this->className);
	}

	/**
	 * Replacement for the original getParameters() method which makes sure
	 * that F3_FLOW3_Reflection_Parameter objects are returned instead of the
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
	 * Returns an instance of the doc comment parser and
	 * runs the parse() method.
	 *
	 * @return F3_FLOW3_Reflection_DocCommentParser
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getDocCommentParser() {
		if (!is_object($this->docCommentParser)) {
			$this->docCommentParser = new F3_FLOW3_Reflection_DocCommentParser;
			$this->docCommentParser->parseDocComment('');
		}
		return $this->docCommentParser;
	}
}

?>