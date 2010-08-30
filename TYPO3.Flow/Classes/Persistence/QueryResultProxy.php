<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
 * A lazy result list that is returned by Query::execute() if fetch mode is FETCH_PROXY
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @api
 */
class QueryResultProxy implements \Countable, \Iterator, \ArrayAccess {

	/**
	 * @var \F3\FLOW3\Persistence\QueryInterface
	 */
	protected $query;

	/**
	 * @var array
	 * @transient
	 */
	protected $queryResult;

	/**
	 * Constructor
	 *
	 * @param \F3\FLOW3\Persistence\QueryInterface $query
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Persistence\QueryInterface $query) {
		$this->query = $query;
	}

	/**
	 * Returns a clone of the query object
	 *
	 * @return \F3\FLOW3\Persistence\QueryInterface
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function getQuery() {
		return clone $this->query;
	}

	/**
	 * Loads the objects this QueryResultProxy is supposed to hold.
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function initialize() {
		if ($this->isInitialized() !== TRUE) {
			$this->queryResult = $this->query->execute(\F3\FLOW3\Persistence\QueryInterface::FETCH_ARRAY);
		}
	}

	/**
	 * Returns TRUE if the QueryResultProxy has been initialized
	 * and $this->queryResult contains the actual Query result
	 *
	 * @return boolean
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function isInitialized() {
		return is_array($this->queryResult);
	}

	/**
	 * This method is needed to implement the \ArrayAccess interface,
	 * but it isn't very useful as the offset has to be an integer
	 *
	 * @param mixed $offset
	 * @return boolean
	 * @see \ArrayAccess::offsetExists()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function offsetExists($offset) {
		$this->initialize();
		return isset($this->queryResult[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 * @see \ArrayAccess::offsetGet()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function offsetGet($offset) {
		$this->initialize();
		return isset($this->queryResult[$offset]) ? $this->queryResult[$offset] : NULL;
	}

	/**
	 * This method has no effect on the persisted objects but only on the result set
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 * @see \ArrayAccess::offsetSet()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function offsetSet($offset, $value) {
		$this->initialize();
		$this->queryResult[$offset] = $value;
	}

	/**
	 * This method has no effect on the persisted objects but only on the result set
	 *
	 * @param mixed $offset
	 * @return void
	 * @see \ArrayAccess::offsetUnset()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function offsetUnset($offset) {
		$this->initialize();
		unset($this->queryResult[$offset]);
	}

	/**
	 * @return integer
	 * @see \Countable::count()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function count() {
		return $this->query->count();
	}

	/**
	 * @return mixed
	 * @see \Iterator::current()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function current() {
		$this->initialize();
		return current($this->queryResult);
	}

	/**
	 * @return mixed
	 * @see \Iterator::key()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function key() {
		$this->initialize();
		return key($this->queryResult);
	}

	/**
	 * @return void
	 * @see \Iterator::next()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function next() {
		$this->initialize();
		next($this->queryResult);
	}

	/**
	 * @return void
	 * @see \Iterator::rewind()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function rewind() {
		$this->initialize();
		reset($this->queryResult);
	}

	/**
	 * @return void
	 * @see \Iterator::valid()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valid() {
		$this->initialize();
		return current($this->queryResult) !== FALSE;
	}
}

?>