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
 * Extended version of the ReflectionClass
 *
 * @package     FLOW3
 * @subpackage  Reflection
 * @version     $Id:T3_FLOW3_Reflection_Class.php 467 2008-02-06 19:34:56Z robert $
 * @copyright   Copyright belongs to the respective authors
 * @license     http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Reflection_Class extends ReflectionClass {

	/**
	 * @var T3_FLOW3_Reflection_DocCommentParser Holds an instance of the doc comment parser for this class
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
		$this->docCommentParser = new T3_FLOW3_Reflection_DocCommentParser;
		$this->docCommentParser->parseDocComment($this->getDocComment());
	}

	/**
	 * Replacement for the original getMethods() method which makes sure
	 * that T3_FLOW3_Reflection_Method objects are returned instead of the
	 * orginal ReflectionMethod instances.
	 *
	 * @param  long $filter: A filter mask
	 * @return T3_FLOW3_Reflection_Method Method reflection objects of the methods in this class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethods($filter = NULL) {
		$extendedMethods = array();

		$methods = ($filter === NULL ? parent::getMethods() : parent::getMethods($filter));
		foreach ($methods as $method) {
			$extendedMethods[] = new T3_FLOW3_Reflection_Method($this->getName(), $method->getName());
		}
		return $extendedMethods;
	}

	/**
	 * Replacement for the original getProperties() method which makes sure
	 * that T3_FLOW3_Reflection_Property objects are returned instead of the
	 * orginal ReflectionProperty instances.
	 *
	 * @param  long $filter: A filter mask
	 * @return T3_FLOW3_Reflection_Property Property reflection objects of the properties in this class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProperties($filter = NULL) {
		$extendedProperties = array();
		$properties = ($filter === NULL ? parent::getProperties() : parent::getProperties($filter));
		foreach ($properties as $property) {
			$extendedProperties[] = new T3_FLOW3_Reflection_Property($this->getName(), $property->getName());
		}
		return $extendedProperties;
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
		$result = $this->docCommentParser->isTaggedWith($tag);
		return $result;
	}

	/**
	 * Returns an array of tags and their values
	 *
	 * @return array Tags and values
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagsValues() {
		return $this->docCommentParser->getTagsValues();
	}

	/**
	 * Returns the values of the specified tag
	 * @return array Values of the given tag
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagValues($tag) {
		return $this->docCommentParser->getTagValues($tag);
	}
}

?>