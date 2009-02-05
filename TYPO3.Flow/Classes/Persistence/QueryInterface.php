<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

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
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 */

/**
 * A persistence query interface
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface QueryInterface {

	/**
	 * Executes the query against the backend and returns the result
	 *
	 * @return array The query result, an array of objects
	 */
	public function execute();

	/**
	 * The constraint used to limit the result set
	 *
	 * @param mixed $constraint Some constraint, depending on the backend
	 * @return \F3\FLOW3\Persistence\QueryInterface
	 */
	public function matching($constraint);

	/**
	 * Performs a logical conjunction of the two given constraints
	 *
	 * @param mixed $constraint1
	 * @param mixed $constraint2
	 * @return unknown
	 */
	public function logicalAnd($constraint1, $constraint2);

	/**
	 * Performs a logical disjunction of the two given constraints
	 *
	 * @param mixed $constraint1
	 * @param mixed $constraint2
	 * @return unknown
	 */
	public function logicalOr($constraint1, $constraint2);

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param mixed $constraint
	 * @return unknown
	 */
	public function logicalNot($constraint);

	/**
	 * Matches against the (internal) identifier.
	 *
	 * @param string $uuid An identifier
	 * @return \F3\FLOW3\Persistence\OperatorInterface
	 */
	public function withUUID($uuid);

	/**
	 * Returns an equals criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\FLOW3\Persistence\OperatorInterface
	 */
	public function equals($property, $operand);

	/**
	 * Returns a like criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\FLOW3\Persistence\OperatorInterface
	 */
	public function like($property, $operand);

	/**
	 * Returns a less than criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\FLOW3\Persistence\OperatorInterface
	 */
	public function lessThan($property, $operand);

	/**
	 * Returns a less or equal than criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\FLOW3\Persistence\OperatorInterface
	 */
	public function lessThanOrEqual($property, $operand);

	/**
	 * Returns a greater than criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\FLOW3\Persistence\OperatorInterface
	 */
	public function greaterThan($property, $operand);

	/**
	 * Returns a greater than or equal criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\FLOW3\Persistence\OperatorInterface
	 */
	public function greaterThanOrEqual($property, $operand);

}
?>