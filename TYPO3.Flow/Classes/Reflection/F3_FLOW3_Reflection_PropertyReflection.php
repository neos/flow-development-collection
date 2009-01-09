<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Reflection;

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
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id$
 */

/**
 * Extended version of the ReflectionProperty
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class PropertyReflection extends \ReflectionProperty {

	/**
	 * @var \F3\FLOW3\Reflection\DocCommentParser: An instance of the doc comment parser
	 */
	protected $docCommentParser;

	/**
	 * The constructor, initializes the reflection class
	 *
	 * @param string $className Name of the property's class
	 * @param string $propertyName Name of the property to reflect
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
	 * @return array Values of the given tag
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagValues($tag) {
		return $this->getDocCommentParser()->getTagValues($tag);
	}

	/**
	 * Returns the value of the reflected property - even if it is protected.
	 *
	 * @param object $object Instance of the declaring class to read the value from
	 * @return mixed Value of the property
	 * @throws \F3\FLOW3\Reflection\Exception
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Maybe support private properties as well, as of PHP 5.3.0 we can do
	 *   $obj = new Foo;
	 *   $prop = new ReflectionProperty('Foo', 'y'); // y is private member
	 *   $prop->setAccessible(true);
	 *   var_dump($prop->getValue($obj)); // int(2)
	 */
	public function getValue($object = NULL) {
		if (!is_object($object)) throw new \F3\FLOW3\Reflection\Exception('$object is of type ' . gettype($object) . ', instance of class ' . $this->class . ' expected.', 1210859212);
		if ($this->isPublic()) return parent::getValue($object);
		if ($this->isPrivate()) throw new \F3\FLOW3\Reflection\Exception('Cannot return value of private property "' . $this->name . '.', 1210859206);

		parent::setAccessible(TRUE);
		return parent::getValue($object);
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