<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Reflection\Fixture;

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
 * ArrayAccess class for the Reflection tests
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ArrayAccessClass implements \ArrayAccess {

	protected $array = array();

	public function __construct(array $array) {
		$this->array = $array;
	}

	public function offsetExists($offset) {
		return array_key_exists($offset, $this->array);
	}

	public function offsetGet($offset) {
		return $this->array[$offset];
	}

	public function offsetSet($offset, $value) {
		$this->array[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->array[$offset]);
	}

}
?>