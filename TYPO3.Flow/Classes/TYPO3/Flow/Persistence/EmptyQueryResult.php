<?php
namespace TYPO3\Flow\Persistence;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or(at your *
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
 * An empty result list
 *
 * @api
 */
class EmptyQueryResult implements  QueryResultInterface {

	/**
	 * @var \TYPO3\Flow\Persistence\QueryInterface
	 */
	protected $query;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\Flow\Persistence\QueryInterface $query
	 */
	public function __construct(\TYPO3\Flow\Persistence\QueryInterface $query) {
		$this->query = $query;
	}

	/**
	 * Returns a clone of the query object
	 *
	 * @return \TYPO3\Flow\Persistence\QueryInterface
	 * @api
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * Returns NULL
	 *
	 * @return object Returns NULL in this case
	 * @api
	 */
	public function getFirst() {
		return NULL;
	}

	/**
	 * Returns an empty array
	 *
	 * @return array
	 * @api
	 */
	public function toArray() {
		return array();
	}

	/**
	 * @return object Returns NULL in this case
	 */
	public function current() {
		return NULL;
	}

	/**
	 * @return void
	 */
	public function next() {}

	/**
	 * @return integer Returns 0 in this case
	 */
	public function key() {
		return 0;
	}

	/**
	 * @return boolean Returns FALSE in this case
	 */
	public function valid() {
		return FALSE;
	}

	/**
	 * @return void
	 */
	public function rewind() {}

	/**
	 * @param mixed $offset
	 * @return boolean Returns FALSE in this case
	 */
	public function offsetExists($offset) {
		return FALSE;
	}

	/**
	 * @param mixed $offset
	 * @return mixed Returns NULL in this case
	 */
	public function offsetGet($offset) {
		return NULL;
	}

	/**
	 * @param mixed $offset The offset is ignored in this case
	 * @param mixed $value The value is ignored in this case
	 * @return void
	 */
	public function offsetSet($offset, $value) {}

	/**
	 * @param mixed $offset The offset is ignored in this case
	 * @return void
	 */
	public function offsetUnset($offset) {}

	/**
	 * @return integer Returns 0 in this case
	 */
	public function count() {
		return 0;
	}
}

?>