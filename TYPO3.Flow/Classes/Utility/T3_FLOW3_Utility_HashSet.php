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
 * HashMap of objects that can be administered and searched, while hiding
 * the internal implementation.
 *
 * @package		FLOW3
 * @subpackage	Utility
 * @version 	$Id:T3_FLOW3_Utility_HashSet.php 467 2008-02-06 19:34:56Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Utility_HashSet {

	/**
	 * @var array Holds stored objects
	 */
	protected $table = array();

	/**
	 * Create a HashMap with the specified values.
	 *
	 * @param  array	$values
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function _construct($values = array()) {
		if (is_array($values)) {
			foreach ($values as $key => $value) {
				$hash = $this->getHash($value);
				$this->table[$hash] = $value;
			}
		}
	}

	/**
	 * Returns the hash of a value
	 *
	 * @param  mixed	$value
	 * @return string	$hash: hash
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	protected function getHash($value) {
		if (is_object($value)) {
			$hash = spl_object_hash($value);
		} else {
			$hash = md5($value);
		}

		return $hash;
	}

	/**
	 * Removes all mappings from this map.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	function clear() {
		$this->table = array();
	}

	/**
	 * Returns true if this map contains a mapping for the specified value.
	 *
	 * @param  mixed	$value
	 * @return boolean
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function contains($value) {
		$hash = $this->getHash($value);
		return array_key_exists($hash, $this->table);
	}

	/**
	 * Returns true if this map contains no key-value mappings.
	 *
	 * @return boolean
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function isEmpty() {
		return empty($this->table);
	}

	/**
	 * Adds the specified element to this set if it is not already
	 * present.
	 *
	 * @param  mixed	element to be added to this set.
	 * @return boolean	<tt>true</tt> if the set did not already contain the specified
	 * element.
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function add($value) {
		$hash = $this->getHash($value);
		if (array_key_exists($hash, $this->table)) {
			$this->table[$hash] = $value;
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Removes the mapping for this key from this map if present.
	 *
	 * @param  mixed	$key: remove the mapping for that kwy
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function remove($key) {
		$hash = $this->getHash($key);

		unset($this->table[$hash]);
	}

	/**
	 * Returns the number of key-value mappings in this map.
	 *
	 * @return integer	number of key-value mappings
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function size() {
		return count($this->table);
	}

	/**
	 * Returns an array of the keys contained in this map.
	 *
	 * @return array	array of keys contained in this map
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function keySet() {
		return array_keys($this->table);
	}

	/**
	 * Returns an array of the values contained in this map.
	 *
	 * @return array	array of the values of the map
	 * @author Ronny Unger <ru@php-workx.de>
	*/
	public function values() {
		return array_values($this->table);
	}

	/**
	 * Returns the value to which the specified key is mapped in this identity
	 * hash map, or <tt>null</tt> if the map contains no mapping for this key.
	 * A return value of <tt>null</tt> does not <i>necessarily</i> indicate
	 * that the map contains no mapping for the key; it is also possible that
	 * the map explicitly maps the key to <tt>null</tt>. The
	 * <tt>containsKey</tt> method may be used to distinguish these two cases.
	 *
	 * @param  string	key the key whose associated value is to be returned.
	 * @return array	the value to which this map maps the specified key, or <tt>null</tt> if the map contains no mapping for this key.
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function get($key) {
		if (array_key_exists($key, $this->table)) {
			return $this->table[$key];
		}
		return NULL;
	}
}
?>