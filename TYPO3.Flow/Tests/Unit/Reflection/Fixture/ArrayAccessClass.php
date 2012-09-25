<?php
namespace TYPO3\Flow\Tests\Reflection\Fixture;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * ArrayAccess class for the Reflection tests
 *
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