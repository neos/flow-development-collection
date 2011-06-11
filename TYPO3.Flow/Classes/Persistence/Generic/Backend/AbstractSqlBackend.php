<?php
namespace F3\FLOW3\Persistence\Generic\Backend;

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
 * An abstract storage backend for SQL RDBMS
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
abstract class AbstractSqlBackend extends \F3\FLOW3\Persistence\Generic\Backend\AbstractBackend {

	/**
	 * @var string
	 */
	protected $dataSourceName = '';

	/**
	 * @var string
	 */
	protected $username = '';

	/**
	 * @var string
	 */
	protected $password = '';

	/**
	 * Sets the DSN to use
	 *
	 * @param string $DSN The DSN to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function setDataSourceName($DSN) {
		if (!is_string($DSN)) {
			throw new \InvalidArgumentException('The data source name for the persistence backend must be a string, ' . gettype($DSN) . ' given.', 1284115218);
		}
		$this->dataSourceName = $DSN;
	}

	/**
	 * Sets the username to use
	 *
	 * @param string $username The username to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function setUsername($username) {
		if (is_string($username)) {
			$this->username = $username;
		} elseif ($username !== NULL) {
			throw new \InvalidArgumentException('The username for the persistence backend must be a string, ' . gettype($username) . ' given.', 1284115216);
		}
	}

	/**
	 * Sets the password to use
	 *
	 * @param string $password The password to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function setPassword($password) {
		if (is_string($password)) {
			$this->password = $password;
		} elseif ($password !== NULL) {
			throw new \InvalidArgumentException('The password for the persistence backend must be a string, ' . gettype($password) . ' given.', 1284115217);
		}
	}

	/**
	 * Returns the SQL operator for the given QOM operator type.
	 *
	 * @param string $operator One of the OPERATOR_* constants
	 * @return string an SQL operator
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function resolveOperator($operator) {
		switch ($operator) {
			case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_EQUAL_TO:
				$operator = '=';
				break;
			case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_NOT_EQUAL_TO:
				$operator = '!=';
				break;
			case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_LESS_THAN:
				$operator = '<';
				break;
			case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO:
				$operator = '<=';
				break;
			case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_GREATER_THAN:
				$operator = '>';
				break;
			case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO:
				$operator = '>=';
				break;
			case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_LIKE:
				$operator = 'LIKE';
				break;
			case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_IS_EMPTY:
			case \F3\FLOW3\Persistence\QueryInterface::OPERATOR_IS_NULL:
				$operator = 'IS NULL';
				break;
			default:
				throw new \InvalidArgumentException('Unsupported operator encountered.', 1263384870);
		}

		return $operator;
	}

}
?>