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
 * @version $Id:F3::FLOW3::Reflection::Property.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Extended version of the ReflectionProperty
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id:F3::FLOW3::Reflection::Property.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Property extends ReflectionProperty {

	/**
	 * @var F3::FLOW3::Reflection::DocCommentParser: An instance of the doc comment parser
	 */
	protected $docCommentParser;

	/**
	 * The constructor, initializes the reflection class
	 *
	 * @param  string $className: Name of the property's class
	 * @param  string $propertyName: Name of the property to reflect
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($className, $propertyName) {
		parent::__construct($className, $propertyName);
	}

	/**
	 * Checks if the doc comment of this property is tagged with
	 * the specified tag
	 *
	 * @param  string $tag: Tag name to check for
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
	 * @return array Values of the given tag
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagValues($tag) {
		return $this->getDocCommentParser()->getTagValues($tag);
	}

	/**
	 * Returns the value of the reflected property - even if it is protected.
	 *
	 * @param  object $object: Instance of the declaring class to read the value from
	 * @return mixed Value of the property
	 * @throws F3::FLOW3::Reflection::Exception
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo   Maybe support private properties as well
	 */
	public function getValue($object) {
		if (!is_object($object)) throw new F3::FLOW3::Reflection::Exception('$object is of type ' . gettype($object) . ', instance of class ' . $this->class . ' expected.', 1210859212);
		if ($this->isPublic()) return parent::getValue($object);
		if ($this->isPrivate()) throw new F3::FLOW3::Reflection::Exception('Cannot return value of private property "' . $this->name . '.', 1210859206);

		$propertyValues = (array)$object;
		$index = chr(0) . '*' . chr(0) . $this->name;
		if (!isset($propertyValues[$index])) return;

		return $propertyValues[$index];
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