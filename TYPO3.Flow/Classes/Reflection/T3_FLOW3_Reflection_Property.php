<?php
declare(encoding = 'utf-8');

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
 * Extended version of the ReflectionProperty
 * 
 * @package		FLOW3
 * @subpackage	Property
 * @version 	$Id:T3_FLOW3_Reflection_Property.php 467 2008-02-06 19:34:56Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Reflection_Property extends ReflectionProperty {

	/**
	 * @var T3_FLOW3_Reflection_DocCommentParser: An instance of the doc comment parser
	 */
	protected $docCommentParser;

	/**
	 * The constructor, initializes the reflection class
	 *
	 * @param  string		$className: Name of the property's class
	 * @param  string		$propertyName: Name of the property to reflect
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($className, $propertyName) {
		parent::__construct($className, $propertyName);
		$this->docCommentParser = new T3_FLOW3_Reflection_DocCommentParser;
		$this->docCommentParser->parseDocComment($this->getDocComment());
	}
	
	/**
	 * Checks if the doc comment of this property is tagged with
	 * the specified tag
	 *
	 * @param  string			$tag: Tag name to check for
	 * @return boolean			TRUE if such a tag has been defined, otherwise FALSE
	 */
	public function isTaggedWith($tag) {
		$result = $this->docCommentParser->isTaggedWith($tag);
		return $result;
	}

	/**
	 * Returns an array of tags and their values
	 *
	 * @return array			Tags and values
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagsValues() {
		return $this->docCommentParser->getTagsValues();
	}
	
	/**
	 * Returns the values of the specified tag
	 * 
	 * @return array			Values of the given tag
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagValues($tag) {
		return $this->docCommentParser->getTagValues($tag);
	}
	
	/**
	 * Returns the value of the reflected property - even if it is protected.
	 * 
	 * @param  object			$object: Instance of the declaring class to read the value from
	 * @return mixed			Value of the property
	 * @throws T3_FLOW3_Reflection_Exception
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo   Maybe support private properties as well
	 */
	public function getValue($object) {
		if (!is_object($object)) throw new T3_FLOW3_Reflection_Exception('$object is of type ' . gettype($object) . ', instance of class ' . $this->class . ' expected.');
		if ($this->isPublic()) return parent::getValue($object);
		if ($this->isPrivate()) throw new T3_FLOW3_Reflection_Exception('Cannot return value of private property "' . $this->name .'.');

		$propertyValues = (array)$object;
		$index = chr(0) . '*' . chr(0) . $this->name;
		if (!isset($propertyValues[$index])) return;
		
		return $propertyValues[$index];
	}
}

?>