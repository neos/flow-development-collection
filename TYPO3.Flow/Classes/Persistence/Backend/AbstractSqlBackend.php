<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Backend;

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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
abstract class AbstractSqlBackend extends \F3\FLOW3\Persistence\Backend\AbstractBackend {

	/**
	 * @var string
	 */
	protected $dataSourceName;

	/**
	 * @var string
	 */
	protected $username;

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * Sets the DSN to use
	 *
	 * @param string $DSN The DSN to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function setDataSourceName($DSN) {
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
		$this->username = $username;
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
		$this->password = $password;
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
			case \F3\FLOW3\Persistence\QueryInterface::JCR_OPERATOR_LIKE:
				$operator = 'LIKE';
				break;
			default:
				throw new \RuntimeException('Unsupported operator encountered.', 1263384870);
		}

		return $operator;
	}

}
?>