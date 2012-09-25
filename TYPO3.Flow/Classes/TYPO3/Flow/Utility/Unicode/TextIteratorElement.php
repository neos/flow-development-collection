<?php
namespace TYPO3\Flow\Utility\Unicode;

/*                                                                        *
 * This script belongs to the Flow package "Flow".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A PHP-based port of PHP6's built in TextIterator
 *
 * @Flow\Scope("singleton")
 */
class TextIteratorElement {

	/**
	 * @var string
	 */
	private $value;

	/**
	 * @var integer
	 */
	private $offset;

	/**
	 * @var integer
	 */
	private $length;

	/**
	 * @var boolean
	 */
	private $boundary;

	/**
	 * Constructor
	 *
	 * @param string $value The value of the element
	 * @param integer $offset The offset in the original string
	 * @param integer $length
	 * @param boolean $boundary
	 */
	public function __construct($value, $offset, $length=0, $boundary=FALSE) {
		$this->value = $value;
		$this->offset = $offset;
		$this->length = $length;
		$this->boundary = $boundary;
	}

	/**
	 * Returns the element's value
	 *
	 * @return string	The element's value
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns the element's offset
	 *
	 * @return int		The element's offset
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * Returns the element's length
	 *
	 * @return int		The element's length
	 */
	public function getLength() {
		return $this->length;
	}

	/**
	 * Returns TRUE for a boundary element
	 *
	 * @return boolean		TRUE for boundary elements
	 */
	public function isBoundary() {
		return $this->boundary;
	}

}

?>