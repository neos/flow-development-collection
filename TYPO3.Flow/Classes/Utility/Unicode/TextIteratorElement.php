<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Utility\Unicode;

/*                                                                        *
 * This script belongs to the FLOW3 package "FLOW3".                      *
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
 * A PHP-based port of PHP6's built in TextIterator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns the element's offset
	 *
	 * @return int		The element's offset
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * Returns the element's length
	 *
	 * @return int		The element's length
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getLength() {
		return $this->length;
	}

	/**
	 * Returns TRUE for a boundary element
	 *
	 * @return boolean		TRUE for boundary elements
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isBoundary() {
		return $this->boundary;
	}

}

?>