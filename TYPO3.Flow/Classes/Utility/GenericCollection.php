<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Utility;

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
 * @subpackage Utility
 * @version $Id$
 */

/**
 * Type safe collection. It is similar to an array, but you can only store one type of objects in it.
 * Extend from this class and override the constructor in order to create a typed collection.
 *
 * @package FLOW3
 * @subpackage Utility
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class GenericCollection implements \Countable, \Iterator, \ArrayAccess {

	/**
	 * @var string element type. Only objects of this type can be added to this collection.
	 */
	protected $elementType;

	/**
	 * @var array elements.
	 */
	protected $elements = array();

	/**
	 * @var integer The current Iterator index.
	 */
	protected $iteratorIndex = 0;

	/**
	 * @var integer The total number of elements.
	 */
	protected $iteratorCount = 0;
	
	/**
	 * Constructor.
	 *
	 * @param string element type. Only objects of this type can be added to this collection.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @internal
	 */
	public function __construct($elementType) {
		$this->elementType = $elementType;
	}

	/**
	 * Returns the number of elements.
	 *
	 * @return integer Option count
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @internal
	 */
	public function count() {
		return $this->iteratorCount;
	}

	/**
	 * Returns the current element.
	 *
	 * @return mixed The current element or FALSE if at the end of elements array.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @internal
	 */
	public function current() {
		return current($this->elements);
	}

	/**
	 * Returns the key of the current element.
	 *
	 * @return mixed The current array index or FALSE if at the end of elements array.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @internal
	 */
	public function key() {
		return key($this->elements);
	}

	/**
	 * Returns the next element or FALSE if at the end of elements array.
	 *
	 * @return mixed the next element
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @internal
	 */
	public function next() {
		$this->iteratorIndex ++;
		return next($this->elements);
	}

	/**
	 * Rewinds the iterator index.
	 *
	 * @return mixed the first element or FALSE if elements array is empty.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @internal
	 */
	public function rewind() {
		$this->iteratorIndex = 0;
		reset ($this->elements);
	}

	/**
	 * Checks if the current index is valid.
	 *
	 * @return boolean TRUE If the current index is valid, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @internal
	 */
	public function valid() {
		return $this->iteratorIndex < $this->iteratorCount;
	}

	/**
	 * Offset check for the ArrayAccess interface.
	 *
	 * @param mixed $key
	 * @return boolean TRUE if the key exists in elements array otherwise FALSE
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @internal
	 */
	public function offsetExists($key) {
		return array_key_exists($key, $this->elements);
	}

	/**
	 * Getter for the ArrayAccess interface.
	 *
	 * @param mixed $key Index of the element to retrieve
	 * @return mixed The element at the given index
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @internal
	 */
	public function offsetGet($key) {
		return $this->elements[$key];
	}

	/**
	 * Setter for the ArrayAccess interface.
	 *
	 * @param mixed $key index of the element to set
	 * @param mixed the element. Must be an instance of the type returned by getElementType()
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @see checkElementType()
	 * @see getElementType()
	 * @internal
	 */
	public function offsetSet($key, $element) {
		if (!$element instanceof $this->elementType) {
			throw new \InvalidArgumentException('You may only add instances of "' . $this->elementType . '" to this collection. "' . gettype($element) . '" given.', 1225385998);
		}
		$this->elements[$key] = $element;
		$this->iteratorCount = count($this->elements);
	}

	/**
	 * Unsetter for the ArrayAccess interface.
	 *
	 * @param mixed $key index of the element to unset
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @internal
	 */
	public function offsetUnset($key) {
		unset($this->elements[$key]);
		$this->iteratorCount = count($this->elements);
	}

	/**
	 * Adds an element to the end of the internal elements array.
	 *
	 * @param mixed the element. Must be an instance of the type returned by getElementType()
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @see checkElementType()
	 * @see getElementType()
	 * @internal
	 */
	public function append($element) {
		if (!$element instanceof $this->elementType) {
			throw new \InvalidArgumentException('You may only add instances of "' . $this->elementType . '" to this collection. "' . gettype($element) . '" given.', 1225385998);
		}
		$this->elements[] = $element;
		$this->iteratorCount = count($this->elements);
	}
}
?>
