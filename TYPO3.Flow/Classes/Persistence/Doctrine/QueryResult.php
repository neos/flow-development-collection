<?php
namespace TYPO3\FLOW3\Persistence\Doctrine;

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
 * A lazy result list that is returned by Query::execute()
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @api
 */
class QueryResult implements \TYPO3\FLOW3\Persistence\QueryResultInterface {

	/**
	 * @var array
	 */
	protected $rows;

	/**
	 * @var \TYPO3\FLOW3\Persistence\Doctrine\Query
	 */
	protected $query;

	/**
	 * @param array $rows
	 * @param \TYPO3\FLOW3\Persistence\Doctrine\Query $query
	 */
	public function __construct(array $rows, \TYPO3\FLOW3\Persistence\Doctrine\Query $query) {
		$this->rows = $rows;
		$this->query = $query;
	}

	/**
	 * Returns a clone of the query object
	 *
	 * @return \TYPO3\FLOW3\Persistence\Doctrine\Query
	 * @api
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * Returns the first object in the result set
	 *
	 * @return object
	 * @api
	 */
	public function getFirst() {
		return (isset($this->rows[0])) ? $this->rows[0] : NULL;
	}

	/**
	 * Returns an array with the objects in the result set
	 *
	 * @return array
	 * @api
	 */
	public function toArray() {
		return $this->rows;
	}

	/**
	 * Returns the number of objects in the result
	 *
	 * @return integer The number of matching objects
	 * @api
	 */
	public function count() {
		return count($this->rows);
	}

	/**
	 * This method is needed to implement the \ArrayAccess interface,
	 * but it isn't very useful as the offset has to be an integer
	 *
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return isset($this->rows[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->rows[$offset];
	}

	/**
	 * This method has no effect on the persisted objects but only on the result set
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->rows[$offset] = $value;
	}

	/**
	 * This method has no effect on the persisted objects but only on the result set
	 *
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		unset($this->rows[$offset]);
	}

	/**
	 * @return mixed
	 */
	public function current() {
		return current($this->rows);
	}

	/**
	 * @return mixed
	 */
	public function key() {
		return key($this->rows);
	}

	/**
	 * @return void
	 */
	public function next() {
		return next($this->rows);
	}

	/**
	 * @return void
	 */
	public function rewind() {
		reset($this->rows);
	}

	/**
	 * @return void
	 */
	public function valid() {
		return current($this->rows) !== FALSE;
	}

}

?>